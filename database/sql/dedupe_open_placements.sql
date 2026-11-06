-- =============================================================================
-- Remove duplicate OPEN OJT placements (one student, several pending/active placements)
-- MySQL / MariaDB. Run BEFORE migration 2026_11_06_000005_enforce_one_open_placement_per_student.
--
-- Nothing is hard-deleted: extra placements are SOFT-deleted (deleted_at) after a full copy
-- of each one is saved to placements_dedupe_backup_20261106. Run the steps one at a time.
--
-- Which placement is kept, per student (first rule that differs wins):
--   1. most recorded work: attendance logs + journal entries + evaluation
--   2. already validated by the coordinator
--   3. newest (created_at, then id)
-- Rule 1 comes before "newest" on purpose: an empty newer placement must never win over
-- an older one that already has the student's hours, journals, or evaluation.
-- If the coordinator knows the student is actually at the OTHER company, change the
-- ORDER BY in step 1/2 (or edit the backup table's rows) before step 3.
-- =============================================================================

-- STEP 0. Full backup first (run in a terminal, not in MySQL):
--   C:\xampp\mysql\bin\mysqldump.exe -u root nexus > nexus-backup-before-placement-dedupe.sql

-- STEP 1. Preview. keep_rank = 1 is kept; every row with keep_rank > 1 will be soft-deleted.
SELECT r.student_id, u.name AS student, r.id AS placement_id, r.application_id, r.status,
       r.activity_score, r.is_validated, r.created_at, r.keep_rank,
       IF(r.keep_rank = 1, 'KEEP', 'SOFT-DELETE') AS action
FROM (
    SELECT x.*,
           ROW_NUMBER() OVER (PARTITION BY x.student_id
                              ORDER BY x.activity_score DESC, x.is_validated DESC, x.created_at DESC, x.id DESC) AS keep_rank,
           COUNT(*) OVER (PARTITION BY x.student_id) AS open_count
    FROM (
        SELECT p.id, p.student_id, p.application_id, p.status, p.created_at,
               (p.validated_at IS NOT NULL) AS is_validated,
               (SELECT COUNT(*) FROM attendance_logs a WHERE a.placement_id = p.id)
             + (SELECT COUNT(*) FROM journal_entries j WHERE j.placement_id = p.id)
             + (SELECT COUNT(*) FROM evaluations e WHERE e.placement_id = p.id) AS activity_score
        FROM placements p
        WHERE p.deleted_at IS NULL AND p.status IN ('pending_validation', 'active')
    ) x
) r
JOIN students s ON s.id = r.student_id
JOIN users u ON u.id = s.user_id
WHERE r.open_count > 1
ORDER BY r.student_id, r.keep_rank;

-- STEP 2. Copy every placement that will be soft-deleted into a backup table.
-- (CREATE TABLE commits on its own in MySQL, so it is kept outside the transaction below.)
CREATE TABLE placements_dedupe_backup_20261106 AS
SELECT p.*, r.activity_score, r.keep_rank, NOW() AS backed_up_at
FROM placements p
JOIN (
    SELECT x.id, x.activity_score,
           ROW_NUMBER() OVER (PARTITION BY x.student_id
                              ORDER BY x.activity_score DESC, x.is_validated DESC, x.created_at DESC, x.id DESC) AS keep_rank
    FROM (
        SELECT p2.id, p2.student_id, p2.created_at,
               (p2.validated_at IS NOT NULL) AS is_validated,
               (SELECT COUNT(*) FROM attendance_logs a WHERE a.placement_id = p2.id)
             + (SELECT COUNT(*) FROM journal_entries j WHERE j.placement_id = p2.id)
             + (SELECT COUNT(*) FROM evaluations e WHERE e.placement_id = p2.id) AS activity_score
        FROM placements p2
        WHERE p2.deleted_at IS NULL AND p2.status IN ('pending_validation', 'active')
    ) x
) r ON r.id = p.id
WHERE r.keep_rank > 1;

-- Check the copy: it must list exactly the SOFT-DELETE rows from step 1.
SELECT id, student_id, application_id, status, activity_score, backed_up_at FROM placements_dedupe_backup_20261106;

-- STEP 3. Soft-delete the extra placements (only the rows saved in step 2).
START TRANSACTION;

UPDATE placements p
JOIN placements_dedupe_backup_20261106 b ON b.id = p.id
SET p.deleted_at = NOW()
WHERE p.deleted_at IS NULL;

-- Must return no rows. If it does, run ROLLBACK; instead of COMMIT;.
SELECT student_id, COUNT(*) AS open_placements
FROM placements
WHERE deleted_at IS NULL AND status IN ('pending_validation', 'active')
GROUP BY student_id
HAVING COUNT(*) > 1;

COMMIT;

-- STEP 4. Follow-up for the coordinator. The applications behind the soft-deleted placements
-- are still "accepted". They now show under Coordinator -> Applications -> "Accepted, no placement".
-- Confirm with the student and company which OJT is real; this script does not change them.
SELECT a.id AS application_id, a.student_id, i.title AS internship, a.status
FROM internship_applications a
JOIN placements_dedupe_backup_20261106 b ON b.application_id = a.id
JOIN internships i ON i.id = a.internship_id;

-- Then run:  php artisan migrate

-- -----------------------------------------------------------------------------
-- UNDO (only if needed). If the migration already ran, roll back its batch first
-- (php artisan migrate:rollback), or the restore hits the one-open-placement rule.
--   UPDATE placements p JOIN placements_dedupe_backup_20261106 b ON b.id = p.id SET p.deleted_at = NULL;
-- When everything is confirmed, the backup table can be dropped:
--   DROP TABLE placements_dedupe_backup_20261106;
-- -----------------------------------------------------------------------------
