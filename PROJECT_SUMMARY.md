# Project Summary

## Purpose

Skill-Bridge is a role-based internship and student-development system built
with Laravel 12 and PHP 8.2+. It supports students, employers, institution
coordinators, and administrators. The core workflow is internship opportunity
posting, student application, employer status progression, and coordinator
monitoring.

The system is not a freelance marketplace. Its matching feature is rule-based,
not machine-learning or AI-powered.

## Implemented capabilities

### Students

- Manage a student profile and profile-completion fields.
- Add competencies and evidence, and maintain certificates and portfolio
  records.
- Generate PDF resumes.
- Browse internship opportunities, receive deterministic rule-based matching
  recommendations, submit applications, and view application progress.

### Employers

- Manage an employer profile and internship opportunities.
- Review applicants and access applicant documents subject to authorization.
- Update application statuses using the application's allowed transition map;
  valid changes are recorded in status history.

### Coordinators

- Review competency, certificate, and portfolio evidence for students within
  their institution.
- View student and placement information and generate PDF/CSV reports.

### Administrators

- Manage users and role profiles, announcements, activity logs, and reports.
- Create users and change roles while retaining existing role-profile data.
- Protect the last active administrator from removal or deactivation.

### Shared behavior

- Password reset and six-digit, expiring email verification codes for newly
  registered accounts.
- Role and ownership authorization on protected pages and document routes.
- Queued in-app notification jobs and email delivery when a working mail
  transport is configured. SMS delivery is not implemented.
- Private-disk storage for protected documents; authorized application routes
  serve them.
- Dashboard cache invalidation when users, internships, applications, or
  evidence change.

## Technical stack

- Laravel 12, PHP 8.2+ and Eloquent ORM.
- MySQL/MariaDB by default in `.env.example`; test configuration can use
  SQLite.
- Blade, Vite, and the frontend dependencies listed in `package.json`.
- Pest 3 for feature and unit tests.
- `barryvdh/laravel-dompdf` for PDF generation.

## Data and file integrity

Related multi-record writes are grouped in database transactions where
required. Generated files are cleaned up when persistence fails, and deletion
of shared certificate/portfolio paths checks whether another record still
references the file. Legacy public files can be copied and SHA-256 verified
with `files:secure-existing`; public copies are retained unless the explicit
`--remove-public` option is used.

## Verification snapshot

At the time this summary was updated, the full Pest suite passed **58 tests
with 623 assertions**. The suite covers core authentication, role access,
application transitions, ownership checks, private document access, reports,
cache invalidation, atomic writes, and shared-file lifecycle behavior.

Passing tests are not a substitute for a production security assessment or
manual review of every route.

## Current limitations

- Matching uses explicit competency, verification, and proficiency rules; it
  does not learn from data or use an AI model.
- SMS sending is not implemented.
- Notification delivery depends on mail configuration and a running worker
  for queued work. Delivery is not guaranteed to be real-time.
- Tests cover representative role denial paths, not every route and HTTP
  method. Some negative authentication cases and complete report-format and
  deployment scenarios remain candidates for more tests.
- Operational production deployment, backups, monitoring, and mail/queue
  infrastructure must be configured by the operator.

See [BACKEND_AUDIT.md](BACKEND_AUDIT.md) for resolved reliability work and
remaining validation gaps, and [GETTING_STARTED.md](GETTING_STARTED.md) for
local setup.
