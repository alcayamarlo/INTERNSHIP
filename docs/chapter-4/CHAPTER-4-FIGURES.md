# Chapter 4 Figures: Entity-Relationship and Use Case Diagrams

Each figure below has its caption and a descriptive paragraph for the chapter
text. The image files are in this folder, as SVG (sharpest in Word 365) and as
PNG. Renumber the figures if the chapter already has earlier ones.

---

## Entity-Relationship Diagrams

The entity-relationship diagrams use crow's foot notation. A short double bar
means exactly one, a circle means zero (optional), and a crow's foot means
many. PK marks a primary key, FK a foreign key, and UK a unique key. The
diagrams were produced from the system's MySQL/MariaDB database schema.

### Figure 4.1 Entity-Relationship Diagram of Skill-Bridge (Core Entities)

Image file: `fig-4-01-erd-core.svg` / `.png`

Figure 4.1 presents the core entities of Skill-Bridge and the relationships
that carry an internship from posting to OJT clearance. Every account is stored
in the users entity and is linked to exactly one role profile: students,
employers, or coordinators. Students belong to an institution and a program.
An employer posts internships, and students submit internship applications to
them. When an employer accepts an application, the system opens one placement
for it, and a student may hold only one open placement at a time. Each
placement records the student's daily attendance logs, weekly journal entries,
and at most one supervisor evaluation.

### Figure 4.2 ERD: Accounts and Academic Records

Image file: `fig-4-02-erd-accounts-academics.svg` / `.png`

Figure 4.2 details the account and academic entities. The users entity holds
the login credentials, role, email verification, and account status of every
user. Student records reference their institution, program, and major, and the
student ID number is unique because it also serves as a login username.
Programs are handled by coordinators and contain majors. Eligibility criteria
are versioned per program, school year, and term, while eligibility decisions
and change requests record the coordinator's actions on individual students.
The academic settings entity is a single record of system-wide defaults, such
as the required number of OJT hours.

### Figure 4.3 ERD: Internships and Applications

Image file: `fig-4-03-erd-internships-applications.svg` / `.png`

Figure 4.3 shows how internships are posted and applied for. An internship
belongs to one employer, is open to one or more programs through the
internship_program associative entity, and lists its required competencies and
skills. A student may apply to an internship only once, and each application
may attach a resume. Every change in an application's status is kept in the
application status history. Employers can also invite students, shortlist
them, and receive their recommendation feedback, while coordinators can flag
internships for review.

### Figure 4.4 ERD: OJT Placement and Tracking

Image file: `fig-4-04-erd-ojt-tracking.svg` / `.png`

Figure 4.4 presents the entities used once a student is deployed. Each
placement is created from exactly one accepted application. The open_student_id
column holds the student's ID only while the placement is pending validation
or active, and its unique key ensures that a student cannot have two ongoing
OJTs. Attendance logs are unique per placement and work date, journal entries
are unique per placement and week, and each placement has at most one
evaluation. The users entity records who validated, cleared, reviewed, or
evaluated each record.

### Figure 4.5 ERD: Student Profile and Evidence

Image file: `fig-4-05-erd-student-evidence.svg` / `.png`

Figure 4.5 shows the records a student submits for review. Student
competencies may be supported by certificates, portfolio files may fulfil a
deployment requirement, and resumes are generated from the student's profile.
Skills, work experience, awards, and character references complete the
student's bio-data. Each verification decision made by a coordinator is
recorded in the verification activities entity.

### Figure 4.6 ERD: Communication and Audit

Image file: `fig-4-06-erd-communication-audit.svg` / `.png`

Figure 4.6 presents the communication and audit entities linked to users.
Notifications are separated into the inbox, archived, and trash views through
their read, archived, and deleted timestamps. Messages are kept when they are
edited, unsent, or removed by the sender. Administrators publish announcements
and resolve content reports, and the system logs entity serves as the audit
trail of user actions.

### Appendix Figure A.1 Complete Entity-Relationship Diagram

Image file: `fig-a-01-erd-complete.svg` / `.png` (place on a landscape or A3 page)

Appendix Figure A.1 combines all 45 application entities and their
relationships in a single diagram. Columns that only record which user reviewed
or verified a record are omitted to keep the diagram readable.

---

## Use Case Diagrams

The use case diagrams follow UML notation. A stick figure represents an actor,
an ellipse represents a use case, and the rectangle marks the system boundary.
A dashed arrow labelled «include» points to a use case that always runs as part
of another, and one labelled «extend» points from an optional use case to the
one it extends. A line with a hollow triangle represents generalization.

### Figure 4.7 Use Case Diagram of Skill-Bridge

Image file: `fig-4-07-usecase-system.svg` / `.png`

Figure 4.7 presents the four primary actors of Skill-Bridge and how they
complete one internship together. The administrator creates accounts and
verifies companies. Students complete their profiles, apply for internships,
log their OJT time and weekly journals, and download their certificate.
Employers post internships, review and accept applicants, approve time records
and journals, and submit evaluations. Accepting an applicant includes opening
an OJT placement, limited to one per student, which the coordinator then
validates, monitors, and clears after verifying the student's evidence.

### Figure 4.8 Use Case Diagram: Common Functions of All Users

Image file: `fig-4-08-usecase-all-users.svg` / `.png`

Figure 4.8 shows the functions shared by every signed-in user. Student,
employer, coordinator, and administrator are specializations of the User
actor. On the first sign-in, a user adds and verifies an email address with a
code and then sets a new password. Users can edit their profiles, and a
student who changes the email address must verify it again. All users can
manage notifications, send messages, report content, and search. The email
service delivers verification and password reset codes, and Pusher delivers
live updates.

### Figure 4.9 Use Case Diagram: Student

Image file: `fig-4-09-usecase-student.svg` / `.png`

Figure 4.9 presents the student's use cases. The student completes a bio-data
profile, records certificates and competencies, uploads portfolio and
deployment documents, and generates a resume. The student browses
internships, applies with an attached resume, tracks applications, and
responds to employer invitations. Once the placement is validated, the student
logs daily time records, submits weekly journals, tracks OJT progress, and
downloads the certificate of completion.

### Figure 4.10 Use Case Diagram: Employer

Image file: `fig-4-10-usecase-employer.svg` / `.png`

Figure 4.10 presents the employer's use cases. The employer manages the
company profile, posts internships with their competency requirements and
eligible programs, browses and shortlists
students, and sends invitations. The employer reviews applicants and updates
their status; accepting an applicant opens the OJT placement. During the OJT,
the employer sets the intern's schedule, approves or rejects time records,
acknowledges weekly journals, and submits the performance evaluation.

### Figure 4.11 Use Case Diagram: OJT Coordinator

Image file: `fig-4-11-usecase-coordinator.svg` / `.png`

Figure 4.11 presents the coordinator's use cases. The coordinator reviews and
verifies student profiles and their evidence, records eligibility and academic
readiness, manages eligibility criteria, and reviews change requests. The
coordinator monitors applications, manages partner companies and their
memoranda of agreement, and flags internships when needed. For each
placement, the coordinator validates or holds it, requests the employer's
rating, reviews journals and evaluations, and clears the OJT, which issues the
certificate of completion. The coordinator also generates reports and
statistics.

### Figure 4.12 Use Case Diagram: Administrator

Image file: `fig-4-12-usecase-administrator.svg` / `.png`

Figure 4.12 presents the administrator's use cases. The administrator manages
user accounts, imports students, verifies companies and email addresses, and
manages institutions, academic settings, programs, eligibility criteria, and
skill catalogs. The administrator publishes announcements, which the email
service delivers, monitors OJT placements, and moderates reported content. The
administrator also reviews the audit and security log, checks system health,
backs up the database, and generates system reports.
