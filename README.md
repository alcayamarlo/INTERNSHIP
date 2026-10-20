# Skill-Bridge

Skill-Bridge is a Laravel web application for managing student competencies,
internship opportunities, applications, and placement reporting. It supports
four account roles: student, employer, institution coordinator, and
administrator.

## What the application provides

- Student profiles, competency and evidence records, portfolios, and PDF resume
  generation.
- Employer profiles and internship opportunity create, edit, close, and delete
  workflows.
- Student internship browsing, rule-based competency matching, applications,
  and application status history.
- Coordinator evidence review and report generation, plus administrator user
  management, announcements, and reports.
- Role-based access control, email verification, queued in-app notifications,
  and protected routes for private documents.

Matching is deterministic and rule-based: verified student competencies are
compared with internship requirements and proficiency levels. It is not an
AI/ML model. This project manages internships; it is not a freelance
marketplace.

Notifications are stored in the application and some notification work is
queued. Email delivery requires a configured mail transport. The project does
not provide SMS delivery or promise real-time delivery.

## Requirements

- PHP 8.2 or later with the PHP extensions required by Laravel 12.
- Composer.
- MySQL/MariaDB or another database supported by Laravel and the configured
  migrations.
- Node.js and npm for compiling frontend assets.

## Local setup (Windows/XAMPP)

Run these commands from the repository root in PowerShell. Create an empty
database first, then configure its connection in `.env`.

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Set `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, and
`DB_PASSWORD` in `.env`. The example environment defaults to a MySQL database
named `nexus`; change it if needed. For a fresh database, run:

```powershell
php artisan migrate --seed
npm install
npm run build
php artisan storage:link
```

The seeder creates demo users and sample application records. Do not run
`migrate:fresh` against a database containing data you need to keep.

Start the web application and the database-backed queue worker in separate
terminals:

```powershell
php artisan serve
php artisan queue:work
```

For frontend development with Vite hot reload, use a third terminal:

```powershell
npm run dev
```

The app is normally available at `http://localhost:8000`.

## Email verification

New registrations are student accounts and are not considered verified until
they submit the emailed 6-digit code. The code expires after 10 minutes; it is
stored hashed, and verification is locked after five incorrect submissions.
The verification page can request another code subject to resend throttling.
Configure a working `MAIL_*` transport in `.env` to deliver messages. The
example environment uses the `log` mailer, which writes mail content to the
Laravel log instead of sending it to an inbox.

## Tests

Run the Pest feature and unit tests with:

```powershell
php artisan test
```

## Documentation

- [Getting started](GETTING_STARTED.md): setup and first-use walkthrough.
- [Project summary](PROJECT_SUMMARY.md): implemented scope and known limits.
- [Backend audit](BACKEND_AUDIT.md): resolved work and remaining coverage
  limitations.
- [Documentation index](DOCUMENTATION_INDEX.md): available project docs.
- [Command reference](COMMANDS_REFERENCE.md): additional Laravel commands.

## Security and deployment notes

Set `APP_ENV=production`, disable debug mode, use a secure application key,
configure production mail and queue workers, and restrict access to the
database and private storage before deployment. Keep uploaded resumes,
certificates, and portfolios on the private disk and serve them through the
authorized application routes. Do not publish `.env`, credentials, or private
backups.

Profile images and uploaded documents default to the local `public` and
`private` disks. On Railway, local files are ephemeral unless a persistent
volume is mounted at Laravel's storage directory. Alternatively, configure
object storage with `PUBLIC_UPLOAD_DISK=s3_public` and
`PRIVATE_UPLOAD_DISK=s3_private`, plus the `AWS_*` credentials and bucket
settings. Keep private objects private; the app serves them through authorized
routes. Existing files are not copied automatically when changing disks.

Railway checklist:

- Variables: `APP_KEY`, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`
  (the public https URL), `DB_*`, `MAIL_*` (or Resend), and
  `LOG_STACK=single,stderr` so stack traces show in the deploy logs.
- Run `php artisan migrate --force` on every deploy (the 2026-10-20
  migrations normalize emails, archive previously deleted users, and verify
  admin-created accounts).
- Mount a volume at `/app/storage` or use the `s3_*` disks above. Without
  `storage:link`, profile pictures are still served by `routes/public-files.php`.
- In-app notifications no longer need a queue worker; only notification
  emails are queued, so run `php artisan queue:work` if you want them sent.
- `/health` reports database, cache and queue status.

Generate `APP_KEY` once for a new installation and preserve it across
deployments; changing it can invalidate encrypted data and sessions. Build
frontend assets with `npm ci` and `npm run build` as part of deployment:
`public/build` is git-ignored, so make sure the generated directory is included
in the release. See [COMMANDS_REFERENCE.md](COMMANDS_REFERENCE.md#-production-deployment)
for the deployment checklist.
