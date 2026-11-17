# Manuscript vs system check

Source documents: `Skill-Bridge-Final-Compiled.pdf` (printed page numbers below).
System: repo `main` at `48463f2`, read from routes, migrations, models, controllers, services and tests (not run in a browser).
Schema: live DB plus migrations. `appraisals`, `company_invitations` and `ojt_reminders` come from migration files only, because migration `2026_11_06_000005` failed locally.
Decision recorded: the system is the source of truth. Coordinators do not verify certificates or competencies.

## 1. Summary

| Status | Count |
|---|---|
| MATCH | 18 |
| MISMATCH | 13 |
| SYSTEM ONLY | 10 |
| DOCUMENTS ONLY | 4 |

## 2. Comparison table

| # | Item | In documents? | In system? | Status | Notes |
|---|---|---|---|---|---|
| 1 | Laravel 12, PHP 8.2+, Blade, Eloquent, MySQL | p.25, p.46 | composer.json | MATCH | |
| 2 | "Native PHP", custom PHP auth, PHP sessions, PDO | p.17-19 (Table 2) | Laravel auth and sessions, Eloquent | MISMATCH | Contradicts p.25 and p.46 |
| 3 | PHP 8.0 deployment | p.17 (Table 1) | composer.json `^8.2` | MISMATCH | |
| 4 | Table 2 omits Blade, Vite, DomPDF, Pusher, Resend, Chart.js, Bootstrap Icons, Pest | p.17-19 | composer.json, package.json, layouts/app.blade.php | SYSTEM ONLY | |
| 5 | Pusher live updates (opt-in) | p.25, p.28 | config/broadcasting.php:17 (default `log`) | MATCH | |
| 6 | Database queue with a worker | p.25, p.31 | .env.example `QUEUE_CONNECTION=sync`; config/queue.php default `database` | MISMATCH | Which does production use? |
| 7 | Self-registration | p.19, p.26, p.88-89 (TC-001) | No register route in routes/web.php | DOCUMENTS ONLY | Accounts are school-issued (p.100, p.143) |
| 8 | Login by email or school ID, email verification, forced password change, 6-digit reset code | p.27, p.100 | routes/web.php:85-131 | MATCH | |
| 9 | Four roles | p.15, p.40-44 | UserRole enum | MATCH | |
| 10 | Fig 1 roles: Dean/Dept. Chair, HR Manager, On-Site Supervisor | p.15 | No such roles (DESIGN.md) | DOCUMENTS ONLY | Employer acts as supervisor |
| 11 | Admin modules (dashboard, academics, eligibility, users, import, OJT placements, announcements, skill categories and tags, reported content, audit log, health, reports, backup, suspension) | p.101-107, FR-AD1-14 | routes/web.php:371-444 | MATCH | 365-day retention not verified |
| 12 | Company verification gate and Company Invitations | Only the "Verified company" badge, p.112 | web.php:411, 422, 274, 354-356 | SYSTEM ONLY | |
| 13 | Scheduled jobs and automatic daily backup | Backup described as manual (FR-AD14, p.106) | routes/console.php | SYSTEM ONLY | |
| 14 | Student profile, certificates, portfolio, deployment documents, character reference, privacy consent | p.108-109, FR-ST3-7 | routes/web.php:179-232 | MATCH | |
| 15 | Awards, 2x2 ID photo, profile change requests | Change requests only as a notification (p.122) | web.php:184, 195, 201 | SYSTEM ONLY | |
| 16 | Resume builder, newest 3 PDFs kept | p.110, FR-ST7 | ResumeBuilderService.php:21 | MATCH | |
| 17 | Resume editor (edit, preview, save as copy) | not found | web.php:237-241 | SYSTEM ONLY | |
| 18 | "archive resumes" | p.145 (App. G) | Delete only (web.php:235); p.110 says delete | MISMATCH | |
| 19 | Browse, filter, match %, recommendations, feedback | p.109, FR-ST5 | web.php:242-245 | MATCH | |
| 20 | Matching uses skills, competencies, experience and year level | p.6, p.36 | CompetencyMatchingService.php: weighted required skills vs proficiency, program-scoped | MISMATCH | No experience or year-level input found (lines 1-170 reviewed) |
| 21 | New application status "pending" | p.56, p.67, p.90 | ApplicationStatus enum has no "pending" | MISMATCH | Values: applied, submitted, reviewed, interview, accepted, rejected, completed, withdrawn |
| 22 | Apply, track, edit, withdraw, status history, interview date | p.56, Table 64 | web.php:245-254 | MATCH | |
| 23 | Employer: profile, postings, applicants, browse, shortlist, invitations, interns | p.112-116, FR-EM1-11 | web.php:267-298 | MATCH | |
| 24 | Employer Skill Catalog | Fig 9.4 (p.113), Fig 3.2 (p.49), App. G (p.146) | No route, view or nav link | DOCUMENTS ONLY | Looks removed |
| 25 | Coordinator: students, eligibility, applications, companies and MOA, report reviews, statistics, OJT monitoring, reports, analytics | p.116-122 | web.php:300-369 | MATCH | |
| 26 | Coordinator approves or rejects competencies and certificates | p.5, p.43-44, Fig 3.3, p.120, p.147 | web.php:322-326 limit review to `portfolio`; VerificationController.php:550-552 | MISMATCH | Admin reviews portfolio, certificate, resume files (web.php:396) |
| 27 | Coordinator "Position" field | Fig 10.12, p.147 | Coordinator/ProfileController.php:85-88 keeps it in the session only; no column | MISMATCH | Lost on logout |
| 28 | OJT: placements, DTR, journals, evaluation, clearance, certificate | p.28, p.111, p.115, p.120 | web.php:256-265, 288-297, 335-340, 172 | MATCH | |
| 29 | One open placement per student | p.28 | migration 2026_11_06_000005 | MATCH | Currently failing locally on duplicate data |
| 30 | Monthly Evaluations (appraisals) | not found | web.php:263, 297, 342-345; `appraisals` | SYSTEM ONLY | |
| 31 | Printable school forms (Activity Narrative, Final Evaluation) | not found | web.php:174-177 | SYSTEM ONLY | |
| 32 | Chat and notification list | p.107, p.111, p.116, p.122 | web.php:140-163 | MATCH | |
| 33 | Edit, unsend, react, pin, forward, reply; notification trash and bulk | not found | web.php:161-168, 148-152 | SYSTEM ONLY | |
| 34 | Global search; user Report button | Search box in screenshots only | web.php:139, 157 | SYSTEM ONLY | |
| 35 | Recommendation: add email alerts | p.126 | Email already sent via Resend (app/Mail) | MISMATCH | `users.sms_notifications` exists, no SMS code |
| 36 | Recommendation: build performance monitoring module | p.126 | Already built | MISMATCH | |
| 37 | Not built: mobile app, AI matching, video interviews, credential verification | p.7, p.126 | none found | MATCH | |
| 38 | "More than 250 test cases" | p.29 | about 597 `it()`/`test()` calls in tests/ | MISMATCH | Run `php artisan test` for the exact figure |
| 39 | Data dictionary: 29 tables documented, 57 exist | p.59-82 | 28 undocumented (list in section 4, item 10) | SYSTEM ONLY | |
| 40 | 14 documented tables lack columns | Tables 40, 43, 45-48, 51, 53, 55, 57, 59-61, 63 | live schema | MISMATCH | |
| 41 | Table 65 "Company Reviews" | p.82 | fields describe `recommendation_feedback` | MISMATCH | |
| 42 | ERD Fig 4 shows `competency_assessments`, `internship_skills`, `interviews`, `task_submissions`, `applications` | p.59 | none exist | DOCUMENTS ONLY | Visible crop only |
| 43 | 13 tables match exactly | Tables 37-39, 41, 42, 44, 50, 52, 54, 56, 58, 62, 64 | live schema | MATCH | |
| 44 | Health page, DB health command, role-based tutorial | p.31 | routes/health.php, CheckDatabaseHealth, TUTORIAL.md | MATCH | |
| 45 | Cost tables 19-23 arithmetic | p.36-38 | n/a | MATCH | Totals recompute |

Missing columns (item 40):
- certificates: category, uploaded_by_user_id, review_notes, reviewed_at, reviewed_by, reviewed_by_user_id, student_competency_id
- employers: company_size, deleted_at, verified_at, verified_by_user_id, four moa_* columns
- institutions: verified_at, verified_by_user_id
- internships: expires_at, alerts_sent_at, deadline_reminded_at, deleted_at, requires_character_reference, requested_documents, hidden_at, hidden_reason
- internship_applications: resume_id, deleted_at
- internship_requirements: is_required
- messages: reply_to_id, hidden_at, edited_at, unsent_at, sender_removed_at, receiver_removed_at, pinned_at, pinned_by, forwarded_at
- notifications: archived_at, deleted_at
- portfolios: category, deployment_requirement_id, uploaded_by_user_id, verification_status, review_notes, reviewed_at, reviewed_by_user_id, reviewed_by, tools_used, project_url, achievements
- resumes: category, uploaded_by_user_id, archived_at, verification_status, review_notes, reviewed_at, reviewed_by_user_id
- skills: name_normalized, category_id
- students: about 40 columns (program_id, major_id, gwa, section, school_year, semester, enrollment_status, profile_verified_*, rendered_internship_hours, bio-data and show_* columns, privacy_consent*, discoverable, id_photo_path, student_id_locked)
- student_competencies: certificate_type, skill_type, review_notes, reviewed_at, reviewed_by
- users: email_notifications, sms_notifications, must_change_password, deleted_at, verification_code*, verification_attempts, archived_at, suspended_*

## 3. Internal consistency issues in the manuscript

1. Team roles: Table 66 (p.130) vs p.85-86 (adds "Data Analyst" and "Tester"). Both Developers have identical contribution rows (p.131).
2. Titles: Approval Sheet (p.ii) has Lisondra as Program Head and Moreno as Dean; Transmittal (p.136) addresses Lisondra as "Dean" and signs "Program Chair"; Ch. VI p.125 says "the Dean".
3. Testing status contradicts itself: p.29 (250+ tests done each sprint), p.46, p.88 (user evaluation not yet conducted), p.89-91 (all "Pending testing"), p.32 and p.125 (feedback and evaluation gathered), p.124 (promises user-testing findings).
4. Fig 2 (p.22) shows six Agile phases; p.82 says four.
5. Fig 1: List of Figures says "Organizational Chart", caption says "User Hierarchy Diagram"; p.14 omits the coordinator.
6. Table 49 (Jobs, p.68) is empty.
7. Objectives (p.5): Placement Module and Opportunity Management Module have the same wording.
8. Naming: manuscript "Skill-Bridge", app UI "SkillBridge".
9. TOC and List of Figures page numbers spot-checked and agree; not every entry checked.

## 4. Edits to make (system is the source of truth)

### A. In the system, missing from the documents

1. **Tech stack** (Table 1 p.17, Table 2 p.17-19, text p.19-20)
   - Replace "Native PHP / Custom PHP Authentication / PHP Session Management / PDO" with "Laravel 12 (PHP 8.2+), Laravel authentication and sessions, Eloquent ORM".
   - Table 1: "PHP 8.0" becomes "PHP 8.2 or later". Add Railway to the Deployment row.
   - Table 2: add Blade, Vite, DomPDF, Resend, Pusher (optional), Chart.js, Bootstrap Icons, Pest.
2. **Company verification and company invitations**
   - FR-AD3 (p.40): add verifying or unverifying employer companies and viewing company invitations.
   - New FR-AD15: the administrator monitors company invitations sent by coordinators.
   - FR-EM4 (p.42): add that posting, browsing students and inviting require an admin-verified company.
   - FR-IC10 (p.44): add inviting companies without an account (email link, resend, expiry).
   - Fig 3.3 (p.50): add use case "Invite companies".
   - Ch V: add Admin "Company Invitations" figure after Fig 7.14 and Coordinator "Invite companies" figure after Fig 10.5; renumber figures.
   - Appendix G: add a bullet in the Administrator and Coordinator lists.
3. **Scheduled jobs and daily backup**
   - FR-AD14 (p.41): add automatic daily database backup at 2:00 AM.
   - Deployment activity 2 (p.31): list the scheduler jobs: daily backup 02:00, competency expiry check 08:00, log pruning 03:00, MOA reminders 07:00, internship deadline reminders 08:30, OJT document reminders 08:00 (Philippine time), hourly expiry of student and company invitations.
   - Maintenance (p.94): "automatic daily backup plus manual backup".
   - Table 12 (p.32): add "Scheduler configuration".
4. **Student awards, 2x2 ID photo, profile change requests**
   - FR-ST3 (p.41): add awards, ID photo upload, and change requests for locked fields.
   - FR-IC3 or FR-IC9 (p.43-44): the coordinator reviews student change requests.
   - Fig 8.2 text (p.108): add "Awards" and "2x2 ID photo" to the profile sections.
   - Table 60 (students): add `id_photo_path`. Add `student_awards` and `student_change_requests` (item 10).
5. **Resume editor**
   - FR-ST7 (p.41), Fig 8.6 text (p.110), Appendix G Resume bullet (p.145): add editing, previewing and saving as a new copy without changing the profile.
6. **Monthly Evaluations (appraisals)**
   - Ch IV Development activity 3 (p.28): add monthly evaluations (student self-rating, supervisor rating, adviser rating) with a printable PDF.
   - FR-ST10, FR-EM10, FR-IC11: student self-rating and comment; employer supervisor rating; coordinator requests (one or all active placements), adviser rating, reopen a step.
   - Narratives (p.51-58): add UC-SB-11 "OJT Monthly Evaluation".
   - Fig 3.1, 3.2, 3.3: add the use case. Fig 8.7, 9.8, 10.9 text: mention monthly evaluations.
   - Data dictionary: add `appraisals`.
7. **Printable school forms**
   - FR-ST10, FR-EM10, FR-IC11, Ch IV (p.28, p.39), Fig 8.7, 9.8, 10.9 text: add printing of the weekly Activity Narrative Report and Final Performance Evaluation.
   - Ch VI System Outputs (p.124-125): add an "OJT Forms and Certificate" output.
   - Appendix E.4 (p.140): optionally add a sample.
8. **Messaging actions and notification handling**
   - FR-ST8, FR-EM8, FR-IC8, FR-AD9: send, edit, unsend, reply, react, pin and forward messages (within allowed role pairs).
   - Fig 7.13, 8.8, 9.9, 10.13 text: add the same, plus notifications can be archived, deleted, restored and handled in bulk.
   - FR-ST11 (p.42): add email notification preferences.
   - Tables 51 and 53: add missing columns. Add `message_reactions`.
9. **Global search and Report button**
   - New FR-ST12, FR-EM12, FR-IC12: search students, companies and skills from the top bar (results limited by role).
   - Student, employer, coordinator: report a posting, company, message or user to the administrators.
   - Fig 7.9 text (p.105): reports are submitted by any user through the Report button.
   - Appendix G: add Search and Report to the Messages and Notifications bullets.
10. **Data dictionary (p.59-82)**
    - Add 28 tables after Table 65 as Tables 66-93. Team Assignment and Contribution Report become Tables 94 and 95; update the List of Tables (p.xi) and all references. Do `placements`, `attendance_logs`, `journal_entries`, `evaluations` first (named on p.25).
    - Tables to add: academic_settings, appraisals, attendance_logs, character_references, company_invitations, content_reports, deployment_requirements, eligibility_criteria, eligibility_decisions, employer_shortlists, evaluations, internship_flags, internship_invitations, internship_program, journal_entries, majors, message_reactions, ojt_reminders, placements, programs, skill_categories, skill_tags, student_awards, student_change_requests, student_competency_skills, student_experiences, student_skills, verification_activities.
    - Extend the 14 tables with the missing columns above.
    - Rename Table 65 to "Recommendation Feedback" (`recommendation_feedback`) in the caption and List of Tables.
    - Fill Table 49 (Jobs).
    - Replace ERD Fig 4 (p.59) with one built from the current schema. `docs/chapter-4/` already has ERD figures (`fig-4-01` to `fig-4-06`); check whether they match the current schema before reusing. Remove `competency_assessments`, `internship_skills`, `interviews`, `task_submissions`, `applications`.

### B. Coordinators do not verify certificates or competencies
Assumption to confirm: coordinators review portfolio and deployment documents only. If certificates added through My Certificates also reach the Verification Queue, adjust the wording.

| Where | Change |
|---|---|
| Executive Summary (p.xiii) | "certificate submission with coordinator verification" becomes "certificate and competency records with portfolio document review" |
| p.5 and p.46 | "competency verification by the coordinator" becomes "document review by the coordinator" |
| FR-IC9 (p.44) | "review portfolio and deployment documents and eligibility decisions" |
| Fig 3.3 (p.50) | remove "Verify evidence and documents" or rename "Review portfolio documents" |
| Fig 10.8 text (p.120) | describe the queue as reviewing portfolio submissions |
| Appendix G Coordinators (p.147) | remove "competencies, certificates" from the Verification bullet |
| Ch VI Summary of Findings (p.125) | replace the sentence about coordinators reviewing competencies with portfolio review wording |
| Administrator functions | add reviewing portfolio, certificate and resume files (FR-AD3 or a new FR) |

### C. Other document fixes (system is truth)
- Remove self-registration (p.19, p.26, p.88); rewrite TC-001 around school-issued accounts and CSV import.
- Remove the Employer Skill Catalog from Fig 9.4, Fig 3.2, the List of Figures and Appendix G.
- Replace "pending" with the real status in p.56, p.67, p.90.
- Fix the matching description (p.6, p.36): proficiency-weighted required skills within the student's program.
- Appendix G (p.145): "archive resumes" becomes "delete resumes".
- Update the test count (p.29); reconcile p.29, p.46, p.88-91, p.125.
- Recommendations (p.126): email already exists (only SMS is missing); performance monitoring is already built.
- Coordinator Position (Fig 10.12): remove it from the documents, or add a column in the system.
- Fix the team roles (p.85-86), Dean and Program Head titles (p.136), Fig 1 title and roles, six-versus-four phases, and the duplicated objective (p.5).

## 5. System items to fix (not document edits)
1. Coordinator "Position" is session-only; add a `coordinators` column or remove the field.
2. Queue setting: pick one default and align `.env.example`.
3. Migration `2026_11_06_000005` blocks on duplicate open placements (student 1). Run `database/sql/dedupe_open_placements.sql` after backup and reading its notes.

## 6. Open questions
1. Does production run `QUEUE_CONNECTION=database` with a worker, or `sync`?
2. Has user evaluation (survey, interviews) actually been carried out?
3. Should Fig 1 roles (Dean, HR Manager, On-Site Supervisor) stay as an organizational view?
4. Cost tables use 12-month figures but the project ran June to November 2026. Intentional?
5. Is Tailwind used anywhere? It is in package.json, but the layout loads Bootstrap from a CDN.
6. Do certificates added via My Certificates appear in the coordinator Verification Queue?
