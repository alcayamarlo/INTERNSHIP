# SkillBridge User Tutorial

SkillBridge is the internship and OJT (On-the-Job Training) system of St. Cecilia's College. It takes a student from building a profile, to applying for an internship, to finishing the OJT with a final evaluation and a certificate.

This guide follows the real order of work:

1. **Admin** sets up the school year and the accounts.
2. **Coordinator** brings in companies, checks students, and watches each OJT.
3. **Company (Employer)** posts internships, accepts students, and supervises interns.
4. **Student** completes a profile, applies, and records the OJT.
5. **Final evaluation**: how it is submitted, how it is computed, and where to see it.

Every button and menu name below is written exactly as it appears on screen, in **bold**.

---

## Quick start

### Admin
1. Sign in, then open **Academic settings** and check the **Programs**, **Deployment requirements** and **Calendar and student ID** tabs.
2. Open **Users** → **Import Students** and upload the registrar CSV file.
3. Open **Users** → **Create User** for each coordinator.
4. Use **Announcements** → **New Announcement** to tell users what to do next.

### Coordinator
1. Open **Partner Companies** → **Invite a company** for each company without an account.
2. Open **Students** → **View profile** → tick **Student has passed all major subjects** → **Save academic progress**.
3. When a company accepts a student, open **OJT Monitoring** → **View** → **Validate placement**.
4. On the same page, use **Request a monthly evaluation** each month and **Request final evaluation** near the end.

### Company (Employer)
1. Open the invitation email and click the link, then **Create company account**. Or sign in with the account the admin gave you.
2. Open **Company Profile** and fill in your job title.
3. Open **Internships** → **Post Internship**.
4. Open **Applicants** → **Review** → set the status to **Reviewed**, then **Accepted** → **Update Status**.
5. Open **Interns** → **Open** to approve time records, certify weekly reports, and submit evaluations.

### Student
1. Sign in with your **School ID** and your birthdate (MMDDYYYY) as the password. Add your Gmail, enter the 6-digit code, and set a new password.
2. Open **My Profile**, tick the privacy box, click **I agree**, and fill in every tab.
3. Open **Resume** → **Generate New Resume**.
4. Open **Internships** → **Apply** → choose your resume → **Submit application**.
5. After your coordinator validates your OJT, use **My OJT** every day and every week.

---

## Before you start: signing in and moving around

### Signing in
1. Go to the SkillBridge home page and click **Sign in**.
2. In **Email or School ID**, type your email (staff and companies) or your School ID (students).
3. Type your **Password** and click **Sign in**.

**Expected result:** your own dashboard opens (Admin, Coordinator, Employer or Student Dashboard).

If you forgot your password, click **Forgot password?** in the sign-in window.

### The screen layout
- **Left sidebar:** the menu for your role. Click the menu icon (**Collapse navigation**) to hide or show it.
- **Top bar, chat icon:** **Messages**.
- **Top bar, bell icon:** **Notifications**. Unread items show a number.
- **Top bar, your name:** the account menu, with your profile, settings and **Sign out**.

### Passwords
New passwords must be 8 to 64 characters long and include upper- and lowercase letters, a number and a symbol.

---

## Part 1 — Admin

The admin sets up the school year rules and creates accounts. The admin sidebar has:

- **Overview:** **Dashboard**.
- **Management:** **Academic settings**, **Eligibility Criteria**, **Users**, **Company Invitations**, **OJT Placements**, **Announcements**, **Skill Categories** and **Student Skill Tags**.
- **System:** **Reported Content**, **Audit & Security Log**, **Usage & Health** and **Reports**.

### 1.1 Check the academic settings
1. Click **Academic settings**. The page has three tabs, and an **Add program** button at the top.
2. **Programs** tab: check each program's **Required hours** (for example, 486) and its **Assigned coordinator**.
   - To change a program, click **Edit**. The **Edit program** page has **Program name**, **Program code** (letters and numbers only), **Required internship hours**, **Hours confirmed by the school**, **Assigned coordinator** and **Majors**.
   - Click **Save program**.
3. **Deployment requirements** tab: see the documents students must submit.
   - To change a row, edit it and click **Save** on that row. **Delete** appears only for documents nobody has uploaded yet.
   - To add one, fill in **Document name** and **Uploaded by**, then click **Add requirement**.
4. **Calendar and student ID** tab: check these settings.
   - **Academic year start month**, the term date ranges, and **Enable Summer term**.
   - **Monthly evaluation periods**: the label each month gets on the monthly evaluation, such as Prelim or Midterm.
   - **OJT departments (Designation)**: one department per line. Students and companies pick from this list, and it prints as "Designation" on the forms.
   - **Weekly report due (days after the week ends)**: the default is 3.
   - Internship eligibility: **Require 4th-year standing** and **Require all major subjects passed**.
   - **Student ID format** and **Required OJT hours**. Required OJT hours is only a fallback for students whose program has no required hours.
5. Click **Save academic settings**.

**Expected result:** "Academic settings saved."

### 1.2 Import students from the registrar file
1. Click **Users** → **Import Students**.
2. Prepare a CSV file. The first row must contain these column names:
   ```
   school_id, last_name, first_name, middle_name, date_of_birth, program, year_level, section, school_year, semester
   ```
   - Required: `school_id`, `last_name`, `first_name` and `date_of_birth`.
   - Dates: `YYYY-MM-DD` or `MM/DD/YYYY`.
   - `school_year`: like `2026-2027`.
   - `semester`: `1st Semester`, `2nd Semester` or `Summer`.
3. Choose the **Institution**, choose the **CSV File**, and click **Import Students**.

**Expected result:** the **Import Results** page shows how many rows were **Created**, **Updated** and in **Errors**, with a message for each row.

- Each new student's first password is their birthdate in `MMDDYYYY` format (May 14, 2003 becomes `05142003`).
- Uploading the same School ID again updates that student. It does not create a duplicate.

### 1.3 Create a coordinator or company account
1. Click **Users** → **Create User**.
2. Fill in **Full Name**, **Email Address** and **Account Type** (**Student**, **Coordinator** or **Employer**).
3. Fill in the extra fields that appear:
   - Coordinator: **Institution**.
   - Employer: **Company Name**.
   - Student: **Institution** and **Student ID Number**.
4. Type a **Temporary Password** and **Confirm Password**, then click **Create Account**.

**Expected result:** you return to **User Management** with "User created successfully."

To edit, suspend or archive a user, use the buttons on that user's row in **Users**.

### 1.4 Post an announcement
1. Click **Announcements** → **New Announcement**.
2. Fill in **Title**, choose the **Target Role** (**All Users**, **Students**, **Coordinators** or **Employers**), and write the **Content**.
3. Click **Publish**.

**Expected result:** "Announcement published successfully." The audience sees it on their dashboard under **Announcements** and in **Notifications**.

### 1.5 Monitor
- **Company Invitations:** every invitation coordinators sent, with its status (Sent, Accepted, Expired).
- **OJT Placements:** every deployed student's OJT. Click the **Ongoing**, **For clearance** or **Cleared** counters to filter the list.
- **Audit & Security Log**, **Usage & Health** and **Reports:** system activity, health and exports.

---

## Part 2 — Coordinator

The coordinator sidebar has:

- **Overview:** **Dashboard**.
- **Manage:** **Students**, **Eligibility Criteria**, **Applications**, **Partner Companies**, **Report Reviews**, **Statistics**, **Verification**, **OJT Monitoring**, **Reports** and **Analytics**.

The dashboard subtitle names your own school. You only see students from your school.

### 2.1 Invite a company that has no account
1. Click **Partner Companies** → **Invite a company**.
2. Fill in **Company name**, **Address**, **Email** and **Phone number**, then click **Send invitation**.

**Expected result:** the invitation appears under **Your invitations** with the status Sent. The company receives an email with a link that works for 14 days.

If the company did not receive the email or the link expired, click **Resend** on that row.

### 2.2 Confirm a student can apply
A student can apply only when they meet the school's rules. By default, the rules are 4th-year standing and all major subjects passed.

1. Click **Students**, find the student, and click **View profile**.
2. Under **Academic progress and profile verification**, tick **Student has passed all major subjects**. You can also fill in **Rendered internship hours** and **GWA (from the registrar)**.
3. Click **Save academic progress**.

**Expected result:** "Academic readiness updated." **Internship eligibility** changes to **Eligible To Apply**.

- To block a student, type a **Reason** and click **Mark not eligible**. The student sees the reason.
- To remove an earlier block, click **Approve**.

### 2.3 Validate a new OJT
When a company accepts a student, an OJT record is created and waits for you.

1. Click **OJT Monitoring**. The new record shows "Waiting for coordinator validation". Click **View**.
2. Read the **Validate before deployment** checklist (company verified, MOA with the school, program included, posting not flagged).
3. If a check shows "not passed" and you still want to continue, tick **I reviewed the warnings and still want to validate**.
4. Click **Validate placement**.

**Expected result:** "Placement validated. The student can now log hours." The status becomes **Ongoing**.

If something is missing, type it in **Or keep it pending and tell the company and student what is needed** and click **Keep pending with remarks**.

### 2.4 Request a monthly evaluation
Do this once a month while the OJT is ongoing.

1. Open **OJT Monitoring** → **View** for the student.
2. Under **Request a monthly evaluation**, pick the **Month**, set the **Due date**, and leave **From (optional)** and **To (optional)** blank to use the whole month.
3. Click **Request**.

**Expected result:** "Monthly evaluation for November (November 2026) requested. The student rates first."

The order is:
1. The student rates themself.
2. The supervisor rates and certifies.
3. The student adds a comment.
4. You rate last (see 2.6).

### 2.5 Request the final evaluation
1. On the same page, under **Final evaluation request**, set the **Due date** (one week ahead by default).
2. Click **Request final evaluation**.

**Expected result:** "Final evaluation requested from the company. Due …" The company is notified.

This is only available once the OJT is validated and ongoing.

### 2.6 Rate the monthly evaluation (adviser column)
1. When the student has commented, open the student's OJT record.
2. In the monthly evaluation, enter a whole number for each factor in your column (from 0 up to the factor's points).
3. Click **Submit rating**.

**Expected result:** "Adviser rating submitted. The monthly evaluation is complete." The status becomes **Complete**.

To correct your rating, click **Reopen coordinator rating**.

### 2.7 Review weekly reports and evaluations
1. Click **Report Reviews**. It lists weekly reports the supervisor already certified, and submitted evaluations.
2. Read the report, then either:
   - click **Mark reviewed**; or
   - type a **Comment (required to return)** and click **Return** so the student can fix it.

### 2.8 Clear the OJT and issue the certificate
The OJT completes automatically when both of these are true:
- the approved hours reach the required hours; and
- the supervisor has submitted the final evaluation.

1. Open **OJT Monitoring**. In **Status**, choose **For clearance**; the list updates by itself.
2. Click **View** on the student's row. The record shows "Completed … — awaiting coordinator clearance."
3. Under **Clear this OJT**, optionally type **Remarks (optional)**, then click **Clear and issue certificate**.
4. In the **Clear OJT** box, click **Clear and issue certificate** again to confirm.

**Expected result:** "OJT cleared. The student can now download the certificate of completion." The record shows "Cleared … Certificate no. OJT-2026-…".

---

## Part 3 — Company (Employer)

The employer sidebar has:

- **Overview:** **Dashboard** and **Company Profile**.
- **Hiring:** **Internships**, **Applicants**, **Browse Students** and **Sent Invitations**.
- **OJT:** **Interns**.

### 3.1 Create your account from the invitation
1. Open the invitation email and click the link.
2. On **Complete your company account**, check the pre-filled details.
3. Add the remaining details and a password, then click **Create company account**.

**Expected result:** you are signed in and the Employer Dashboard opens. From then on, sign in with that email.

### 3.2 Complete your company profile
1. Click **Company Profile**.
2. Fill in **Company Name**, **Industry**, **Company Size**, **Your job title**, **Website** and **Company Description**. Use **Upload Logo** to add your logo.
3. Click **Save Changes**.

**Expected result:** a short pop-up says "Company profile updated successfully!"

Your job title is filled in automatically on the final evaluation, so fill it in before you evaluate an intern.

### 3.3 Post an internship
1. Click **Internships** → **Post Internship** (also on the dashboard).
2. Fill in **Title**, **Status** (**Open**), **Work Setup**, the programs it is open to, **Location**, **Duration**, **Application deadline** and **Description**.
3. Optionally add **Responsibilities**, **General Requirements**, skills (**Add Requirement**) and requested documents.
4. Click **Post Internship**.

**Expected result:** "Internship posted successfully." Students in the selected programs can now find it.

### 3.4 Review applicants
1. Click **Applicants**. New applications show the status **Pending**. Use the filters to see **Pending**, **Under Review**, **Accepted**, **Rejected** or **Withdrawn**.
2. Click **Review** to open an application.
3. Under **Update Status**, choose the next status and click **Update Status**. A new application can only move to **Reviewed**, **Interview**, **Rejected** or **Withdrawn**, so move it to **Reviewed** before **Accepted**.

**Expected result:** "Application status updated." The badge changes and the next possible statuses appear. You stay on the same page.

When you choose **Accepted**, the OJT record is created and goes to the coordinator for validation. Once a final status is set, the page says "No further status changes are available."

### 3.5 Supervise an intern
1. Click **Interns** and click **Open** on the intern's row.
2. The page has five tabs: **Progress**, **Time Record**, **Weekly Report**, **Monthly Evaluation** and **Final Evaluation**.
3. **Time Record:** click **Approve** or **Reject** on each daily entry. Or click **Approve all pending** and confirm with **Approve all**.
   - **Expected result:** "DTR entry approved." Only approved hours count toward the requirement.
4. **Weekly Report:** read the report and click **Certify true and correct**.
   - **Expected result:** "Report certified true and correct." The report is locked for the student.
5. **Monthly Evaluation:** after the student has rated themself, enter a whole number for each factor and a **Supervisor's comment**, then click **Certify and submit rating**.
   - **Expected result:** "Rating certified and submitted. The student adds a comment next."
6. **Final Evaluation:** see Part 5.

Each report and evaluation has a **Print** button that downloads the school form as a PDF.

---

## Part 4 — Student

The student sidebar has:

- **Overview:** **Dashboard** and **My Profile**.
- **Career:** **My Certificates**, **Internships**, **Invitations** and **Resume**.
- **OJT:** **My OJT**.

### 4.1 First sign-in (accounts imported by the school)
1. Click **Sign in**. In **Email or School ID**, type your School ID (for example `SCC-26-00000001`).
2. For **Password**, type your birthdate as `MMDDYYYY` (May 14, 2003 becomes `05142003`). Click **Sign in**.
3. **Add your Gmail:** type your Gmail address and click **Send Code**.
4. Type the 6-digit code from your email (it expires in 10 minutes) and click **Verify Code**.
5. **Set a new password:** type the birthdate password as **Current Password**, then your **New Password** twice. Click **Update Password**.

**Expected result:** "Your password has been updated." The Student Dashboard opens.

### 4.2 Complete your profile
1. Click **My Profile**.
2. At **Your privacy**, tick the box, then click **I agree**. You cannot save your profile before this.
3. Fill in each tab: **Personal**, **Contact**, **Academic**, **Skills**, **Experience** (optional), **Portfolio**, **Character Reference** and **Documents**.
4. Click **Save changes** on each tab.

**Expected result:** the **Profile completion** percentage goes up, and each finished tab is marked complete.

Your mobile number (on the **Contact** tab) and your School ID, course and year level are printed on your OJT forms, so fill them in first.

### 4.3 Create your resume
1. Click **Resume** → **Generate New Resume**.

**Expected result:** "Resume generated successfully." If parts of your profile are empty, a yellow box lists them. Click **Complete my profile →** to fix them, then generate again.

Only your 3 latest resumes are kept. Click **Back to internships** to return to the list.

### 4.4 Find and apply for an internship
1. Click **Internships**. Click any internship to see a preview, or **Full details** for the full page.
2. If you see **You are not yet eligible to apply**, read the reason under it. Usually your coordinator still has to confirm your major subjects. You can still browse.
3. When you are eligible, click **Apply**, choose a resume in **Resume to send**, and click **Submit application**.

**Expected result:** the button changes to **Applied · View application**.

Follow your applications on **Internships** → **My Applications**. The status moves through **Applied**, **Reviewed**, **Interview**, and then **Accepted** or **Rejected**.

### 4.5 Messages
1. Click the chat icon (**Messages**) in the top bar.
2. Choose a person from the contact list, type your message, and press Enter or click **Send**.

### 4.6 My OJT (after your coordinator validates it)
Click **My OJT**. It has four tabs: **Progress**, **Time Record**, **Weekly Report** and **Monthly Evaluation**. **Due documents** at the top lists what is due soon.

**Details on your forms (Progress tab)**
1. If a yellow box says "Complete your profile first: …", fix each item listed.
2. Choose your **Designation (department)**, check your **Supervisor's name**, and click **Save**.

**Expected result:** "OJT details saved. They are used on your forms from now on." Your reports and evaluations cannot be submitted or printed until these details are complete.

**Daily time (Time Record tab)**
1. Click **Add daily entry**.
2. Fill in **Date**, **Time in**, **Time out**, **Break (min)** and **Tasks done**.
3. Click **Submit time entry**.

**Expected result:** "Time logged. Your supervisor will review it." The entry shows **Pending** until your supervisor approves it.

**Weekly report (Weekly Report tab)**
1. Click **Add weekly report**.
2. Pick **Any day in the week**.
3. Fill in **Difficulties encountered**, **Actions taken** and **Learning insights**.
4. Click **Submit report**.

The daily table on the report is filled from that week's time entries, so you do not type it twice. A week runs Monday to Sunday:
- The table always lists Monday to Friday.
- It also lists a Saturday or Sunday when you logged time that day.
- It never lists days before your OJT start date or after its end date. For example, if your OJT starts on a Wednesday, your first report covers Wednesday to Friday.

**Inclusive Dates** at the top follow the same days.

**Expected result:** "Weekly report submitted. Your supervisor certifies it next."

A weekly report is due a few days after the week ends (3 days by default).

**Monthly evaluation (Monthly Evaluation tab)**
1. When your coordinator requests a month, rate yourself first. Enter a whole number for each factor, from 0 up to its points. Click **Submit rating**.
   - **Expected result:** "Your rating is submitted. Your supervisor rates next."
2. After your supervisor rates, write **Your comment** and click **Submit comment**.
   - **Expected result:** "Your comment is submitted. Your coordinator rates last."

**Finishing**
When your coordinator clears your OJT, a **Certificate of completion** button appears on the **Progress** tab.

---

## Part 5 — The final evaluation

### How it is requested and submitted
1. The **coordinator** opens **OJT Monitoring** → **View** and clicks **Request final evaluation**, with a due date (see 2.5). This works only while the OJT is ongoing.
2. The **company supervisor** opens **Interns** → **Open** → **Final Evaluation**. The form has these parts:
   - **Part II. Training details:** **Division / department assigned**, **Field of training assigned** and **Your designation / job title**. These are pre-filled from the OJT record and your company profile.
   - **Part III. Final performance evaluation:** a whole-number rating for each factor, up to its maximum.
     - **Work performance:** Knowledge at work (25), Quality of Work (25), Quantity of Work (25), Punctuality (10) and Attendance (15). These add up to 100.
     - **Personality traits:** ten traits worth 10 each. These add up to 100.
   - **Part IV. Final comments / recommendations**, and an optional **Certified true and correct** name and position (for example, HR).
3. The supervisor clicks **Submit final evaluation**.

**Expected result:** "Evaluation saved. The OJT completes automatically once the required hours are approved."

If the required hours are already approved, it says "Evaluation saved. The OJT is now complete and awaits coordinator clearance." instead.

The supervisor can update it until the coordinator clears the OJT.

### How it is computed
SkillBridge adds up each part, then turns the two totals into a score from 1 to 5:

```
Final score = ((Work performance total + Personality traits total) / 2) / 20
```

Example from a test run:

| Part | Ratings | Total |
|---|---|---|
| Work performance | 22 + 23 + 22 + 9 + 14 | 90 |
| Personality traits | 9 + 9 + 10 + 9 + 9 + 8 + 9 + 9 + 9 + 9 | 90 |
| **Final score** | ((90 + 90) / 2) / 20 | **4.50 / 5** |

The score is rounded to the nearest whole number to give the rating word:

| Score (rounded) | Rating |
|---|---|
| 1 | Poor |
| 2 | Fair |
| 3 | Satisfactory |
| 4 | Very satisfactory |
| 5 | Outstanding |

So 4.50 rounds to 5 and reads as **Outstanding**.

For comparison, the monthly evaluation is not converted. Each column (Self, Supervisor, Adviser) is simply the sum of its factor scores, out of 100%.

### Where to see the result
| Who | Where | What they see |
|---|---|---|
| Coordinator | **OJT Monitoring** | The **Evaluation** column ("4.50 / 5") for every student |
| Coordinator | **OJT Monitoring** → **View** | **Evaluation** "4.50 / 5" at the top, and the full form under **Final Performance Evaluation**, with **Print** |
| Coordinator | **Report Reviews** | The submitted evaluation, to **Mark reviewed** or **Return** |
| Company | **Interns** → **Open** → **Final Evaluation** | The submitted form and **Print** |
| Student | **My OJT** → **Progress** | **Evaluation** "4.50 / 5". Students see the score, not the detailed ratings or comments. |
| Admin | **OJT Placements** | Every OJT's status, for monitoring |

### What happens next
Once the final evaluation is submitted and the approved hours reach the requirement, the OJT moves to **For clearance**. The coordinator clicks **Clear and issue certificate**, and the student can download the **Certificate of completion**.

---

## Deadlines and reminders
- **Weekly report:** due 3 days after the week ends (the admin can change this). The student gets a reminder 1 day before the due date, and once more if it becomes overdue.
- **Monthly evaluation:** due on the date the coordinator chose. Whoever's turn it is (the student, then the supervisor) gets a reminder 2 days before, and once more if it becomes overdue.
- **Final evaluation:** due on the date the coordinator chose. The supervisor gets the same reminders.

Reminders go out every morning at 8:00 (Philippine time), both in **Notifications** and by email. Users who turned off email notifications get only the bell notification.

---

## Common problems and solutions

| Problem | Solution |
|---|---|
| "Incorrect email/School ID or password." | Check spelling and caps lock. Students with imported accounts use their School ID and birthdate (MMDDYYYY) the first time. Otherwise click **Forgot password?**. |
| The 6-digit code did not arrive | Check Spam. Click **Didn't get it? Resend code**, or **Wrong email? Use a different one**. The code expires in 10 minutes. |
| "Tick the privacy box at the top of the page and click "I agree" before saving your profile." | On **My Profile**, tick the privacy box under **Your privacy** and click **I agree**, then save again. |
| "You are not yet eligible to apply." | Read the reason under it. Usually the coordinator must tick **Student has passed all major subjects** on your profile. Ask your coordinator. |
| The **Apply** button is missing and you see **Create a resume to apply** | Open **Resume** → **Generate New Resume**, then come back. |
| The resume has empty sections | Complete the profile tabs listed in the yellow box on the **Resume** page, then generate again. |
| A student cannot log hours | The OJT is still "Waiting for coordinator validation". The coordinator must click **Validate placement**. |
| "Complete your profile first: …" on My OJT | Fill in each listed item. Designation and supervisor name are set right there, under **Details on your forms**. The rest are on **My Profile**. |
| The company cannot choose **Accepted** | Set the status to **Reviewed** (or **Interview**) first, then **Accepted**. |
| The coordinator cannot request the final evaluation | It opens only after **Validate placement**, while the OJT is ongoing. |
| A monthly evaluation is stuck | It follows a fixed order: student → supervisor → student comment → coordinator. The status line shows whose turn it is. |
| A score is refused | Enter whole numbers from 0 up to the factor's maximum (the number in the points column). |
| A Saturday or Sunday is missing from the weekly report | Weekend days appear only when a time entry was logged for that day and was not rejected. Add the entry under **Time Record** first, then submit the report. |
| Hours do not count | Only **Approved** daily entries count. Ask the supervisor to approve them under **Interns** → **Open** → **Time Record**. |
| Import Students shows errors | Fix the rows listed on **Import Results**. Check the School ID format, dates (`YYYY-MM-DD` or `MM/DD/YYYY`), school year (`2026-2027`) and semester (`1st Semester`, `2nd Semester`, `Summer`). Then upload the file again; rows already created are updated, not duplicated. |
| A company's invitation link does not work | Links expire after 14 days and work once. The coordinator clicks **Resend** under **Partner Companies** → **Your invitations**. |
| The certificate button is missing | The OJT must be cleared first. The coordinator clicks **Clear and issue certificate** when it shows **For clearance**. |
