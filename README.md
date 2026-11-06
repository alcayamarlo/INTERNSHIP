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
- Role-based access control, email verification, in-app notifications with
  archive, Trash, and bulk actions, messaging, and protected routes for
  private documents.
- One ongoing OJT per student: a student who already has an open placement
  cannot be accepted by a second company.

Matching is deterministic and rule-based: verified student competencies are
compared with internship requirements and proficiency levels. It is not an
AI/ML model. This project manages internships; it is not a freelance
marketplace.

Notifications are stored in the application as soon as they happen. Their
email copies and live updates are sent by the queue worker, so keep it running
(see [Local setup](#local-setup-windowsxampp)). With Pusher configured,
notifications, chat messages, and the main lists (applicants, placements, OJT
records, applications, notifications) update on screen without a reload. Other
pages show changes when they are opened or refreshed. Email delivery requires
a configured mail transport. The project does not provide SMS delivery.

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

Use the application menu names below as a guide. Role pages are in the left
sidebar (on a phone, open it with the menu button). Three things live in the
top bar instead: **Messages** (chat icon), **Notifications** (bell icon), and
your **account menu** (your name), which holds your profile page (**My
Profile**, **Company Profile**, or **Coordinator Profile**) and **Sign out**.
The application uses separate accounts and permissions; changing the role
requires an administrator and is not a way to switch between dashboards
yourself.

Many lists update on their own while you watch them: when another role saves
something that affects a list you have open, it refreshes in place. If you
are typing or have items selected at that moment, the list is left alone and a
**This page has new updates** bar appears; select **Refresh** when you are
ready.

### Before using the forms

1. Complete the [local setup](#local-setup-windowsxampp), start the app with
   `start-dev.bat`, and sign in at `http://127.0.0.1:8000`.
2. Use an account assigned the role for the task. This project does not define
   a public registration route; an administrator provisions student,
   coordinator, and employer accounts from **Admin → Users → Create User** (or
   imports students). Use a seeded demo account only on a disposable local
   database as described in [Getting Started](GETTING_STARTED.md).
3. On the first sign-in, follow the account setup in this order: add your
   email address, enter the 6-digit code sent to it, then replace the
   temporary password with a new one. Accounts created by an administrator
   already have a verified email and go straight to the new-password step.
   Each step holds you there until it is done. Email codes require a configured mail
   transport; with the default `log` mailer, check `storage/logs/laravel.log`.
   Later sign-ins go straight to the dashboard; you are asked for a code again
   only if you change your email address.
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
| Employer → Student and Coordinator | Application is accepted, creating an OJT placement. A student can have only one ongoing OJT. | Student and employer work in the OJT record; coordinator validates and monitors it. |
| Student → Employer | Attendance entries and weekly journals are submitted. | Employer reviews the work records and provides the requested evaluation. |
| Employer → Coordinator | Approved hours and supervisor evaluation are recorded. | Coordinator reviews the OJT and clears it when all completion requirements are met. |

### Step 1 — Administrator prepares the system and accounts

1. Open **Admin → Institutions** and create or confirm the institution. Verify
   it before assigning it to new student or coordinator accounts.
2. Open **Admin → Academic settings** and confirm the programs and deployment
   requirements students will use. Configure the required internship hours
   there; the default is 300 hours. Program-specific hours and rules can be
   set under **Eligibility Criteria**.
3. Go to **Admin → Users → Create User**. Enter the person's name, email,
   account type, and temporary password. Select the institution for a student
   or coordinator; enter the company name for an employer. For a student,
   enter the student ID because it is used as the login username. Save the
   form and give the user their login instructions securely.
   The role selection controls which role profile fields are shown. Check the
   email address, role, institution/company, and student ID before submitting.
4. After the employer completes its company profile in Step 4, return to
   **Admin → Users**, review the company account, and select **Verify
   company** in its row. Until verification, the employer cannot post
   internships, browse student profiles, or send invitations. **Unverify
   company** reverses it.
5. Use **Admin → Users** to search and filter accounts if you need to confirm
   their role or status. Do not share a temporary password in a public
   channel; tell the user to change it if prompted.

### Step 2 — Student completes the profile and submits evidence

1. Sign in as a student and open **My Profile** from the account menu. If
   shown, agree to the privacy statement before saving profile information.
2. Work through the profile tabs: **Personal**, **Contact**, **Academic**,
   **Skills**, **Experience**, **Portfolio**, **Character Reference**, and
   **Documents**. Complete the required fields and save. Use the profile
   completion indicator to find missing sections. If you change your email
   address on the **Contact** tab, a code is sent to the new address and you
   must enter it before using the rest of the app.
3. Add competencies and their proficiency levels in **My Certificates**. Upload
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
   Use the **Applications** and **OJT Monitoring** pages to monitor progress
   once students begin applying or are accepted.
5. If partner-company documentation needs follow-up, review it under
   **Partner Companies**. Keep the student and employer informed through the system's
   messaging and notifications.
6. Revisit pending work after the student or employer responds. A review is
   complete only when the corresponding status or decision is saved and the
   queue no longer shows that item as awaiting review. Some evidence types
   have different review controls; use the actions displayed for that item.

### Step 4 — Employer completes its profile and posts an internship

1. Sign in as the employer and open **Company Profile** from the account menu.
   Complete the company name,
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
2. Open the opportunity and select **Apply Now** (or **Apply** in the list).
   Complete the application form, including the cover letter if applicable,
   and submit it once.
3. Open **Internships → My Applications** to see the submitted application and
   its current status; it updates on its own when the employer changes it.
   Edit or withdraw it only while the application is in a status that allows
   those actions.
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
   Add interview date and notes when relevant, then select **Update Status**.
3. The student follows the status from **My Applications** and notifications.
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
6. A student can have only one ongoing OJT. If another company has already
   accepted the student, **Accepted** is not offered and a note on the page
   explains why (the other company is not named). You can still mark the
   application **Rejected**. If the arrangement needs to change, the student's
   coordinator handles it.

### Step 7 — Coordinator validates the placement before deployment

1. Open **OJT Monitoring** and select the **To validate** card (or choose
   **To validate** in the status filter). New placements appear here without a
   reload when an employer accepts a student.
2. Open the placement and review the listed validation checks. If requirements
   are still missing, enter remarks and select **Keep pending with remarks** so
   the student and employer know what to address.
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
   **My OJT**. The page has three tabs: **Progress**, **Time Record**, and
   **Weekly Journal**.
2. For each workday, open **Time Record** and select **Add daily entry**.
   Enter the date, time in, time out, break, and work/tasks completed, then
   select **Submit time entry** for supervisor review.
3. On **Weekly Journal**, select **Add weekly journal** to summarize
   accomplishments, learning, and challenges for the week, then select
   **Submit journal**.
4. Use **Progress** to check approved hours against the requirement. Entries
   and journals show their review status, and update on their own when the
   supervisor approves or rejects them. Correct returned items as needed.
5. Enter actual work dates and hours, and submit records regularly so the
   employer can review them. Only approved attendance contributes to the
   approved-hours progress; a submitted or pending entry is not yet a
   confirmed hour.

### Step 9 — Employer supervises the intern and submits an evaluation

1. Open **Interns** and select the placement. Confirm or update the start and
   expected end dates, then select **Save schedule**.
2. In **Daily Time Record**, select **Approve** or **Reject** for each entry
   (a rejection needs a remark), or **Approve all pending**. Read submitted
   weekly journals and select **Acknowledge** with an optional comment. New
   entries from the student appear without a reload.
3. When the coordinator requests a rating, complete the performance
   evaluation form. Rate each criterion from 1 to 5 and add strengths,
   improvement areas, overall comments, and the hiring consideration if
   appropriate. Select **Submit evaluation** (or **Update evaluation** later).
4. Keep reviewing entries until the approved hours meet the required total and
   the evaluation has been submitted. The student can see the OJT progress
   from their own dashboard.
5. If the coordinator returns an evaluation for changes, read the coordinator
   comment, update the evaluation, and save it again. The employer's rating
   supports completion but does not replace the coordinator's clearance.

### Step 10 — Coordinator completes final review and clears the OJT

1. Open **OJT Monitoring**, select the **For clearance** card, and open the
   placement.
2. Confirm that the required hours have been approved and the employer
   evaluation is submitted. Use the separate **Report Reviews** page for any
   weekly journals or evaluations requiring the coordinator's second review.
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

- **Messages** (chat icon in the top bar) can be used to coordinate between
  eligible participants. Conversations are listed on the left and the chat on
  the right. You can edit or unsend your own messages; **unsend** removes it
  for both people, while **remove for you** only clears your own copy.
- **Notifications** (bell icon) show updates and follow-up actions. The bell
  count shows unread items in your inbox. On the **Notifications** page:
  - **Inbox**, **Archived**, and **Trash** tabs show how many items each holds.
  - **Archive** moves an item out of the inbox; **Unarchive** brings it back.
  - **Delete** asks for confirmation and moves the item to **Trash**. From
    Trash you can **Restore** it or **Delete forever** (this cannot be undone).
  - Tick several items (or **Select all on this page**) to mark them read,
    archive, unarchive, delete, or restore them together. **Mark all read**
    and **Archive all read** act on the whole inbox.
  - Archived and deleted items do not appear on dashboards or in the bell.
- Use only files and information that you are authorized to access. Student
  resumes and other protected documents are served through role- and
  ownership-checked pages.
- If an action is unavailable, check the account role, email verification,
  employer verification, current application/placement status, and any
  coordinator review requirements before retrying.
- Read form errors from top to bottom. Required fields are marked in the
  interface; date, file type/size, and allowed status rules can also prevent a
  save. Correct the fields named in the error message and submit again.
- After saving, check for the success message. Lists that other people are
  watching update for them automatically; pages without live updates show the
  change after a refresh. If the new status is not visible, check for a
  validation message instead of assuming that the save succeeded.

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

Set `QUEUE_CONNECTION=database` in `.env`. Then start the web server and the
queue worker together by double-clicking `start-dev.bat`, or by running it from
a terminal in the project folder:

```powershell
.\start-dev.bat
```

It opens two windows: the web server and the queue worker. Keep both open
while you use the app, and close them to stop it. The queue worker sends
notification emails and live updates in the background, so clicks do not wait
for them; without it, they wait in the queue until a worker starts. To start
them by hand instead, use two terminals:

```powershell
php artisan serve
php artisan queue:listen --tries=3 --timeout=120 --sleep=1
```

For frontend development with Vite hot reload, use another terminal:

```powershell
npm run dev
```

Open the app at `http://127.0.0.1:8000`. On Windows, `http://localhost:8000`
also works, but it is noticeably slower because each request tries IPv6 first.

For speed on XAMPP, enable OPcache in `C:\xampp\php\php.ini` (remove the
leading `;` from `zend_extension=opcache` and set `opcache.enable=1`,
`opcache.enable_cli=1`, `opcache.revalidate_freq=0`), then restart the server.
Without it, every request spends about half a second loading PHP files.

## Email verification

There is no public registration. Accounts created by an administrator are
marked as email-verified. Imported students start without an email: at their
first sign-in they add one, enter the 6-digit code sent to it, and then set a
new password. After that, signing in goes straight to the dashboard.

A student who changes their email address (on the profile **Contact** tab, or
when an administrator edits it) must verify the new address: a code is sent to
it and other pages stay locked until it is entered. Saving the same address
again does not ask for a code.

The code expires after 10 minutes; it is stored hashed, and verification is
locked after five incorrect submissions. The verification page can request
another code subject to resend throttling. Configure a working `MAIL_*`
transport in `.env` to deliver messages. The example environment uses the
`log` mailer, which writes mail content to the Laravel log instead of sending
it to an inbox.

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
- The `2026_11_06_000005` migration adds the one-OJT-per-student rule and
  stops if any student already has more than one open placement. If it
  reports student IDs, back up the database, run
  `database/sql/dedupe_open_placements.sql` one step at a time (read its notes
  first; it soft-deletes the extra placements and keeps a backup table), then
  migrate again.
- Run `php artisan db:health` after migrating (safe, read-only) to catch a
  table whose `id` column lost its `AUTO_INCREMENT` -- this happened on this
  project's own database from what looked like a SQL dump/restore, and every
  insert into an affected table fails until it's fixed. The command prints
  the exact `ALTER TABLE` statement for anything it finds.
- Mount a volume at `/app/storage` or use the `s3_*` disks above. Without
  `storage:link`, profile pictures are still served by `routes/public-files.php`.
- Set `QUEUE_CONNECTION=database` and run a queue worker
  (`php artisan queue:work`) as its own process. In-app notifications are
  saved without it, but notification emails, announcement emails, and live
  updates are sent by the worker.
- `/health` reports database, cache and queue status.

Generate `APP_KEY` once for a new installation and preserve it across
deployments; changing it can invalidate encrypted data and sessions. Build
frontend assets with `npm ci` and `npm run build` as part of deployment:
`public/build` is git-ignored, so make sure the generated directory is included
in the release. See [COMMANDS_REFERENCE.md](COMMANDS_REFERENCE.md#-production-deployment)
for the deployment checklist.
