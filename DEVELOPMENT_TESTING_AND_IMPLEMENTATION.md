# Development and Testing Procedure Plan
## Skill-Bridge System

This section outlines how the Skill-Bridge System was developed, the tools and technologies used, and the testing procedures planned to ensure that the system works as intended.

## 1. Development Process

The development of the Skill-Bridge System followed a structured and iterative Agile methodology. The process was divided into four key phases: Planning, Design, Development, and Testing. Evaluation and feedback will guide the Improvement phase in the next iteration.

### Planning Phase

The system requirements were analyzed to ensure that the platform addresses the needs of students, employers, institution coordinators, and administrators.

- Identified the need for a centralized platform for student competencies and internship placement.
- Defined system priorities, including student profiling, competency tracking, internship opportunities, matching, applications, notifications, and dashboards.
- Reviewed related studies and existing internship placement systems to identify functional and non-functional requirements.
- Defined the system scope and user roles: Administrator, Student, Employer, and Institution Coordinator.
- Identified the core modules: User Management, Student Profiles, Competency Management, Portfolio and Resume Management, Internship Management, Competency Matching, Applications, Notifications, Messaging, and Reports.
- Assessed the technical requirements and selected Laravel, MySQL, and a local XAMPP server for development.

### Design Phase

This phase translated the identified requirements into the system's technical structure and interface designs. Emphasis was placed on usability, data integrity, security, and role-based access.

- Designed the Laravel MVC architecture and service-layer structure.
- Designed the MySQL database schema for users, students, competencies, internships, requirements, applications, notifications, and reports.
- Defined relationships between student competencies, internship requirements, matching results, and applications.
- Designed role-based dashboards and navigation for each type of user.
- Designed responsive interfaces using Blade templates and Bootstrap 5.
- Prepared system workflows for registration, profile completion, internship posting, matching, application submission, and application status tracking.
- Planned validation, authentication, authorization, file storage, and activity logging procedures.

### Development Phase

The system was developed using a modular and incremental approach. Each module was implemented, reviewed, and connected with the other modules according to the approved requirements and design.

#### Frontend Development

- Technologies: Blade templates, HTML, CSS, Bootstrap 5, JavaScript, Bootstrap Icons, and Chart.js.
- Focus: Responsive layouts, role-based dashboards, forms, tables, notifications, charts, validation messages, and accessible navigation.

#### Backend Development

- Framework: Laravel 12 PHP Framework.
- Programming language: PHP 8.2+.
- Database: MySQL using Eloquent ORM and Laravel migrations.
- Server environment: Local XAMPP Apache and MySQL server for development and demonstration.
- Supporting tools: Composer for PHP dependencies and npm/Vite for frontend dependencies and asset building.
- Core services: Competency matching, notifications, resume generation, report export, and activity logging.

#### Team Workflow

The development team worked collaboratively with defined responsibilities:

- **Project Manager:** Coordinated requirements, monitored progress, managed timelines, and reviewed milestone completion.
- **Backend Developer:** Developed Laravel models, migrations, controllers, services, validation, authentication, authorization, and database functionality.
- **Frontend Developer:** Developed Blade views, Bootstrap layouts, forms, dashboards, responsive behavior, and user interface components.
- **Data Analyst:** Assisted with database structure, test data preparation, data validation, and interpretation of system results.
- **Tester:** Prepared test scenarios, executed tests, recorded defects, and verified corrections.
- **Documenters and Researchers:** Prepared technical documentation, user instructions, progress records, and research-related system descriptions.

The team used iterative development, regular progress reviews, issue recording, code review, and retesting after corrections. Git was used to track changes and preserve the development history.

## 2. Testing Plans

Testing was planned to evaluate system reliability, functionality, security, performance, and user satisfaction. Formal evaluation and data gathering have not yet been completed; therefore, final testing results remain pending.

### Unit Testing

Unit testing verifies individual models, services, functions, and validation rules. Tests will include:

- Competency and proficiency-level handling.
- Competency matching and match-percentage calculation.
- Application status transitions.
- Form validation rules.
- Notification creation.
- User-role and permission checks.

### Integration Testing

Integration testing verifies that connected modules work together without conflicts. Test combinations will include:

- Student competencies, internship requirements, and competency matching.
- Internship applications, status updates, notifications, and dashboards.
- User registration, role assignment, authentication, and role-specific dashboard access.
- Portfolio records, resume generation, and application information.
- Internship postings, search filters, requirements, and student recommendations.

### System Testing

System testing evaluates the complete Skill-Bridge System under normal user workflows. It will cover:

- Authentication and authorization.
- Student profile and competency management.
- Portfolio uploads and resume generation.
- Internship creation, editing, searching, and filtering.
- Matching results and recommendations.
- Application submission and tracking.
- Notifications, messaging, and reports.
- Form validation, file validation, security controls, responsiveness, and performance.

### User Acceptance Testing

User acceptance testing will involve representative students, employers, institution coordinators, and administrators. Participants will complete tasks appropriate to their roles and provide feedback through surveys, interviews, observations, and feedback forms.

The evaluation will examine functionality, ease of use, reliability, performance, security, maintainability, and portability using the seven ISO/IEC 25010 quality dimensions. Data gathering has not yet been conducted, so user acceptance results and satisfaction ratings are pending.

### Sample Test Cases

#### Test Case 1: Student Profile and Account Access

- **Test Case ID:** TC-001
- **Test Type:** Functional testing
- **Actor:** Student
- **Objective:** Verify that a student can register, log in, and complete a profile.
- **Preconditions:** The student has valid registration information and access to the system.
- **Test Steps:**
  1. Open the registration page.
  2. Enter valid student information.
  3. Submit the registration form.
  4. Log in using the created account.
  5. Open the student profile and complete the required fields.
- **Expected Result:** The account is created, login succeeds, the student is directed to the student dashboard, and the completed profile is saved.
- **Status:** Pending execution and recording of results.

#### Test Case 2: Competency-Based Internship Matching

- **Test Case ID:** TC-002
- **Test Type:** Integration testing
- **Actor:** Student
- **Objective:** Verify that internship recommendations use the student's competencies and proficiency levels.
- **Preconditions:** The student has saved competencies, and an employer has posted an internship with competency requirements.
- **Test Steps:**
  1. Log in as a student.
  2. Add or verify the student's competencies and proficiency levels.
  3. Open the internship recommendations page.
  4. Select an internship posting.
  5. Review the displayed match result.
- **Expected Result:** The system compares the student's competencies with the internship requirements and displays the corresponding match result and recommendation.
- **Status:** Pending execution and recording of results.

#### Test Case 3: Role-Based Access Control

- **Test Case ID:** TC-003
- **Test Type:** Security testing
- **Actor:** Student or unauthorized user
- **Objective:** Verify that users cannot access functions assigned to another role.
- **Preconditions:** The user is logged in with a student account.
- **Test Steps:**
  1. Attempt to open an administrator or employer management page.
  2. Attempt to create or modify a restricted record.
- **Expected Result:** Access is denied, restricted data is not displayed, and the unauthorized action is not completed.
- **Status:** Pending execution and recording of results.

## 3. Implementation Strategies

The implementation strategy focuses on deploying the system correctly, introducing it to users, and maintaining stable and secure operation.

### Deployment

The current prototype will run on a local development server using XAMPP. Apache will serve the Laravel application, while MySQL will store system data. The web server document root must point to the Laravel `public` directory.

After system testing, user evaluation, and approval, the application may be deployed to web hosting or a production server using Apache or Nginx, PHP 8.2+, and MySQL.

### Pre-Installation Requirements

- Windows computer with XAMPP installed.
- Apache and MySQL services enabled.
- PHP 8.2 or later with the Laravel-required extensions.
- Composer installed for PHP dependencies.
- Node.js and npm installed for frontend dependencies.
- MySQL database and database credentials.
- Configured `.env` file containing the application URL, application key, database settings, mail settings, session settings, and storage settings.
- Writable `storage` and `bootstrap/cache` directories.
- Laravel storage link configured for uploaded files.
- Database migrations executed before first use.

### Installation and Configuration

- Install PHP dependencies using Composer.
- Install frontend dependencies using npm.
- Generate the Laravel application key.
- Configure the MySQL database in the `.env` file.
- Run migrations and seeders when test data is required.
- Build frontend assets using Vite.
- Configure mail, sessions, file storage, and application permissions.
- Verify login, dashboards, database operations, file uploads, and the main user workflows.

### Training and Orientation

Users will receive role-specific orientation on system navigation, data handling, privacy, and proper use of the system.

#### Administrator

- Manage user accounts and roles.
- Review system activity and reports.
- Monitor system settings and data records.
- Perform or coordinate database backups.

#### Student

- Create and update a profile.
- Add competencies and proficiency levels.
- Manage portfolio records and resume information.
- Browse internships, review match results, and submit applications.
- Track application statuses and notifications.

#### Employer

- Create and update an employer profile.
- Post internship opportunities.
- Define competency requirements.
- Review applications and communicate with applicants.

#### Institution Coordinator

- Monitor student profiles and competency progress.
- Review internship and application information.
- Generate institutional reports.
- Support students and coordinate with employers.

Supporting materials will include user instructions, system guidelines, privacy information, and basic troubleshooting procedures.

### Maintenance and Support

Since the system is pending final deployment, the development team will initially manage maintenance and support. Maintenance activities will include:

- Monitoring application logs and system errors.
- Applying Laravel, PHP, and dependency security updates.
- Correcting defects and retesting affected features.
- Backing up the database and uploaded files.
- Reviewing user feedback and recurring issues.
- Managing database migrations and system changes through Git.
- Checking storage capacity, performance, and access permissions.

Users may report errors and operational concerns through a designated support email or issue record. Reported issues will be documented, prioritized, assigned, resolved, and verified through retesting. Production readiness will be confirmed only after the required testing, user evaluation, data gathering, and final improvements have been completed.
