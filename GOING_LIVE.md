# Going live checklist

Do every step before real students use Skill-Bridge on a server. The local setup (XAMPP, `APP_DEBUG=true`,
demo accounts) is for development and presentations only.

## 1. Environment (`.env` on the server)

| Setting | Value | Why |
|---|---|---|
| `APP_ENV` | `production` | Turns off development-only behaviour; the demo seeders refuse to run. |
| `APP_DEBUG` | `false` | With `true`, an error page shows code, file paths and database details to anyone. |
| `APP_URL` | `https://your-domain` | Links in emails and PDFs use HTTPS. |
| `SESSION_SECURE_COOKIE` | `true` | The sign-in cookie is only sent over HTTPS. |
| `SESSION_ENCRYPT` | `true` | Session data stored in the database is encrypted. |
| `CSP_ENFORCE` | `true` | Only after the browser console shows no CSP messages on each role's main pages (see section 5). |
| `BACKUP_PASSWORD` | 48 random characters | Encrypts every backup (`php artisan backup:run`). |
| `BACKUP_COPY_PATH` | a folder on another drive or a synced cloud folder | A second copy of every backup, off this disk. |
| `IP_HASH_KEY` | its own random value, not `APP_KEY` | IP addresses are stored only as keyed hashes. |
| `MAIL_*` | the school's mail account | Password links and notices must arrive. |

## 2. HTTPS

- Serve the site only over HTTPS with a valid certificate (for example Let's Encrypt), and redirect HTTP to HTTPS
  in the web server.
- Once HTTPS works, the app sends `Strict-Transport-Security` by itself, and browsers will then refuse plain HTTP
  for a year. Do not turn HTTPS off afterwards.

## 3. Two-factor sign-in

- **Admins always use it.** After the password, a 6-digit code is emailed to the admin's address (valid 10 minutes, 5 tries).
  Coordinators, HR and supervisors can switch it on under *Your account menu → Two-factor sign-in* (a reminder banner
  invites them); students never use it.
- **Every admin must have a real, verified email address**, and the server's `MAIL_*` settings must work, or codes cannot
  be delivered. Codes are never emailed to seeded demo accounts (`users.demo_account`) or to `.test`, `.example`,
  `example.com` addresses.
- **It is skipped when `APP_ENV=local`** (`TWO_FACTOR_SKIP_LOCAL=true`), so demos on a development machine are never
  blocked. On a real server `APP_ENV` is `production`, so the step is on. Check once by signing in as an admin from a
  private window.
- **Recovery codes.** Each admin makes 8 on first sign-in (shown once; download or print) and keeps them away from the
  server. A recovery code works in place of the emailed code, once each.
- **If email is down:** use a recovery code. **If an admin has lost their codes too:** on the server run
  `php artisan user:reset-2fa admin@school.edu.ph`; their next password sign-in goes through once and asks them to make
  new recovery codes.
- Known limitation: while an account is locked after repeated wrong passwords, the sign-in page says it is locked,
  which confirms that the account exists.

## 4. Keys and passwords: keep a copy away from the server

Students' personal data is stored **encrypted** in the database with `APP_KEY`: date and place of birth, home and
provincial address, city, province, zip code, emergency contact (name, relationship, address, phone), religion, civil
status, every user's phone number, and character references' phone numbers (the list is `App\Support\SensitiveColumns`).
A database dump or a backup shows only unreadable text for these. The cost: **the data is only as recoverable as the
key.** Backups hold the encrypted values, so a backup can be restored and read only together with the `APP_KEY` that
encrypted them.

Store these **together**, in a password manager or a sealed envelope held by the school, **not** only on the server:

- `APP_KEY` (the whole line, including `base64:`): without it these columns, in the database and in every backup, can
  never be read again. A restored backup opened with a different key shows unreadable values (tested).
  **Never run `php artisan key:generate` on a system that has data.**
- `BACKUP_PASSWORD`: without it no backup can be opened.
- `APP_PREVIOUS_KEYS`, if you ever rotated the key (see below).
- The database password and the admin account's password.

The backup password and the key protect two different things (the archive, and the data inside it) and a restore needs
both, so keep them in the same safe place and update that place whenever either changes.

Test it once: on another computer, open a backup with `php artisan backup:verify <file>` using only the saved values,
restore its `database.sql` into an empty database, and run `php artisan data:reencrypt --check` there: it must report
"All personal data is encrypted and readable with this APP_KEY."

### Check that nothing is stored in plain text

`php artisan data:reencrypt --check` counts, per column, the values that are encrypted, in plain text, or unreadable with
the current key. It changes nothing and exits with an error if any value is plain or unreadable. Run it after a restore,
after importing data, and after each deploy that touches students' data.

### Rotating the key (only if the key may have leaked)

1. Take an encrypted backup (`php artisan backup:run`) and keep the **old** `APP_KEY`.
2. Generate a new key (`php artisan key:generate --show`), set it as `APP_KEY` in `.env`, and put the old key in
   `APP_PREVIOUS_KEYS` (comma-separated if there are several). Laravel now reads values made with either key.
3. `php artisan config:clear`, then `php artisan data:reencrypt`: it rewrites every value with the new key.
4. `php artisan data:reencrypt --check` must report everything encrypted and readable. Also: all sessions are signed
   out, and password-reset codes sent before the change stop working.
5. Only then remove `APP_PREVIOUS_KEYS`. Old backups still need the old key: keep it with them, labelled with the
   date range they cover.

### Developers and shared databases

A copy of a database (or a backup) only works with the `APP_KEY` of the machine that wrote it. A teammate who pulls this
change must run `php artisan migrate` (it encrypts their own rows with their own key). Do not copy a database between
machines with different keys without also copying the key.

## 5. Before switching the Content Security Policy to enforce

With `CSP_ENFORCE=false` the browser only reports. Sign in as each role (admin, coordinator, HR, supervisor,
student), open the main pages, and check the browser console (F12) for messages starting with
"[Report Only] Refused to load" or "violates the following Content Security Policy directive". When there are none,
set `CSP_ENFORCE=true`, run `php artisan config:cache`, and check the same pages once more.

## 6. Accounts and data

- Do not run the demo seeders (`PresentationDataSeeder`) on the server. Remove any demo accounts if the database was
  copied from a demo machine.
- Give the admin account a long unique password.
- Real certificates, IDs and resumes go on the private disk only (the app's upload forms already do this).

## 7. Server and packages

- `php artisan config:cache`, `route:cache`, `view:cache` after each deploy.
- The scheduler (`php artisan schedule:run` every minute) and a queue worker must be running; the nightly backup
  depends on them.
- Run `composer audit` and `npm audit` before each release, and review the Dependabot pull requests on GitHub.
- The web server's document root is the `public` folder, never the project folder (that would expose `.env`).
