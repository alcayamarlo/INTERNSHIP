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

## Role-based tutorial: from account setup to OJT completion

This walkthrough follows one internship from setup through the student's
certificate of completion. The four roles share the same records, but each
role has its own dashboard and actions:

| Role | Main responsibility in the workflow |
| --- | --- |
| Administrator | Prepare institutions and academic catalogs, create accounts, verify employers, and manage system settings. |
| Institution coordinator | Review student information and evidence, monitor placements, validate deployments, and clear completed OJTs. |
| Employer | Complete and verify the company profile, post internships, review applications, and supervise interns. |
| Student | Complete a profile, submit evidence and applications, then record OJT attendance and journals. |

### How to follow this guide

The steps below describe one example internship from beginning to end. In a
real deployment, account setup, profile preparation, and company verification
can happen at the same time. The important rule is that each person signs in
with their own account and performs only their role's part:

1. The administrator prepares the institution and creates the accounts.
2. The student and employer complete their profiles. The administrator
   verifies the company before it can post internships.
3. The coordinator reviews student information and later validates the
   placement. The employer owns the internship and application decisions.
4. The student and employer share the accepted placement's OJT record. The
   coordinator performs the final institutional clearance.

Saving a form records that role's action; it does not automatically complete
the next role's work. After saving, check the success or validation message,
then tell the next role what is ready for review. If a button or form is
missing, confirm that you are signed in with the right role and that the
account, company, application, or placement has reached the state required
for that action.

Use the application menu names below as a guide. Menu layout can vary by
screen size, but role pages are also available from that role's dashboard.
The application uses separate accounts and permissions; changing the role
requires an administrator and is not a way to switch between dashboards
yourself.

### Before using the forms

1. Complete the [local setup](#local-setup-windowsxampp) and sign in at
   `http://localhost:8000`.
2. Use an account assigned the role for the task. This project does not define
   a public registration route; an administrator provisions student,
   coordinator, and employer accounts from **Admin → Users → Create User** (or
   imports students). Use a seeded demo account only on a disposable local
   database as described in [Getting Started](GETTING_STARTED.md).
3. If prompted, change the temporary password and verify the account's email.
   Email codes require a configured mail transport; with the default `log`
   mailer, check `storage/logs/laravel.log`.
4. Finish the role profile if the system sends you to profile completion.
   Forms show validation messages when a required value is missing or invalid.
5. To practice all hand-offs, use one test account per role (the demo users
   created by the seeder are described in [Getting Started](GETTING_STARTED.md)).
   Avoid testing with real personal information or real documents in a
   development database.

### What each role hands to the next

| Handoff | What must be ready | What the next role does |
| --- | --- | --- |
| Administrator → Student | Student account has the correct institution and student ID. | Student signs in, completes the profile, and submits information requested by the institution. |
| Administrator → Coordinator | Coordinator account is linked to the correct verified institution. | Coordinator checks students and institutional placement requirements. |
| Employer → Administrator | Company profile is saved with accurate company information. | Administrator reviews the company account and verifies it. |
| Employer → Student | Internship is saved as open and describes programs, duties, and skill requirements. | Student reviews the listing and applies if it is suitable. |
| Student → Employer | Application is submitted with the requested cover letter/details. | Employer reviews the application and records the next decision. |
| Employer → Student and Coordinator | Application is accepted, creating an OJT placement. | Student and employer work in the OJT record; coordinator validates and monitors it. |
| Student → Employer | Attendance entries and weekly journals are submitted. | Employer reviews the work records and provides the requested evaluation. |
| Employer → Coordinator | Approved hours and supervisor evaluation are recorded. | Coordinator reviews the OJT and clears it when all completion requirements are met. |

### Step 1 — Administrator prepares the system and accounts

1. Open **Admin → Institutions** and create or confirm the institution. Verify
   it before assigning it to new student or coordinator accounts.
2. Open **Admin → Academics** and confirm the programs and deployment
   requirements students will use. Configure the required internship hours
   there; the default is 300 hours.
3. Go to **Admin → Users → Create User**. Enter the person's name, email,
   account type, and temporary password. Select the institution for a student
   or coordinator; enter the company name for an employer. For a student,
   enter the student ID because it is used as the login username. Save the
   form and give the user their login instructions securely.
   The role selection controls which role profile fields are shown. Check the
   email address, role, institution/company, and student ID before submitting.
4. After the employer completes its company profile in Step 4, return to
   **Admin → Users**, review the company account, and use the company
   verification action. Until verification, the employer cannot post
   internships, browse student profiles, or send invitations.
5. Use **Admin → Users** to search and filter accounts if you need to confirm
   their role or status. Do not share a temporary password in a public
   channel; tell the user to change it if prompted.

### Step 2 — Student completes the profile and submits evidence

1. Sign in as a student and open **Profile**. If shown, agree to the privacy
   statement before saving profile information.
2. Work through the profile tabs: **Personal**, **Contact**, **Academic**,
   **Skills**, **Experience**, **Portfolio**, **Character Reference**, and
   **Documents**. Complete the required fields and save. Use the profile
   completion indicator to find missing sections.
3. Add competencies and their proficiency levels in **Competencies**. Upload
   supporting evidence where requested. Add portfolio items, certificates, or
   other deployment documents from the corresponding profile sections.
4. Generate a resume from **Resume** after the profile has enough information.
   Keep documents accurate and current; coordinators review submissions, and
   employers only see information permitted by the system.
5. If the student profile or evidence needs institutional review, the
   coordinator handles it in the next step. Check the dashboard and
   notifications for review results or requested changes.
6. If profile completion remains below 100%, open the profile completion
   indicator and follow its missing-section list. A complete profile and
   reviewed evidence are different things: finishing the form does not mean
   the coordinator has verified its contents.

### Step 3 — Coordinator reviews student information

1. Sign in as the institution coordinator and open **Students** or
   **Verification**.
2. Find the student using the search and program filters. Open the student
   record to review profile details and submitted files.
3. Review the available evidence and record the appropriate verification
   decision. If rejecting or requesting a correction, include a clear reason
   when the form asks for one. The student can then correct the profile or
   replace the submission and check the result.
4. Review the student's academic readiness and eligibility where applicable.
   Use the **Applications** and **Placements** pages to monitor progress once
   students begin applying or are accepted.
5. If partner-company documentation needs follow-up, review it under
   **Companies**. Keep the student and employer informed through the system's
   messaging and notifications.
6. Revisit pending work after the student or employer responds. A review is
   complete only when the corresponding status or decision is saved and the
   queue no longer shows that item as awaiting review. Some evidence types
   have different review controls; use the actions displayed for that item.

### Step 4 — Employer completes its profile and posts an internship

1. Sign in as the employer and open **Profile**. Complete the company name,
   industry, contact details, and other relevant company information, then
   save. The profile indicates whether admin verification is still pending.
   Ask the administrator to review and verify the company account.
2. After verification, go to **Internships → Create**. Complete the form:
   title, open/closed status, work setup, eligible programs, location,
   duration, allowance, description, responsibilities, and general
   requirements.
3. Add competency requirements and choose a proficiency level for each one.
   Set any additional applicant requirements offered in the form, then save
   the opportunity as **Open** to publish it.
   Choose requirements that genuinely describe the work; these are also used
   by the rule-based competency matching shown to students. Select the
   intended programs, or leave them unselected if the opportunity is open to
   all programs.
4. Review the public listing and keep its description, dates, status, and
   competency requirements up to date. Close the listing when applications
   should no longer be accepted.
5. If the internship form reports validation errors, correct the highlighted
   fields and save again. Confirm the saved opportunity appears in the
   internship list with the intended open status before asking students to
   apply.

### Step 5 — Student finds an opportunity and submits an application

1. As a student, open **Internships** and browse or search open opportunities.
   Review the description, work setup, location, program eligibility,
   requirements, and competencies before applying. Recommendations are
   rule-based matches, not AI decisions.
2. Open the opportunity and select **Apply**. Complete the application form,
   including the cover letter if applicable, and submit it once.
3. Open **Applications** to see the submitted application and its current
   status. Edit or withdraw it only while the application is in a status that
   allows those actions.
4. The coordinator can monitor applications and placements, but the employer
   is responsible for reviewing and updating each application decision.
5. Apply only once per opportunity. If the application form returns an error,
   read the field-level message and correct that information rather than
   repeatedly resubmitting. A successful application should appear in **My
   Applications** with its status and internship details.

### Step 6 — Employer reviews applicants and records a decision

1. Open **Applicants** and select an application. Review the student's profile
   and permitted supporting documents.
2. Use the status form to choose one of the currently available next statuses.
   The workflow can include **Reviewed**, **Interview**, **Accepted**, or
   **Rejected**; available choices depend on the application's current status.
   Add interview date and notes when relevant, then save.
3. The student follows the status from **Applications** and notifications.
   When the employer accepts the application, the system creates its OJT
   placement record; the student and coordinator can then follow that record.
4. Status choices are intentionally limited to valid next steps. For example,
   an application may move from **Applied** to **Reviewed**, **Interview**,
   **Rejected**, or **Withdrawn**; a **Reviewed** application may proceed to
   **Interview**, **Accepted**, **Rejected**, or **Withdrawn**. The form only
   offers actions permitted from the current status. Do not try to skip
   directly to **Completed**; completion is recorded through the OJT process.
5. Before accepting, confirm the student and internship are correct.
   Acceptance starts a placement, so the resulting OJT needs its own schedule,
   deployment validation, attendance, and review.

### Step 7 — Coordinator validates the placement before deployment

1. Open **Coordinator → Placements** and filter for placements awaiting
   validation.
2. Open the placement and review the listed validation checks. If requirements
   are still missing, keep the placement pending and enter remarks so the
   student and employer know what to address.
3. When checks pass, select **Validate placement**. If the form presents
   warnings, review and acknowledge them before proceeding.
4. The coordinator can monitor ongoing hours, flag a placement for follow-up,
   and request the employer's performance rating as needed.
5. If a required check is not satisfied, do not treat the placement as ready
   just because an application was accepted. Keep it pending with clear
   remarks, coordinate the missing action, and validate after the record is
   corrected.

### Step 8 — Student records OJT attendance and weekly journals

1. Once the placement is active and its start date has arrived, open
   **Student → OJT**.
2. For each workday, use **Add daily entry** to enter the date, time in, time
   out, break, and work/tasks completed. Submit the entry for supervisor review.
3. Use **Add weekly journal** to summarize accomplishments, learning, and
   challenges for the week, then submit it.
4. Return to the OJT page to check progress toward the required hours and see
   which entries or journals are awaiting review. Correct returned items as
   needed.
5. Enter actual work dates and hours, and submit records regularly so the
   employer can review them. Only approved attendance contributes to the
   approved-hours progress; a submitted or pending entry is not yet a
   confirmed hour.

### Step 9 — Employer supervises the intern and submits an evaluation

1. Open **Employer → Interns** and select the placement. Confirm or update the
   start and expected end dates in the schedule form.
2. Review the student's daily attendance and approve or reject entries. Read
   submitted weekly journals and record the supervisor review.
3. When the coordinator requests a rating, complete the performance
   evaluation form. Rate each criterion from 1 to 5 and add strengths,
   improvement areas, overall comments, and the hiring consideration if
   appropriate. Submit or update the evaluation.
4. Keep reviewing entries until the approved hours meet the required total and
   the evaluation has been submitted. The student can see the OJT progress
   from their own dashboard.
5. If the coordinator returns an evaluation for changes, read the coordinator
   comment, update the evaluation, and save it again. The employer's rating
   supports completion but does not replace the coordinator's clearance.

### Step 10 — Coordinator completes final review and clears the OJT

1. Open **Coordinator → Placements** and select the placement marked for
   clearance.
2. Confirm that the required hours have been approved and the employer
   evaluation is submitted. Use the separate **Reviews** page for any weekly
   journals or evaluations requiring the coordinator's second review.
3. If the record is complete, choose **Clear and issue certificate**. Add
   optional clearance remarks and confirm the action. Clearing the OJT issues
   the certificate of completion.
4. The student can download the certificate from the OJT record. The employer
   and coordinator can continue to view the completed placement; application
   completion is recorded by the OJT workflow rather than by manually setting
   an application to completed.
5. If **Clear and issue certificate** is unavailable, inspect the placement
   progress and evaluation first. The approved hours must reach the configured
   requirement, and the employer evaluation and any required coordinator
   review must be complete.

### Shared forms and follow-up

- **Messages** can be used to coordinate between eligible participants.
- **Notifications** and application/OJT status pages show updates and
  follow-up actions.
- Use only files and information that you are authorized to access. Student
  resumes and other protected documents are served through role- and
  ownership-checked pages.
- If an action is unavailable, check the account role, email verification,
  employer verification, current application/placement status, and any
  coordinator review requirements before retrying.
- Read form errors from top to bottom. Required fields are marked in the
  interface; date, file type/size, and allowed status rules can also prevent a
  save. Correct the fields named in the error message and submit again.
- Refresh the relevant list or record after saving to confirm the new status
  is visible. If it is not, check for a validation message instead of assuming
  that the save succeeded.

### Quick completion checklist

- **Administrator:** institution and academic data are ready; each account
  has the right role; the employer company is verified.
- **Student:** profile information is saved; requested competencies and
  documents are submitted; the application appears in **My Applications**.
- **Employer:** internship is open; applicants have a saved decision; OJT
  attendance and journals are reviewed; evaluation is submitted.
- **Coordinator:** student submissions are reviewed; placement is validated;
  final hours and evaluation are checked; OJT is cleared and the certificate
  is issued.

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
- [Role-based tutorial](README.md#role-based-tutorial-from-account-setup-to-ojt-completion):
  end-to-end steps connecting administrators, coordinators, employers, and
  students.
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
  (the public https URL), `DB_*`, `MAIL_MAILER=resend` + `RESEND_API_KEY` +
  `MAIL_FROM_ADDRESS` (a verified Resend domain -- do not use a personal
  Gmail/SMTP account in production), and `LOG_STACK=single,stderr` so stack
  traces show in the deploy logs.
- For live notifications and messaging: `BROADCAST_CONNECTION=pusher` plus
  `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET`, `PUSHER_APP_CLUSTER`,
  and the matching `VITE_PUSHER_APP_KEY`/`VITE_PUSHER_APP_CLUSTER` (these two
  must also be set wherever `npm run build` runs during deploy, since Vite
  bakes them into the compiled JS at build time, not at runtime). Leaving
  `BROADCAST_CONNECTION=log` is safe and simply disables live updates.
- Run `php artisan migrate --force` on every deploy (the 2026-10-20
  migrations normalize emails, archive previously deleted users, and verify
  admin-created accounts).
- Run `php artisan db:health` after migrating (safe, read-only) to catch a
  table whose `id` column lost its `AUTO_INCREMENT` -- this happened on this
  project's own database from what looked like a SQL dump/restore, and every
  insert into an affected table fails until it's fixed. The command prints
  the exact `ALTER TABLE` statement for anything it finds.
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
