# Backend Audit Status

This status document records backend reliability and authorization work
implemented and tested in this repository. It is not a claim that every route
has received an independent security audit.

## Resolved

| Area | Status | Evidence |
|---|---|---|
| Email verification | Resolved | Registration sends a hashed six-digit code that expires after 10 minutes; attempts are limited and verification routes are throttled. Covered by `EmailVerificationTest`. |
| Role/profile consistency | Resolved | Admin role changes create a missing role profile transactionally and preserve older role profiles. Missing profiles are routed to profile completion. Last-active-admin and self-role safeguards are covered in `AdminUserRoleProfileTest`. |
| Application status integrity | Resolved | A centralized allowed-transition map is checked during validation and again during the locked update. Valid changes record status history; ownership and illegal transitions are tested in `ApplicationLifecycleHistoryTest`. |
| Multi-record database writes | Resolved for reviewed workflows | Internship/requirement writes, status/history writes, certificate/portfolio creation, and related profile/application writes use transaction boundaries. Failure behavior is covered in `MultiRecordAtomicityTest`. |
| Shared evidence-file lifecycle | Resolved for reviewed certificate/portfolio paths | File deletion checks remaining certificate/portfolio references; new uploads are compensated on rollback and replaced/deleted files are removed after database success. |
| Existing public evidence files | Migration command implemented | `files:secure-existing` copies and SHA-256 verifies referenced files to private storage and retains public copies by default. Missing references cause a nonzero exit. Public deletion requires the explicit `--remove-public` option. |
| Dashboard metrics cache | Resolved for observed model writes | Model observers invalidate the relevant admin, student, employer, coordinator, and evidence/recommendation keys. Covered in `DashboardCacheInvalidationTest`. |
| Core feature-test coverage | Expanded | Tests exercise authentication flows, representative cross-role denials, admin user CRUD, employer opportunity ownership/CRUD, private document ownership, reports, duplicate applications, status history, and atomic file/database behavior. |

## Known gaps and cautions

- Role-denial tests cover representative dashboard and role-area pages, not
  every route, method, and policy branch. Continue adding route-level tests as
  endpoints change.
- Invalid login, inactive account login, invalid/expired reset tokens,
  password-reset throttling, and email-verification throttling are not all
  independently covered by the current expanded tests.
- Coordinator report history is owner-scoped, but a separate
  report-download-by-ID endpoint is not currently exposed/tested. Reports are
  generated and returned by the generation request.
- Both PDF and CSV report generation should be exercised when making changes
  to export templates or formats; automated report coverage is not exhaustive.
- The legacy-file command correctly fails if any referenced path is missing
  from both disks. That failure requires data cleanup or file restoration;
  do not bypass it with `--remove-public`.
- Filesystem operations cannot be rolled back by a database transaction.
  Existing code uses compensating cleanup and post-commit deletion for reviewed
  flows; newly introduced file writes must follow the same pattern.
- Production readiness still depends on external configuration: secure
  secrets, least-privilege database accounts, HTTPS, reliable backups, mail
  transport, queue workers, and monitoring.

## Test snapshot

The full Pest suite passed **254 tests and 2187 assertions** when this document
was updated. Run `php artisan test` to verify against the current working tree.
