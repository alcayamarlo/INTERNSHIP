<?php

namespace Database\Seeders\Presentation;

use App\Enums\CertificateType;
use App\Enums\ProficiencyLevel;
use App\Enums\SkillType;

/**
 * The people, companies and per-department texts behind the presentation data (PresentationDataSeeder).
 * Names are Cebuano / Filipino; every email is firstname.lastname@gmail.com (the system never emails them: see
 * PresentationDataSeeder::user()) and every account has the password "password" (never created in production).
 */
final class Data
{
    public const PASSWORD = 'password';

    /** Department code => the college it belongs to, what the intern does there, and the paperwork texts. */
    public static function departments(): array
    {
        return [
            'BSIT' => [
                'college' => 'College of Information Technology',
                'field' => 'Software Development and IT Support',
                'designation' => 'IT Department',
                'skills' => ['PHP', 'Laravel', 'JavaScript', 'MySQL', 'Python', 'Git'],
                'tasks' => ['Built CRUD pages with Laravel and Blade', 'Fixed bugs reported by the QA team', 'Wrote SQL queries for the monthly report', 'Tested a new login flow and logged the results', 'Set up a local development environment for the team', 'Documented the API endpoints in Postman'],
                'challenges' => ['Understanding a large existing codebase', 'Debugging a slow database query', 'Keeping up with the sprint schedule'],
                'actions' => ['Asked my supervisor to walk me through the main modules', 'Added indexes and checked the query plan', 'Broke the tasks into smaller steps and updated the board daily'],
                'learnings' => ['Reading other people\'s code carefully saves time', 'Version control habits make teamwork easier', 'Clear commit messages help the whole team'],
                'certificates' => [
                    ['Web Development Certificate', CertificateType::Certificate, 'TESDA Cebu', SkillType::Technical, ProficiencyLevel::Intermediate, ['HTML', 'CSS', 'JavaScript']],
                    ['Cybersecurity Fundamentals', CertificateType::Training, 'Cisco Networking Academy', SkillType::Technical, ProficiencyLevel::Beginner, ['Network Security', 'Threat Awareness']],
                    ['Database Management Training', CertificateType::Training, 'Oracle Academy', SkillType::Technical, ProficiencyLevel::Intermediate, ['SQL', 'Database Design']],
                    ['Seminar on Professional Ethics and Workplace Readiness', CertificateType::Seminar, 'St. Cecilia\'s College - College of IT', SkillType::Soft, ProficiencyLevel::Intermediate, ['Professionalism', 'Teamwork']],
                    ['Programming Fundamentals (Python)', CertificateType::Certificate, 'Coursera', SkillType::Technical, ProficiencyLevel::Intermediate, ['Python', 'Problem Solving']],
                ],
            ],
            'BSHM' => [
                'college' => 'College of Hospitality and Tourism Management',
                'field' => 'Guest Experience and Events Support',
                'designation' => 'Operations Department',
                'skills' => ['Customer Service', 'Food Safety', 'Event Coordination', 'Communication', 'Teamwork', 'MS Office'],
                'tasks' => ['Greeted client visitors and handled their inquiries', 'Prepared the venue checklist for a company event', 'Answered customer emails using the support templates', 'Updated the guest feedback spreadsheet', 'Helped set up a client training session', 'Prepared welcome kits for new hires'],
                'challenges' => ['Handling several guest requests at the same time', 'Learning the company\'s ticketing tool', 'Staying calm with an upset client'],
                'actions' => ['Prioritised requests by urgency and noted them down', 'Watched a tutorial and practised on sample tickets', 'Listened first, then offered two options to the client'],
                'learnings' => ['Clear communication prevents most complaints', 'Preparation makes events run smoothly', 'Small details are what guests remember'],
                'certificates' => [
                    ['Food Safety and Sanitation Training', CertificateType::Training, 'Department of Health Cebu', SkillType::Technical, ProficiencyLevel::Intermediate, ['Food Handling', 'Sanitation']],
                    ['Customer Service Excellence Seminar', CertificateType::Seminar, 'Cebu Hotel and Restaurant Association', SkillType::Soft, ProficiencyLevel::Intermediate, ['Customer Service', 'Communication']],
                    ['Housekeeping NC II', CertificateType::Certificate, 'TESDA Cebu', SkillType::Technical, ProficiencyLevel::Intermediate, ['Housekeeping', 'Room Standards']],
                    ['Events Management Workshop', CertificateType::Workshop, 'Visayas Events Guild', SkillType::Technical, ProficiencyLevel::Beginner, ['Event Planning', 'Logistics']],
                    ['Workplace Readiness and Ethics Seminar', CertificateType::Seminar, 'St. Cecilia\'s College - CHTM', SkillType::Soft, ProficiencyLevel::Intermediate, ['Professionalism', 'Teamwork']],
                ],
            ],
            'BSTM' => [
                'college' => 'College of Hospitality and Tourism Management',
                'field' => 'Digital Content and Customer Experience',
                'designation' => 'Marketing Department',
                'skills' => ['Tour Itinerary Planning', 'Customer Service', 'Content Writing', 'Communication', 'Canva', 'MS Office'],
                'tasks' => ['Wrote destination descriptions for the company blog', 'Designed social media posts using Canva', 'Compiled a list of partner travel agencies', 'Answered customer chats and logged their concerns', 'Helped prepare a client presentation', 'Updated the weekly content calendar'],
                'challenges' => ['Writing for a technical audience', 'Meeting content deadlines every week', 'Learning the analytics dashboard'],
                'actions' => ['Read past posts to match the tone and asked for feedback', 'Planned the posts a week ahead', 'Practised with the sample reports before presenting numbers'],
                'learnings' => ['A clear message beats a long one', 'Deadlines need a plan, not last-minute effort', 'Feedback makes the next draft better'],
                'certificates' => [
                    ['Tour Guiding NC II', CertificateType::Certificate, 'TESDA Cebu', SkillType::Technical, ProficiencyLevel::Intermediate, ['Tour Guiding', 'Local History']],
                    ['Travel Services Fundamentals', CertificateType::Training, 'Philippine Tour Operators Association', SkillType::Technical, ProficiencyLevel::Beginner, ['Itinerary Planning', 'Booking Systems']],
                    ['Digital Marketing Basics Workshop', CertificateType::Workshop, 'Google Digital Garage', SkillType::Technical, ProficiencyLevel::Beginner, ['Social Media', 'Content Planning']],
                    ['Cultural Heritage Awareness Seminar', CertificateType::Seminar, 'Cebu Provincial Tourism Office', SkillType::Soft, ProficiencyLevel::Intermediate, ['Cultural Awareness', 'Communication']],
                    ['Workplace Readiness and Ethics Seminar', CertificateType::Seminar, 'St. Cecilia\'s College - CHTM', SkillType::Soft, ProficiencyLevel::Intermediate, ['Professionalism', 'Teamwork']],
                ],
            ],
            'BSBA' => [
                'college' => 'College of Business Administration',
                'field' => 'Business Operations and Digital Marketing',
                'designation' => 'Administrative Department',
                'skills' => ['MS Excel', 'Business Communication', 'Digital Marketing', 'Data Entry', 'Teamwork', 'Time Management'],
                'tasks' => ['Updated the client database and removed duplicates', 'Drafted email campaigns for a product launch', 'Prepared the weekly sales summary in Excel', 'Scheduled appointments for the account managers', 'Researched competitors for the marketing team', 'Filed and organised the monthly purchase documents'],
                'challenges' => ['Working with a very large spreadsheet', 'Writing persuasive email copy', 'Managing my time between several requests'],
                'actions' => ['Learned pivot tables and filters from a short tutorial', 'Studied past campaigns and wrote three versions', 'Used a daily task list ranked by deadline'],
                'learnings' => ['Good data entry habits prevent costly mistakes', 'A strong subject line decides if the email is read', 'Planning the day in the morning saves hours'],
                'certificates' => [
                    ['Digital Marketing Fundamentals', CertificateType::Certificate, 'Google Digital Garage', SkillType::Technical, ProficiencyLevel::Intermediate, ['SEO', 'Social Media Marketing']],
                    ['Excel for Business Training', CertificateType::Training, 'Microsoft Learn', SkillType::Technical, ProficiencyLevel::Intermediate, ['MS Excel', 'Data Analysis']],
                    ['Entrepreneurship Seminar', CertificateType::Seminar, 'Cebu Chamber of Commerce and Industry', SkillType::Soft, ProficiencyLevel::Beginner, ['Business Planning', 'Networking']],
                    ['Business Communication Workshop', CertificateType::Workshop, 'Philippine Institute of Management', SkillType::Soft, ProficiencyLevel::Intermediate, ['Business Writing', 'Presentation']],
                    ['Workplace Readiness and Ethics Seminar', CertificateType::Seminar, 'St. Cecilia\'s College - CBA', SkillType::Soft, ProficiencyLevel::Intermediate, ['Professionalism', 'Teamwork']],
                ],
            ],
            'BSMA' => [
                'college' => 'College of Business Administration',
                'field' => 'Accounting, Payroll and Billing',
                'designation' => 'Finance Department',
                'skills' => ['Bookkeeping', 'MS Excel', 'Payroll Basics', 'Financial Reporting', 'Attention to Detail', 'Communication'],
                'tasks' => ['Recorded daily transactions in the accounting software', 'Reconciled the bank statement with the cash book', 'Prepared payroll summaries for the HR team', 'Checked supplier invoices against purchase orders', 'Helped prepare the monthly financial report', 'Filed tax documents for the quarter'],
                'challenges' => ['Finding a small difference in a reconciliation', 'Learning the payroll rules', 'Keeping accurate records under deadline'],
                'actions' => ['Re-checked each entry against the receipts until it matched', 'Asked the finance officer to explain each deduction', 'Used a checklist for every month-end task'],
                'learnings' => ['Accuracy matters more than speed in accounting', 'Every number needs a document behind it', 'A checklist removes most errors'],
                'certificates' => [
                    ['Bookkeeping NC III', CertificateType::Certificate, 'TESDA Cebu', SkillType::Technical, ProficiencyLevel::Intermediate, ['Bookkeeping', 'Ledger Posting']],
                    ['Basic Accounting Software Training', CertificateType::Training, 'QuickBooks Philippines', SkillType::Technical, ProficiencyLevel::Intermediate, ['Accounting Software', 'Reconciliation']],
                    ['Advanced Excel for Accountants', CertificateType::Workshop, 'Philippine Institute of Certified Public Accountants', SkillType::Technical, ProficiencyLevel::Advanced, ['MS Excel', 'Financial Reports']],
                    ['Taxation Updates Seminar', CertificateType::Seminar, 'Bureau of Internal Revenue Cebu', SkillType::Technical, ProficiencyLevel::Beginner, ['Tax Compliance', 'Record Keeping']],
                    ['Workplace Readiness and Ethics Seminar', CertificateType::Seminar, 'St. Cecilia\'s College - CBA', SkillType::Soft, ProficiencyLevel::Intermediate, ['Professionalism', 'Teamwork']],
                ],
            ],
        ];
    }

    /**
     * The 9 new coordinators (the existing BSIT coordinator is kept). Each department ends with two.
     *
     * @return list<array{name: string, program: string, position: string}>
     */
    public static function coordinators(): array
    {
        return [
            ['name' => 'Lorna Estenzo', 'program' => 'BSHM', 'position' => 'OJT Coordinator'],
            ['name' => 'Mylene Cuizon', 'program' => 'BSTM', 'position' => 'OJT Coordinator'],
            ['name' => 'Gina Abellana', 'program' => 'BSBA', 'position' => 'OJT Coordinator'],
            ['name' => 'Teresita Lapinig', 'program' => 'BSMA', 'position' => 'OJT Coordinator'],
        ];
    }

    /**
     * The 10 IT companies, each with its HR account and its supervisor, and the one internship it offers.
     *
     * @return list<array<string, mixed>>
     */
    public static function companies(): array
    {
        return [
            ['company' => 'Cebu Pixel Labs', 'industry' => 'Custom Computer Programming Services', 'size' => '11-50 employees', 'address' => 'Cebu IT Park, Apas, Lahug, Cebu City', 'hr' => 'Roselyn Cabatingan', 'supervisor' => 'Noel Dumaguing', 'supervisor_position' => 'Senior Software Engineer', 'role' => 'Web Application Developer Intern'],
            ['company' => 'Visayas Cloudworks', 'industry' => 'Computer Systems Design Services', 'size' => '51-200 employees', 'address' => 'A.S. Fortuna Street, Mandaue City', 'hr' => 'Dominic Sabellano', 'supervisor' => 'Rowena Caballes', 'supervisor_position' => 'QA and Support Lead', 'role' => 'QA and Systems Support Intern'],
            ['company' => 'Mactan Digital Systems', 'industry' => 'Data Processing, Hosting, and Related Services', 'size' => '51-200 employees', 'address' => 'Mactan Economic Zone 1, Lapu-Lapu City', 'hr' => 'Jocelyn Alburo', 'supervisor' => 'Jerome Alferez', 'supervisor_position' => 'Operations Supervisor', 'role' => 'Guest Experience and Events Intern'],
            ['company' => 'Queen City Software', 'industry' => 'Software Publishers', 'size' => '11-50 employees', 'address' => 'Osmena Boulevard, Cebu City', 'hr' => 'Francis Moralde', 'supervisor' => 'Michelle Pacatang', 'supervisor_position' => 'Customer Success Manager', 'role' => 'Customer Support Intern'],
            ['company' => 'Islas Data Solutions', 'industry' => 'Custom Computer Programming Services', 'size' => '11-50 employees', 'address' => 'Banilad Town Centre, Banilad, Cebu City', 'hr' => 'Maricar Gonzaga', 'supervisor' => 'Gilbert Ceniza', 'supervisor_position' => 'Content and Community Lead', 'role' => 'Digital Content and Travel Tech Intern'],
            ['company' => 'Sugbo NetWorks', 'industry' => 'Computer Systems Design Services', 'size' => '201-500 employees', 'address' => 'Tabunok, Talisay City, Cebu', 'hr' => 'Alvin Canete', 'supervisor' => 'Vanessa Libres', 'supervisor_position' => 'Customer Experience Manager', 'role' => 'Customer Experience Intern'],
            ['company' => 'Bisaya Byte Technologies', 'industry' => 'Custom Computer Programming Services', 'size' => '11-50 employees', 'address' => 'Subangdaku, Mandaue City', 'hr' => 'Cheryl Bontuyan', 'supervisor' => 'Lester Sepulveda', 'supervisor_position' => 'Business Operations Manager', 'role' => 'Business Operations Intern'],
            ['company' => 'Pearl Ocean IT Services', 'industry' => 'Computer Facilities Management Services', 'size' => '51-200 employees', 'address' => 'Mango Avenue, Cebu City', 'hr' => 'Leandro Pacana', 'supervisor' => 'Daryl Maglasang', 'supervisor_position' => 'Marketing Manager', 'role' => 'Digital Marketing Intern'],
            ['company' => 'Metro Cebu Tech Hub', 'industry' => 'Other Computer Related Services', 'size' => '51-200 employees', 'address' => 'Poblacion, Minglanilla, Cebu', 'hr' => 'Sheila Mercado', 'supervisor' => 'Joan Escario', 'supervisor_position' => 'Senior Accountant', 'role' => 'Accounting and Finance Intern'],
            ['company' => 'Kawasan Digital Studio', 'industry' => 'Software Publishers', 'size' => '11-50 employees', 'address' => 'Gorordo Avenue, Lahug, Cebu City', 'hr' => 'Bernard Salinas', 'supervisor' => 'Ryan Cadungog', 'supervisor_position' => 'Finance and Payroll Officer', 'role' => 'Payroll and Billing Intern'],
        ];
    }

    /**
     * The 10 students: two per department, one per coordinator. `ojt` is where each is in the OJT:
     * completed_cleared, completed, ongoing or pending_validation. `strengths` / `weak` shift the competency scores.
     *
     * @return list<array<string, mixed>>
     */
    public static function students(): array
    {
        return [
            ['name' => 'Jericho Pepito', 'gender' => 'Male', 'program' => 'BSIT', 'year' => '4th Year', 'section' => 'A', 'ojt' => 'completed_cleared', 'strengths' => [0, 1, 2], 'weak' => [5]],
            ['name' => 'Hazel Villaflor', 'gender' => 'Female', 'program' => 'BSIT', 'year' => '4th Year', 'section' => 'B', 'ojt' => 'ongoing', 'strengths' => [5, 6, 9], 'weak' => [1]],
            ['name' => 'Mikaela Tumulak', 'gender' => 'Female', 'program' => 'BSHM', 'year' => '4th Year', 'section' => 'A', 'ojt' => 'ongoing', 'strengths' => [5, 6, 7], 'weak' => [1, 2]],
            ['name' => 'Dennis Cabahug', 'gender' => 'Male', 'program' => 'BSHM', 'year' => '3rd Year', 'section' => 'B', 'ojt' => 'completed', 'strengths' => [4, 7, 8], 'weak' => [2]],
            ['name' => 'Rhea Dacillo', 'gender' => 'Female', 'program' => 'BSTM', 'year' => '4th Year', 'section' => 'A', 'ojt' => 'ongoing', 'strengths' => [5, 9, 8], 'weak' => [1]],
            ['name' => 'Joshua Gabutan', 'gender' => 'Male', 'program' => 'BSTM', 'year' => '3rd Year', 'section' => 'B', 'ojt' => 'pending_validation', 'strengths' => [3, 4], 'weak' => [5]],
            ['name' => 'Kristine Lumapas', 'gender' => 'Female', 'program' => 'BSBA', 'year' => '4th Year', 'section' => 'A', 'ojt' => 'ongoing', 'strengths' => [4, 7, 6], 'weak' => [2]],
            ['name' => 'Paolo Montecillo', 'gender' => 'Male', 'program' => 'BSBA', 'year' => '4th Year', 'section' => 'B', 'ojt' => 'completed_cleared', 'strengths' => [3, 8, 0], 'weak' => [5, 6]],
            ['name' => 'Angelica Labrador', 'gender' => 'Female', 'program' => 'BSMA', 'year' => '4th Year', 'section' => 'A', 'ojt' => 'ongoing', 'strengths' => [4, 7, 1], 'weak' => [8]],
            ['name' => 'Nathan Ybanez', 'gender' => 'Male', 'program' => 'BSMA', 'year' => '3rd Year', 'section' => 'B', 'ojt' => 'pending_validation', 'strengths' => [0, 3], 'weak' => [6]],
        ];
    }

    /**
     * 10 more students (2 per department) who are not connected to any employer: no application, no placement,
     * no invitation. Each has 5 certificates and 6 skills of their own: no title and no skill repeats between them,
     * or with the 10 students above.
     *
     * @return list<array<string, mixed>>
     */
    public static function freeStudents(): array
    {
        $cert = CertificateType::Certificate;
        $train = CertificateType::Training;
        $semi = CertificateType::Seminar;
        $shop = CertificateType::Workshop;
        $tech = SkillType::Technical;
        $soft = SkillType::Soft;
        $beg = ProficiencyLevel::Beginner;
        $mid = ProficiencyLevel::Intermediate;
        $adv = ProficiencyLevel::Advanced;

        return [
            ['name' => 'Lance Dumalagan', 'gender' => 'Male', 'program' => 'BSIT', 'year' => '4th Year', 'section' => 'C', 'ojt' => 'none', 'strengths' => [0, 2, 3], 'weak' => [5],
                'skills' => ['React', 'Node.js', 'HTML and CSS', 'Figma', 'REST APIs', 'Docker'],
                'certs' => [
                    ['React Front-End Development Course', $cert, 'Udemy', $tech, $mid, ['React', 'Components']],
                    ['Node.js and REST API Bootcamp', $train, 'Coursera', $tech, $mid, ['Node.js', 'API Design']],
                    ['UI/UX Design with Figma', $shop, 'Google Developer Student Club Cebu', $tech, $beg, ['Figma', 'Prototyping']],
                    ['Docker and DevOps Fundamentals', $train, 'The Linux Foundation', $tech, $beg, ['Containers', 'Deployment']],
                    ['Agile and Scrum Seminar', $semi, 'Philippine Software Industry Association', $soft, $mid, ['Scrum', 'Sprint Planning']],
                ]],
            ['name' => 'Trisha Caballero', 'gender' => 'Female', 'program' => 'BSIT', 'year' => '3rd Year', 'section' => 'D', 'ojt' => 'none', 'strengths' => [1, 4, 6], 'weak' => [8],
                'skills' => ['Java', 'C Sharp', 'Android Studio', 'Linux', 'Computer Networking', 'Cybersecurity'],
                'certs' => [
                    ['Java Programming Masterclass', $cert, 'Oracle Academy', $tech, $adv, ['Java', 'Object-Oriented Design']],
                    ['Android App Development Workshop', $shop, 'Google Developer Group Cebu', $tech, $mid, ['Android Studio', 'Mobile UI']],
                    ['Network Fundamentals (CCNA 1)', $train, 'Cisco Networking Academy', $tech, $mid, ['IP Addressing', 'Switching']],
                    ['Ethical Hacking Basics Seminar', $semi, 'DICT Region VII', $tech, $beg, ['Penetration Testing', 'Security Tools']],
                    ['Linux Administration Essentials', $cert, 'Linux Professional Institute', $tech, $mid, ['Linux', 'Shell Scripting']],
                ]],
            ['name' => 'Jasmine Alcoseba', 'gender' => 'Female', 'program' => 'BSHM', 'year' => '4th Year', 'section' => 'C', 'ojt' => 'none', 'strengths' => [5, 6, 9], 'weak' => [1],
                'skills' => ['Front Office Operations', 'Barista Skills', 'Table Service', 'Housekeeping', 'Cashiering', 'Menu Planning'],
                'certs' => [
                    ['Front Office Services NC II', $cert, 'TESDA Cebu', $tech, $mid, ['Reservations', 'Guest Check-in']],
                    ['Barista NC II', $cert, 'TESDA Cebu', $tech, $mid, ['Espresso Preparation', 'Latte Art']],
                    ['Table Service Excellence Training', $train, 'Cebu Restaurant Association', $tech, $mid, ['Table Setting', 'Order Taking']],
                    ['Cashiering and Point-of-Sale Workshop', $shop, 'Philippine Hotel and Restaurant Association', $tech, $beg, ['Cashiering', 'POS Systems']],
                    ['Menu Planning Seminar', $semi, 'Department of Tourism Region VII', $soft, $beg, ['Menu Costing', 'Presentation']],
                ]],
            ['name' => 'Kenneth Rosales', 'gender' => 'Male', 'program' => 'BSHM', 'year' => '3rd Year', 'section' => 'D', 'ojt' => 'none', 'strengths' => [4, 7, 8], 'weak' => [2],
                'skills' => ['Event Planning', 'Catering Management', 'Bartending', 'Guest Relations', 'Inventory Control', 'Banquet Setup'],
                'certs' => [
                    ['Events Management NC III', $cert, 'TESDA Cebu', $tech, $adv, ['Event Planning', 'Budgeting']],
                    ['Catering Services NC II', $cert, 'TESDA Cebu', $tech, $mid, ['Food Preparation', 'Buffet Service']],
                    ['Bartending NC II', $cert, 'TESDA Cebu', $tech, $mid, ['Cocktail Mixing', 'Bar Operations']],
                    ['Guest Relations Training', $train, 'Cebu Hospitality Academy', $soft, $mid, ['Guest Service', 'Complaint Handling']],
                    ['Banquet Setup Workshop', $shop, 'Visayas Events Guild', $tech, $beg, ['Room Layout', 'Linen Handling']],
                ]],
            ['name' => 'Bianca Tinambacan', 'gender' => 'Female', 'program' => 'BSTM', 'year' => '4th Year', 'section' => 'C', 'ojt' => 'none', 'strengths' => [5, 9, 6], 'weak' => [3],
                'skills' => ['Tour Guiding', 'Itinerary Design', 'Ticketing and Reservations', 'Photography', 'Korean Language', 'Heritage Interpretation'],
                'certs' => [
                    ['Tourism Photography Workshop', $shop, 'Visit Cebu Tourism', $tech, $beg, ['Photography', 'Photo Editing']],
                    ['Korean Language Level 1', $cert, 'King Sejong Institute Cebu', $soft, $beg, ['Korean', 'Basic Conversation']],
                    ['Ticketing and Reservations Training', $train, 'Amadeus Philippines', $tech, $mid, ['Airline Ticketing', 'Booking Systems']],
                    ['Heritage Interpretation Seminar', $semi, 'National Museum Cebu', $soft, $mid, ['Local History', 'Storytelling']],
                    ['Itinerary Design Workshop', $shop, 'Cebu Provincial Tourism Office', $tech, $mid, ['Itinerary Design', 'Costing']],
                ]],
            ['name' => 'Gerard Lavilla', 'gender' => 'Male', 'program' => 'BSTM', 'year' => '3rd Year', 'section' => 'D', 'ojt' => 'none', 'strengths' => [3, 8], 'weak' => [6],
                'skills' => ['Travel Agency Operations', 'Social Media Marketing', 'Video Editing', 'Destination Research', 'First Aid', 'Public Speaking'],
                'certs' => [
                    ['Travel Agency Operations Training', $train, 'Philippine Travel Agencies Association', $tech, $mid, ['Tour Packages', 'Visa Processing']],
                    ['Social Media for Tourism Workshop', $shop, 'Meta Blueprint', $tech, $mid, ['Content Calendar', 'Page Management']],
                    ['Video Editing for Content Creators', $cert, 'Adobe Education Exchange', $tech, $beg, ['Video Editing', 'Storyboarding']],
                    ['Emergency First Aid and BLS Training', $train, 'Philippine Red Cross Cebu', $tech, $mid, ['First Aid', 'CPR']],
                    ['Public Speaking Seminar', $semi, 'Toastmasters Cebu', $soft, $mid, ['Public Speaking', 'Confidence']],
                ]],
            ['name' => 'Camille Dyogi', 'gender' => 'Female', 'program' => 'BSBA', 'year' => '4th Year', 'section' => 'C', 'ojt' => 'none', 'strengths' => [4, 5, 7], 'weak' => [1],
                'skills' => ['Search Engine Optimization', 'Customer Relationship Management', 'Sales Presentation', 'Market Research', 'Graphic Design', 'Google Analytics'],
                'certs' => [
                    ['Search Engine Optimization Certificate', $cert, 'HubSpot Academy', $tech, $mid, ['SEO', 'Keyword Research']],
                    ['Customer Relationship Management Training', $train, 'Salesforce Trailhead', $tech, $mid, ['CRM', 'Lead Tracking']],
                    ['Sales Presentation Skills Workshop', $shop, 'Philippine Marketing Association', $soft, $mid, ['Pitching', 'Closing']],
                    ['Market Research Methods Course', $cert, 'Coursera', $tech, $beg, ['Surveys', 'Data Interpretation']],
                    ['Google Analytics Certification', $cert, 'Google Skillshop', $tech, $mid, ['Web Analytics', 'Reporting']],
                ]],
            ['name' => 'Ivan Nacorda', 'gender' => 'Male', 'program' => 'BSBA', 'year' => '3rd Year', 'section' => 'D', 'ojt' => 'none', 'strengths' => [6, 8, 9], 'weak' => [0],
                'skills' => ['Human Resource Basics', 'Recruitment Support', 'Project Coordination', 'Business Writing', 'Negotiation', 'Microsoft Project'],
                'certs' => [
                    ['Human Resource Management Basics', $cert, 'People Management Association of the Philippines', $tech, $mid, ['HR Policies', 'Onboarding']],
                    ['Recruitment and Selection Seminar', $semi, 'Cebu HR Practitioners Network', $soft, $mid, ['Interviewing', 'Screening']],
                    ['Project Coordination Essentials', $train, 'PMI Philippines Chapter', $tech, $mid, ['Scheduling', 'Stakeholder Updates']],
                    ['Business Writing Workshop', $shop, 'Philippine Institute of Management', $soft, $mid, ['Memos', 'Reports']],
                    ['Negotiation Skills Training', $train, 'Ateneo Graduate School of Business', $soft, $beg, ['Negotiation', 'Persuasion']],
                ]],
            ['name' => 'Lovely Pacaldo', 'gender' => 'Female', 'program' => 'BSMA', 'year' => '4th Year', 'section' => 'C', 'ojt' => 'none', 'strengths' => [4, 7, 1], 'weak' => [5],
                'skills' => ['Ledger Management', 'Financial Statements', 'Payroll Processing', 'QuickBooks', 'Tax Compliance', 'Auditing Basics'],
                'certs' => [
                    ['Ledger Management Training', $train, 'Philippine Institute of Certified Public Accountants', $tech, $mid, ['General Ledger', 'Trial Balance']],
                    ['Preparing Financial Statements Workshop', $shop, 'Cebu CPA Association', $tech, $mid, ['Balance Sheet', 'Income Statement']],
                    ['Payroll Processing Seminar', $semi, 'Social Security System Cebu', $tech, $beg, ['Payroll', 'Government Contributions']],
                    ['QuickBooks Online Certification', $cert, 'Intuit', $tech, $adv, ['QuickBooks', 'Invoicing']],
                    ['Tax Compliance for Small Business Seminar', $semi, 'Bureau of Internal Revenue Cebu', $tech, $beg, ['Tax Filing', 'Receipts']],
                ]],
            ['name' => 'Ethan Genotiva', 'gender' => 'Male', 'program' => 'BSMA', 'year' => '3rd Year', 'section' => 'D', 'ojt' => 'none', 'strengths' => [0, 3, 8], 'weak' => [6],
                'skills' => ['Cost Accounting', 'Budgeting', 'Inventory Costing', 'Excel Macros', 'Financial Analysis', 'Accounts Payable'],
                'certs' => [
                    ['Cost Accounting Fundamentals', $cert, 'Philippine Institute of Certified Management Accountants', $tech, $mid, ['Cost Sheets', 'Variance Analysis']],
                    ['Budgeting and Forecasting Workshop', $shop, 'Cebu Chamber of Commerce and Industry', $tech, $mid, ['Budgets', 'Forecasts']],
                    ['Excel Macros and VBA Certificate', $cert, 'Microsoft Learn', $tech, $adv, ['VBA', 'Automation']],
                    ['Financial Analysis Essentials', $train, 'Corporate Finance Institute', $tech, $mid, ['Ratios', 'Trend Analysis']],
                    ['Accounts Payable Training', $train, 'Visayas Accountants Guild', $tech, $beg, ['Invoices', 'Vendor Payments']],
                ]],
        ];
    }

    /**
     * 10 more students for every department (50), not connected to any employer. Each has 5 certificates and 6 skills
     * picked from what that department's students have, in a different combination for every student: no two students
     * of a department share the same set of certificates or the same set of skills.
     *
     * @return list<array<string, mixed>>
     */
    public static function bulkStudents(): array
    {
        $names = [
            'BSIT' => ['Aliyah Abapo', 'Jomar Bacarisas', 'Danica Cenabre', 'Rommel Dumdum', 'Shaira Echavez', 'Carlo Fuentes', 'Nicole Gerodias', 'Rey Hontiveros', 'Mariel Ibarra', 'Dante Jabonillo'],
            'BSHM' => ['Katrina Kintanar', 'Alvin Labasano', 'Jenny Maglinte', 'Cedric Navarra', 'Pauline Ogoc', 'Marvin Pandac', 'Leah Quimpo', 'Joel Rabaca', 'Daisy Sarmiento', 'Eric Tabotabo'],
            'BSTM' => ['Ria Ubaldo', 'Neil Valmores', 'Cherry Wenceslao', 'Jun Yarra', 'Faith Zamora', 'Glenn Abellar', 'Joy Bacus', 'Harold Cabrera', 'Ellen Dacoco', 'Wilson Escalante'],
            'BSBA' => ['Mica Fabillar', 'Allan Gaviola', 'Rina Hermosa', 'Tony Inocian', 'Lyka Jaranilla', 'Bryan Kiamco', 'Sheena Lauron', 'Nelson Magallanes', 'Irish Navales', 'Oscar Pelayo'],
            'BSMA' => ['Dessa Quitoriano', 'Felix Ramos', 'Hannah Salazar', 'Troy Tecson', 'Vanessa Urbina', 'Arvin Villamor', 'Jessa Yap', 'Mark Zaragoza', 'Wendy Alcazar', 'Ronnie Bajao'],
        ];

        $departments = self::departments();
        $free = self::freeStudents();
        $rows = [];
        foreach ($names as $program => $list) {
            // What the department's students already have: the pool each new student picks from.
            $certificatePool = $departments[$program]['certificates'];
            $skillPool = $departments[$program]['skills'];
            foreach ($free as $other) {
                if ($other['program'] === $program) {
                    array_push($certificatePool, ...$other['certs']);
                    array_push($skillPool, ...$other['skills']);
                }
            }

            $usedCertificateSets = [];
            $usedSkillSets = [];
            foreach ($list as $position => $name) {
                $pick = function (array $pool, int $count, array &$used, callable $key) use ($name) {
                    for ($salt = 0; ; $salt++) {
                        $sorted = $pool;
                        usort($sorted, fn ($a, $b) => crc32($name.$salt.$key($a)) <=> crc32($name.$salt.$key($b)));
                        $chosen = array_slice($sorted, 0, $count);
                        $signature = implode('|', array_map($key, $chosen));
                        $keys = array_map($key, $chosen);
                        sort($keys);
                        $set = implode('|', $keys);
                        if (! in_array($set, $used, true)) {
                            $used[] = $set;

                            return $chosen;
                        }
                    }
                };

                $hash = abs(crc32($name));
                $rows[] = [
                    'name' => $name,
                    'gender' => $position % 2 === 0 ? 'Female' : 'Male',
                    'program' => $program,
                    'year' => $position % 3 === 0 ? '3rd Year' : '4th Year',
                    'section' => ['A', 'B', 'C', 'D'][$position % 4],
                    'ojt' => 'none',
                    'strengths' => [$hash % 10, ($hash >> 4) % 10],
                    'weak' => [($hash >> 8) % 10],
                    'certs' => $pick($certificatePool, 5, $usedCertificateSets, fn ($certificate) => $certificate[0]),
                    'skills' => $pick($skillPool, 6, $usedSkillSets, fn ($skill) => $skill),
                ];
            }
        }

        return $rows;
    }

    /** Places in Cebu for the students' addresses: [city, barangay]. */
    public static function localities(): array
    {
        return [['Talisay City', 'Tabunok'], ['Minglanilla', 'Tunghaan'], ['Cebu City', 'Guadalupe'], ['Mandaue City', 'Subangdaku'], ['Lapu-Lapu City', 'Pajo'], ['Naga City', 'Colon'], ['Cebu City', 'Banilad'], ['Consolacion', 'Cansaga'], ['Liloan', 'Yati'], ['Carcar City', 'Poblacion']];
    }

    public static function schools(): array
    {
        return [
            ['Cebu City Central Elementary School', 'Cebu City National Science High School'],
            ['Talisay City Pilot Elementary School', 'Talisay City National High School'],
            ['Minglanilla Central School', 'Minglanilla National High School'],
            ['Mandaue City Central Elementary School', 'Mandaue City Comprehensive National High School'],
            ['Lapu-Lapu City Central School', 'Mactan National High School'],
        ];
    }

    /** Short comments the supervisor and student write on the monthly evaluations. */
    public static function comments(): array
    {
        return [
            'supervisor' => [
                'Dependable and eager to learn. Delivers tasks on time and asks the right questions.',
                'Shows good initiative and a positive attitude. Needs a bit more confidence when presenting work.',
                'Consistently produces careful work and gets along well with the team.',
                'Improved a lot this month. Keep building speed without losing accuracy.',
            ],
            'student' => [
                'I learned a lot from my supervisor and my teammates this month.',
                'I became more confident in handling tasks on my own.',
                'I want to improve my time management and keep learning new tools.',
                'Thank you for the feedback. I will keep working on it.',
            ],
        ];
    }

    public static function slug(string $text): string
    {
        return trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower(\Illuminate\Support\Str::ascii($text))), '-');
    }

    /** firstname.lastname@gmail.com: looks like a real account. The seeder switches the system's emails to it off. */
    public static function email(string $name): string
    {
        return self::localPart($name).'@gmail.com';
    }

    /** The address these accounts had before (firstname.lastname@example.com); the seeder renames them. */
    public static function legacyEmail(string $name): string
    {
        return self::localPart($name).'@example.com';
    }

    private static function localPart(string $name): string
    {
        $parts = explode(' ', strtolower(\Illuminate\Support\Str::ascii($name)));

        return preg_replace('/[^a-z.]/', '', $parts[0].'.'.end($parts));
    }

    /** A +63 mobile number that is the same for the same person every time. */
    public static function phone(string $name): string
    {
        $n = abs(crc32($name));

        return sprintf('09%02d%03d%04d', 17 + $n % 10, ($n >> 4) % 1000, ($n >> 12) % 10000);
    }
}
