# Getting Started

This guide covers a local Skill-Bridge installation and a first-use walkthrough.
For all available setup and verification commands, see [README.md](README.md)
and [COMMANDS_REFERENCE.md](COMMANDS_REFERENCE.md).

## 1. Prerequisites

Install:

- PHP 8.2+ and Composer.
- MySQL/MariaDB (XAMPP is suitable for local development).
- Node.js and npm.

From PowerShell, change directory to the project root:

```powershell
Set-Location C:\xampp\htdocs\INTERNSHIP
```

## 2. Install and configure

Create an empty database in MySQL, then install the PHP dependencies and
prepare the environment file:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Edit `.env` and set the database credentials. The checked-in example uses
`DB_DATABASE=nexus`, `DB_USERNAME=root`, and an empty password; use values
appropriate for your local database.

Run migrations and (optionally) demo seed data, then build the frontend:

```powershell
php artisan migrate --seed
npm install
npm run build
php artisan storage:link
```

The seeder inserts sample users, profiles, competencies, and internship
records. It creates private placeholder PDFs for its file-backed portfolio
examples. Seeded demo credentials use the password `password`; use only on a
disposable local database and change them immediately if exposed.

## 3. Start the application and queue worker

Run each command in a separate terminal:

```powershell
php artisan serve
php artisan queue:work
```

If you are changing frontend assets, also run:

```powershell
npm run dev
```

The web application is normally available at `http://localhost:8000`.

The example `.env` uses the database queue connection. Keep a queue worker
running to process queued notification jobs. In production, configure a
managed worker and restart it after deployments.

## 4. Configure email

The example mailer is `log`, so messages are written to the application log
instead of delivered to a mailbox. To receive verification codes and other
email, set a working `MAIL_MAILER` and the corresponding mail host, port,
credentials, and sender values in `.env`.

After a new student registers:

1. Skill-Bridge emails a six-digit verification code.
2. Enter the code on the verification page within 10 minutes.
3. If needed, use **Resend code**; resend requests are throttled.
4. Five incorrect code submissions invalidate the current code.

The stored code is hashed. Registration and code delivery are separate steps;
do not assume the account is verified until the code is accepted.

## 5. Try the role workflows

The database seeder creates administrator, coordinator, employer, and student
demo users. Their local-only password is `password`. Use a configured email
transport or the verification-page resend action for seeded accounts that are
not verified.

- **Student:** complete the profile, add competencies/evidence, browse
  internships, view rule-based matches, and apply.
- **Employer:** create an internship opportunity, review applicants, and
  update application statuses through allowed transitions.
- **Coordinator:** review student evidence and generate reports.
- **Administrator:** manage users and announcements and generate system
  reports.

Role-specific pages are protected by role middleware. Accounts with an
incomplete role profile are directed to profile completion.

## 6. Run tests

```powershell
php artisan test
```

Tests use the test environment and are separate from the local database.
Avoid pointing test configuration at any database with data you need.

## Troubleshooting

- **Database connection error:** confirm the MySQL service is running and the
  `.env` database values are correct; then run `php artisan config:clear`.
- **Missing application encryption key:** on a new installation only, run
  `php artisan key:generate`. Preserve the generated key for all later
  deployments; do not regenerate it for an existing installation.
- **No email arrives:** check the configured mail transport. With the default
  `log` mailer, inspect `storage/logs/laravel.log`.
- **Queued notification is pending:** start `php artisan queue:work` and check
  the configured queue backend.
- **Frontend assets are missing:** run `npm ci` and `npm run build`. Ensure
  `public/build` is included in the deployed release; it is git-ignored.
- **Private file not found:** confirm the database reference points to an
  existing file on the private disk. Legacy migration can be checked with
  `php artisan files:secure-existing`; this command reports missing
  references and does not delete public copies unless explicitly invoked with
  `--remove-public`.
