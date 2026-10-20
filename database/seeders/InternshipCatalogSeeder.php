<?php

namespace Database\Seeders;

use App\Enums\ProficiencyLevel;
use App\Enums\UserRole;
use App\Enums\WorkSetup;
use App\Models\Employer;
use App\Models\Internship;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Ten internship postings per academic program (BSIT, BSHM, BSTM, BSBA,
 * BSMA), each under a fitting partner company.
 * Safe to re-run: companies and postings are matched by email and title.
 */
class InternshipCatalogSeeder extends Seeder
{
    public function run(): void
    {
        // The original demo postings are IT roles.
        $bsitId = Program::where('code', 'BSIT')->value('id');
        if ($bsitId) {
            $originals = Internship::whereIn('title', ['Web Developer Intern', 'UI/UX Design Intern'])
                ->whereDoesntHave('programs')
                ->get();
            foreach ($originals as $internship) {
                $internship->programs()->attach($bsitId);
            }
        }

        foreach ($this->catalog() as $company) {
            $employer = $this->employer($company['employer']);

            foreach ($company['internships'] as $posting) {
                $internship = Internship::withTrashed()->firstOrCreate(
                    ['employer_id' => $employer->id, 'title' => $posting['title']],
                    [
                        'description' => $posting['description'],
                        'responsibilities' => implode("\n", $posting['responsibilities']),
                        'requirements' => $posting['requirements'],
                        'duration' => $posting['duration'],
                        'allowance' => $posting['allowance'],
                        'work_setup' => $posting['work_setup'],
                        'location' => $posting['location'],
                        'status' => 'open',
                    ]
                );

                $programIds = Program::whereIn('code', $posting['programs'])->pluck('id');
                if ($internship->wasRecentlyCreated || $internship->programs()->doesntExist()) {
                    $internship->programs()->syncWithoutDetaching($programIds);
                }

                if ($internship->wasRecentlyCreated) {
                    foreach ($posting['skills'] as [$name, $level]) {
                        $internship->requirementsList()->create([
                            'requirement_name' => $name,
                            'required_level' => $level,
                        ]);
                    }
                }
            }
        }
    }

    private function employer(array $data): Employer
    {
        $user = User::withTrashed()->firstOrCreate(
            ['email' => $data['email']],
            [
                'name' => $data['contact_person'],
                'password' => 'password',
                'role' => UserRole::Employer,
            ]
        );

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return Employer::firstOrCreate(
            ['user_id' => $user->id],
            collect($data)->except('email')->all()
        );
    }

    private function catalog(): array
    {
        return [
            // ---------------------------------------------------------------
            // BSIT — TechNova Solutions (5) + ByteForge Technologies (5)
            // ---------------------------------------------------------------
            [
                'employer' => [
                    'email' => 'employer@skillbridge.test',
                    'company_name' => 'TechNova Solutions',
                    'industry' => 'Information Technology',
                    'contact_person' => 'John Reyes',
                    'address' => '456 Business Park, Makati',
                ],
                'internships' => [
                    [
                        'title' => 'IT Support and Network Intern',
                        'programs' => ['BSIT'],
                        'description' => 'For BS Information Technology students. Help the IT team keep office systems, networks and user accounts running.',
                        'responsibilities' => ['Resolve help-desk tickets', 'Set up workstations and network devices', 'Document fixes in the knowledge base'],
                        'requirements' => 'BS Information Technology, 4th year. Basic networking and troubleshooting.',
                        'duration' => '3 months',
                        'allowance' => 7000,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Makati City',
                        'skills' => [['Networking', ProficiencyLevel::Beginner], ['Technical Support', ProficiencyLevel::Beginner], ['Communication', ProficiencyLevel::Intermediate]],
                    ],
                    [
                        'title' => 'Mobile App Developer Intern',
                        'programs' => ['BSIT'],
                        'description' => 'For BS Information Technology students. Build features for our customer mobile app.',
                        'responsibilities' => ['Implement screens and API integrations', 'Fix bugs reported by QA', 'Join daily stand-ups'],
                        'requirements' => 'BS Information Technology, 4th year. Knowledge of JavaScript or Flutter.',
                        'duration' => '3 months',
                        'allowance' => 8000,
                        'work_setup' => WorkSetup::Hybrid,
                        'location' => 'Makati City',
                        'skills' => [['JavaScript', ProficiencyLevel::Intermediate], ['Mobile Development', ProficiencyLevel::Beginner], ['Git Version Control', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'Backend Developer Intern',
                        'programs' => ['BSIT'],
                        'description' => 'For BS Information Technology students. Build and maintain API services behind our web products.',
                        'responsibilities' => ['Build REST API endpoints', 'Write database migrations and seeders', 'Add automated tests'],
                        'requirements' => 'BS Information Technology, 4th year. Knowledge of PHP or Node.js and SQL.',
                        'duration' => '3 months',
                        'allowance' => 8000,
                        'work_setup' => WorkSetup::Hybrid,
                        'location' => 'Makati City',
                        'skills' => [['PHP', ProficiencyLevel::Intermediate], ['MySQL', ProficiencyLevel::Beginner], ['REST API Development', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'QA Tester Intern',
                        'programs' => ['BSIT'],
                        'description' => 'For BS Information Technology students. Test new features before release and report issues.',
                        'responsibilities' => ['Write and run test cases', 'Log and verify bug fixes', 'Help build an automated test suite'],
                        'requirements' => 'BS Information Technology, 4th year. Detail-oriented and curious about edge cases.',
                        'duration' => '3 months',
                        'allowance' => 7000,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Makati City',
                        'skills' => [['Software Testing', ProficiencyLevel::Beginner], ['Attention to Detail', ProficiencyLevel::Intermediate], ['Problem Solving', ProficiencyLevel::Intermediate]],
                    ],
                    [
                        'title' => 'Data Analyst Intern',
                        'programs' => ['BSIT'],
                        'description' => 'For BS Information Technology students. Turn product and sales data into reports the team can act on.',
                        'responsibilities' => ['Build dashboards from raw data', 'Write SQL queries for ad-hoc requests', 'Document data definitions'],
                        'requirements' => 'BS Information Technology, 4th year. Comfortable with SQL and spreadsheets.',
                        'duration' => '3 months',
                        'allowance' => 7500,
                        'work_setup' => WorkSetup::Hybrid,
                        'location' => 'Makati City',
                        'skills' => [['MySQL Database Design', ProficiencyLevel::Beginner], ['Microsoft Excel', ProficiencyLevel::Intermediate], ['Problem Solving', ProficiencyLevel::Intermediate]],
                    ],
                ],
            ],
            [
                'employer' => [
                    'email' => 'careers@byteforge.test',
                    'company_name' => 'ByteForge Technologies',
                    'industry' => 'Information Technology',
                    'contact_person' => 'Patricia Uy',
                    'address' => 'Asiatown IT Park, Cebu City',
                    'description' => 'Software outsourcing company building web and enterprise systems for clients abroad.',
                ],
                'internships' => [
                    [
                        'title' => 'Frontend Developer Intern',
                        'programs' => ['BSIT'],
                        'description' => 'For BS Information Technology students. Build user interfaces for client web applications.',
                        'responsibilities' => ['Implement UI components from design mockups', 'Fix cross-browser issues', 'Pair with senior developers on code review'],
                        'requirements' => 'BS Information Technology, 4th year. Knowledge of HTML, CSS and JavaScript.',
                        'duration' => '3 months',
                        'allowance' => 7500,
                        'work_setup' => WorkSetup::Hybrid,
                        'location' => 'Cebu City',
                        'skills' => [['JavaScript', ProficiencyLevel::Intermediate], ['Communication', ProficiencyLevel::Beginner], ['Git Version Control', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'Systems Administrator Intern',
                        'programs' => ['BSIT'],
                        'description' => 'For BS Information Technology students. Support server maintenance and cloud deployments.',
                        'responsibilities' => ['Monitor server health and uptime', 'Assist with deployments and backups', 'Maintain internal documentation'],
                        'requirements' => 'BS Information Technology, 4th year. Basic Linux and cloud knowledge.',
                        'duration' => '3 months',
                        'allowance' => 7500,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Cebu City',
                        'skills' => [['Networking', ProficiencyLevel::Beginner], ['AWS Cloud Services', ProficiencyLevel::Beginner], ['Technical Support', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'Cybersecurity Intern',
                        'programs' => ['BSIT'],
                        'description' => 'For BS Information Technology students. Assist the security team with basic audits and monitoring.',
                        'responsibilities' => ['Run vulnerability scans', 'Review access logs for anomalies', 'Help update security checklists'],
                        'requirements' => 'BS Information Technology, 4th year. Interest in information security.',
                        'duration' => '3 months',
                        'allowance' => 8000,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Cebu City',
                        'skills' => [['Networking', ProficiencyLevel::Intermediate], ['Problem Solving', ProficiencyLevel::Intermediate], ['Attention to Detail', ProficiencyLevel::Intermediate]],
                    ],
                    [
                        'title' => 'Database Administrator Intern',
                        'programs' => ['BSIT'],
                        'description' => 'For BS Information Technology students. Help maintain and optimize client databases.',
                        'responsibilities' => ['Write and optimize SQL queries', 'Assist with backup and recovery drills', 'Monitor database performance'],
                        'requirements' => 'BS Information Technology, 4th year. Knowledge of relational databases.',
                        'duration' => '3 months',
                        'allowance' => 7500,
                        'work_setup' => WorkSetup::Hybrid,
                        'location' => 'Cebu City',
                        'skills' => [['MySQL Database Design', ProficiencyLevel::Intermediate], ['Database Design', ProficiencyLevel::Beginner], ['Attention to Detail', ProficiencyLevel::Intermediate]],
                    ],
                    [
                        'title' => 'Technical Writer Intern',
                        'programs' => ['BSIT'],
                        'description' => 'For BS Information Technology students. Document features and write user guides for client systems.',
                        'responsibilities' => ['Write and update product documentation', 'Interview developers for accurate details', 'Maintain the internal wiki'],
                        'requirements' => 'BS Information Technology, 4th year. Clear written English.',
                        'duration' => '3 months',
                        'allowance' => 6500,
                        'work_setup' => WorkSetup::Remote,
                        'location' => 'Remote',
                        'skills' => [['Communication', ProficiencyLevel::Advanced], ['Attention to Detail', ProficiencyLevel::Intermediate], ['Project Management', ProficiencyLevel::Beginner]],
                    ],
                ],
            ],

            // ---------------------------------------------------------------
            // BSHM — Cebu Grand Hotel (5) + Marina Bay Resort & Spa (5)
            // ---------------------------------------------------------------
            [
                'employer' => [
                    'email' => 'hr@cebugrandhotel.test',
                    'company_name' => 'Cebu Grand Hotel',
                    'industry' => 'Hospitality',
                    'contact_person' => 'Liza Fernandez',
                    'address' => 'Escario St., Cebu City',
                    'description' => 'Full-service hotel with 220 rooms, restaurants and events facilities.',
                ],
                'internships' => [
                    [
                        'title' => 'Front Office Intern',
                        'programs' => ['BSHM'],
                        'description' => 'For BS Hospitality Management students. Assist the front desk with guest check-in, reservations and concierge requests.',
                        'responsibilities' => ['Check guests in and out', 'Handle reservations in the property system', 'Respond to guest requests'],
                        'requirements' => 'BS Hospitality Management, 4th year. Good English and customer service skills.',
                        'duration' => '600 hours',
                        'allowance' => 5000,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Cebu City',
                        'skills' => [['Customer Service', ProficiencyLevel::Intermediate], ['Communication', ProficiencyLevel::Intermediate], ['Front Office Operations', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'Food and Beverage Service Intern',
                        'programs' => ['BSHM'],
                        'description' => 'For BS Hospitality Management students. Rotate through the hotel restaurant, bar and banquet operations.',
                        'responsibilities' => ['Serve guests following service standards', 'Set up banquet functions', 'Assist with inventory counts'],
                        'requirements' => 'BS Hospitality Management, 4th year. Food safety training is a plus.',
                        'duration' => '600 hours',
                        'allowance' => 5000,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Cebu City',
                        'skills' => [['Food and Beverage Service', ProficiencyLevel::Beginner], ['Teamwork', ProficiencyLevel::Intermediate], ['Customer Service', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'Housekeeping Operations Intern',
                        'programs' => ['BSHM'],
                        'description' => 'For BS Hospitality Management students. Learn room inspection and housekeeping scheduling.',
                        'responsibilities' => ['Inspect rooms against quality checklists', 'Coordinate linen and supply inventory', 'Assist with staff scheduling'],
                        'requirements' => 'BS Hospitality Management, 4th year.',
                        'duration' => '600 hours',
                        'allowance' => 4800,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Cebu City',
                        'skills' => [['Attention to Detail', ProficiencyLevel::Intermediate], ['Teamwork', ProficiencyLevel::Beginner], ['Time Management', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'Hotel Sales and Marketing Intern',
                        'programs' => ['BSHM'],
                        'description' => 'For BS Hospitality Management students. Support room-block sales and social media promotions.',
                        'responsibilities' => ['Prepare sales proposals for corporate clients', 'Draft social media content', 'Track booking promotions'],
                        'requirements' => 'BS Hospitality Management, 4th year.',
                        'duration' => '600 hours',
                        'allowance' => 5000,
                        'work_setup' => WorkSetup::Hybrid,
                        'location' => 'Cebu City',
                        'skills' => [['Communication', ProficiencyLevel::Intermediate], ['Digital Marketing', ProficiencyLevel::Beginner], ['Customer Service', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'Guest Relations Intern',
                        'programs' => ['BSHM'],
                        'description' => 'For BS Hospitality Management students. Handle VIP guest requests and feedback resolution.',
                        'responsibilities' => ['Greet and assist VIP guests', 'Log and follow up on guest feedback', 'Coordinate special requests with other departments'],
                        'requirements' => 'BS Hospitality Management, 4th year. Pleasing personality and patience.',
                        'duration' => '600 hours',
                        'allowance' => 5000,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Cebu City',
                        'skills' => [['Customer Service', ProficiencyLevel::Advanced], ['Communication', ProficiencyLevel::Intermediate], ['Problem Solving', ProficiencyLevel::Beginner]],
                    ],
                ],
            ],
            [
                'employer' => [
                    'email' => 'careers@marinabayresort.test',
                    'company_name' => 'Marina Bay Resort & Spa',
                    'industry' => 'Hospitality',
                    'contact_person' => 'Grace Lopez',
                    'address' => 'Mactan Island, Lapu-Lapu City',
                    'description' => 'Beachfront resort with spa, restaurants and event venues.',
                ],
                'internships' => [
                    [
                        'title' => 'Event Planning and Banquet Intern',
                        'programs' => ['BSHM'],
                        'description' => 'For BS Hospitality Management students. Help plan and run weddings, conferences and resort events.',
                        'responsibilities' => ['Prepare event setups and floor plans', 'Coordinate with suppliers and vendors', 'Assist on event day'],
                        'requirements' => 'BS Hospitality Management, 4th year.',
                        'duration' => '600 hours',
                        'allowance' => 5000,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Lapu-Lapu City',
                        'skills' => [['Event Planning', ProficiencyLevel::Beginner], ['Teamwork', ProficiencyLevel::Intermediate], ['Time Management', ProficiencyLevel::Intermediate]],
                    ],
                    [
                        'title' => 'Culinary Arts Intern',
                        'programs' => ['BSHM'],
                        'description' => 'For BS Hospitality Management students. Rotate through resort kitchens under senior chefs.',
                        'responsibilities' => ['Prepare ingredients following recipes', 'Maintain kitchen sanitation standards', 'Assist during service rush'],
                        'requirements' => 'BS Hospitality Management, 4th year. Food handling certificate is a plus.',
                        'duration' => '600 hours',
                        'allowance' => 5000,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Lapu-Lapu City',
                        'skills' => [['Food and Beverage Service', ProficiencyLevel::Beginner], ['Attention to Detail', ProficiencyLevel::Intermediate], ['Teamwork', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'Spa and Wellness Intern',
                        'programs' => ['BSHM'],
                        'description' => 'For BS Hospitality Management students. Support spa front desk and guest wellness programs.',
                        'responsibilities' => ['Schedule spa appointments', 'Prepare treatment rooms', 'Explain wellness packages to guests'],
                        'requirements' => 'BS Hospitality Management, 4th year.',
                        'duration' => '600 hours',
                        'allowance' => 4800,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Lapu-Lapu City',
                        'skills' => [['Customer Service', ProficiencyLevel::Intermediate], ['Communication', ProficiencyLevel::Beginner], ['Attention to Detail', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'Reservations Intern',
                        'programs' => ['BSHM'],
                        'description' => 'For BS Hospitality Management students. Process room bookings across online and phone channels.',
                        'responsibilities' => ['Process bookings and cancellations', 'Confirm rates and availability', 'Update the property management system'],
                        'requirements' => 'BS Hospitality Management, 4th year. Comfortable with booking software.',
                        'duration' => '600 hours',
                        'allowance' => 4800,
                        'work_setup' => WorkSetup::Hybrid,
                        'location' => 'Lapu-Lapu City',
                        'skills' => [['Attention to Detail', ProficiencyLevel::Intermediate], ['Customer Service', ProficiencyLevel::Beginner], ['Communication', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'Resort Recreation Intern',
                        'programs' => ['BSHM'],
                        'description' => 'For BS Hospitality Management students. Help run pool, beach and guest activity programs.',
                        'responsibilities' => ['Set up and supervise guest activities', 'Maintain recreation equipment', 'Promote daily activity schedules to guests'],
                        'requirements' => 'BS Hospitality Management, 4th year. Comfortable working outdoors.',
                        'duration' => '600 hours',
                        'allowance' => 4800,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Lapu-Lapu City',
                        'skills' => [['Customer Service', ProficiencyLevel::Beginner], ['Teamwork', ProficiencyLevel::Intermediate], ['Communication', ProficiencyLevel::Beginner]],
                    ],
                ],
            ],

            // ---------------------------------------------------------------
            // BSTM — Island Hop Travel & Tours (5) + Visayas Discovery Tours (5)
            // ---------------------------------------------------------------
            [
                'employer' => [
                    'email' => 'careers@islandhoptravel.test',
                    'company_name' => 'Island Hop Travel & Tours',
                    'industry' => 'Tourism',
                    'contact_person' => 'Mark Villanueva',
                    'address' => 'IT Park, Lahug, Cebu City',
                    'description' => 'Travel agency offering domestic and international tour packages.',
                ],
                'internships' => [
                    [
                        'title' => 'Travel Agency Operations Intern',
                        'programs' => ['BSTM'],
                        'description' => 'For BS Tourism Management students. Support ticketing, bookings and client inquiries.',
                        'responsibilities' => ['Prepare travel itineraries and quotations', 'Book flights and hotels', 'Answer client inquiries by phone and chat'],
                        'requirements' => 'BS Tourism Management, 4th year. Familiarity with booking systems is a plus.',
                        'duration' => '500 hours',
                        'allowance' => 4500,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Cebu City',
                        'skills' => [['Travel Booking', ProficiencyLevel::Beginner], ['Customer Service', ProficiencyLevel::Intermediate], ['Communication', ProficiencyLevel::Intermediate]],
                    ],
                    [
                        'title' => 'Tour Coordination and Events Intern',
                        'programs' => ['BSTM', 'BSHM'],
                        'description' => 'For BS Tourism Management students. Help plan and run local tours and travel events.',
                        'responsibilities' => ['Coordinate with tour guides and suppliers', 'Prepare tour materials', 'Assist guests during tours'],
                        'requirements' => 'BS Tourism Management, 4th year. Willing to travel within Cebu.',
                        'duration' => '500 hours',
                        'allowance' => 4500,
                        'work_setup' => WorkSetup::Hybrid,
                        'location' => 'Cebu Province',
                        'skills' => [['Event Planning', ProficiencyLevel::Beginner], ['Tour Guiding', ProficiencyLevel::Beginner], ['Teamwork', ProficiencyLevel::Intermediate]],
                    ],
                    [
                        'title' => 'Destination Marketing Intern',
                        'programs' => ['BSTM'],
                        'description' => 'For BS Tourism Management students. Promote travel packages across social media and partner channels.',
                        'responsibilities' => ['Draft destination content for social media', 'Coordinate with partner resorts for promotions', 'Track campaign engagement'],
                        'requirements' => 'BS Tourism Management, 4th year.',
                        'duration' => '500 hours',
                        'allowance' => 4500,
                        'work_setup' => WorkSetup::Hybrid,
                        'location' => 'Cebu City',
                        'skills' => [['Digital Marketing', ProficiencyLevel::Beginner], ['Communication', ProficiencyLevel::Intermediate], ['Market Research', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'Travel Consultant Intern',
                        'programs' => ['BSTM'],
                        'description' => 'For BS Tourism Management students. Advise walk-in and online clients on travel packages.',
                        'responsibilities' => ['Recommend packages based on client budget and preferences', 'Prepare cost comparisons', 'Process booking paperwork'],
                        'requirements' => 'BS Tourism Management, 4th year. Good interpersonal skills.',
                        'duration' => '500 hours',
                        'allowance' => 4500,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Cebu City',
                        'skills' => [['Customer Service', ProficiencyLevel::Intermediate], ['Travel Booking', ProficiencyLevel::Beginner], ['Communication', ProficiencyLevel::Intermediate]],
                    ],
                    [
                        'title' => 'Airline Ticketing Intern',
                        'programs' => ['BSTM'],
                        'description' => 'For BS Tourism Management students. Learn airline reservation and ticketing procedures.',
                        'responsibilities' => ['Process flight bookings and rebookings', 'Check fare rules and restrictions', 'Assist with refund requests'],
                        'requirements' => 'BS Tourism Management, 4th year. Detail-oriented.',
                        'duration' => '500 hours',
                        'allowance' => 4500,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Cebu City',
                        'skills' => [['Travel Booking', ProficiencyLevel::Beginner], ['Attention to Detail', ProficiencyLevel::Intermediate], ['Customer Service', ProficiencyLevel::Beginner]],
                    ],
                ],
            ],
            [
                'employer' => [
                    'email' => 'careers@visayasdiscovery.test',
                    'company_name' => 'Visayas Discovery Tours',
                    'industry' => 'Tourism',
                    'contact_person' => 'Ramon Castillo',
                    'address' => 'Panglao, Bohol',
                    'description' => 'Tour operator specializing in eco-tourism and island-hopping experiences across the Visayas.',
                ],
                'internships' => [
                    [
                        'title' => 'Ecotourism Program Intern',
                        'programs' => ['BSTM'],
                        'description' => 'For BS Tourism Management students. Support eco-tour planning and community-based tourism programs.',
                        'responsibilities' => ['Assist with eco-tour itinerary planning', 'Coordinate with local community guides', 'Help monitor visitor impact'],
                        'requirements' => 'BS Tourism Management, 4th year. Interest in sustainable tourism.',
                        'duration' => '500 hours',
                        'allowance' => 4500,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Panglao, Bohol',
                        'skills' => [['Tour Guiding', ProficiencyLevel::Beginner], ['Communication', ProficiencyLevel::Intermediate], ['Teamwork', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'Cruise and Island-Hopping Intern',
                        'programs' => ['BSTM'],
                        'description' => 'For BS Tourism Management students. Support daily island-hopping tour operations.',
                        'responsibilities' => ['Prepare boat manifests and safety checks', 'Brief guests before departure', 'Assist with onboard guest service'],
                        'requirements' => 'BS Tourism Management, 4th year. Comfortable on water-based tours.',
                        'duration' => '500 hours',
                        'allowance' => 4500,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Panglao, Bohol',
                        'skills' => [['Tour Guiding', ProficiencyLevel::Beginner], ['Customer Service', ProficiencyLevel::Intermediate], ['Attention to Detail', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'Visitor Center and Information Intern',
                        'programs' => ['BSTM'],
                        'description' => 'For BS Tourism Management students. Staff the visitor information desk and assist walk-in tourists.',
                        'responsibilities' => ['Answer visitor questions about local attractions', 'Distribute maps and brochures', 'Log daily visitor statistics'],
                        'requirements' => 'BS Tourism Management, 4th year.',
                        'duration' => '500 hours',
                        'allowance' => 4200,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Panglao, Bohol',
                        'skills' => [['Customer Service', ProficiencyLevel::Intermediate], ['Communication', ProficiencyLevel::Intermediate], ['Attention to Detail', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'Adventure Tourism Intern',
                        'programs' => ['BSTM'],
                        'description' => 'For BS Tourism Management students. Support outdoor adventure tour logistics and guest safety briefings.',
                        'responsibilities' => ['Prepare adventure tour equipment checklists', 'Assist with guest safety briefings', 'Coordinate transport schedules'],
                        'requirements' => 'BS Tourism Management, 4th year. Physically fit for outdoor activities.',
                        'duration' => '500 hours',
                        'allowance' => 4500,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Panglao, Bohol',
                        'skills' => [['Tour Guiding', ProficiencyLevel::Beginner], ['Teamwork', ProficiencyLevel::Intermediate], ['Problem Solving', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'Tourism Research and Planning Intern',
                        'programs' => ['BSTM'],
                        'description' => 'For BS Tourism Management students. Help gather visitor feedback and analyze tour performance.',
                        'responsibilities' => ['Conduct guest satisfaction surveys', 'Summarize feedback into reports', 'Support new package research'],
                        'requirements' => 'BS Tourism Management, 4th year. Comfortable with basic data analysis.',
                        'duration' => '500 hours',
                        'allowance' => 4500,
                        'work_setup' => WorkSetup::Hybrid,
                        'location' => 'Panglao, Bohol',
                        'skills' => [['Market Research', ProficiencyLevel::Beginner], ['Microsoft Excel', ProficiencyLevel::Beginner], ['Attention to Detail', ProficiencyLevel::Intermediate]],
                    ],
                ],
            ],

            // ---------------------------------------------------------------
            // BSBA — Visayas Retail Group (5) + Metro Business Solutions (5)
            // ---------------------------------------------------------------
            [
                'employer' => [
                    'email' => 'talent@visayasretail.test',
                    'company_name' => 'Visayas Retail Group',
                    'industry' => 'Retail and Distribution',
                    'contact_person' => 'Carmela Tan',
                    'address' => 'North Reclamation Area, Cebu City',
                    'description' => 'Regional retail chain with 40 stores and a central distribution hub.',
                ],
                'internships' => [
                    [
                        'title' => 'Marketing Assistant Intern',
                        'programs' => ['BSBA'],
                        'description' => 'For BS Business Administration students majoring in Marketing Management. Support campaigns and social media.',
                        'responsibilities' => ['Draft social media posts', 'Track campaign results', 'Help organize in-store promotions'],
                        'requirements' => 'BS Business Administration, Marketing Management major, 4th year.',
                        'duration' => '3 months',
                        'allowance' => 6000,
                        'work_setup' => WorkSetup::Hybrid,
                        'location' => 'Cebu City',
                        'skills' => [['Digital Marketing', ProficiencyLevel::Beginner], ['Communication', ProficiencyLevel::Intermediate], ['Market Research', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'Human Resources Intern',
                        'programs' => ['BSBA'],
                        'description' => 'For BS Business Administration students majoring in Human Resource Management. Assist with recruitment and employee records.',
                        'responsibilities' => ['Screen applications and schedule interviews', 'Update employee files', 'Help prepare onboarding materials'],
                        'requirements' => 'BS Business Administration, Human Resource Management major, 4th year.',
                        'duration' => '3 months',
                        'allowance' => 6000,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Cebu City',
                        'skills' => [['Recruitment', ProficiencyLevel::Beginner], ['Microsoft Excel', ProficiencyLevel::Intermediate], ['Communication', ProficiencyLevel::Intermediate]],
                    ],
                    [
                        'title' => 'Operations and Supply Chain Intern',
                        'programs' => ['BSBA'],
                        'description' => 'For BS Business Administration students majoring in Operations or Financial Management. Support the distribution hub.',
                        'responsibilities' => ['Monitor inventory levels', 'Prepare delivery and cost reports', 'Suggest process improvements'],
                        'requirements' => 'BS Business Administration, Operations or Financial Management major, 4th year.',
                        'duration' => '3 months',
                        'allowance' => 6000,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Mandaue City',
                        'skills' => [['Inventory Management', ProficiencyLevel::Beginner], ['Microsoft Excel', ProficiencyLevel::Intermediate], ['Problem Solving', ProficiencyLevel::Intermediate]],
                    ],
                    [
                        'title' => 'Sales Support Intern',
                        'programs' => ['BSBA'],
                        'description' => 'For BS Business Administration students. Support the sales team with reporting and store coordination.',
                        'responsibilities' => ['Prepare weekly sales reports', 'Coordinate stock requests with stores', 'Track sales targets per branch'],
                        'requirements' => 'BS Business Administration, 4th year.',
                        'duration' => '3 months',
                        'allowance' => 6000,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Cebu City',
                        'skills' => [['Microsoft Excel', ProficiencyLevel::Intermediate], ['Communication', ProficiencyLevel::Beginner], ['Attention to Detail', ProficiencyLevel::Intermediate]],
                    ],
                    [
                        'title' => 'Administrative Services Intern',
                        'programs' => ['BSBA'],
                        'description' => 'For BS Business Administration students. Support the head office with documentation and scheduling.',
                        'responsibilities' => ['Organize and file company records', 'Schedule meetings and prepare minutes', 'Assist with procurement paperwork'],
                        'requirements' => 'BS Business Administration, 4th year. Organized and reliable.',
                        'duration' => '3 months',
                        'allowance' => 5500,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Cebu City',
                        'skills' => [['Microsoft Excel', ProficiencyLevel::Beginner], ['Attention to Detail', ProficiencyLevel::Intermediate], ['Communication', ProficiencyLevel::Beginner]],
                    ],
                ],
            ],
            [
                'employer' => [
                    'email' => 'careers@metrobusiness.test',
                    'company_name' => 'Metro Business Solutions',
                    'industry' => 'Business Services',
                    'contact_person' => 'Daniel Ocampo',
                    'address' => 'Ayala Business Park, Cebu City',
                    'description' => 'Business process outsourcing and consulting firm serving local and international clients.',
                ],
                'internships' => [
                    [
                        'title' => 'Business Development Intern',
                        'programs' => ['BSBA'],
                        'description' => 'For BS Business Administration students. Support client prospecting and proposal preparation.',
                        'responsibilities' => ['Research prospective clients', 'Prepare proposal drafts', 'Track outreach activity'],
                        'requirements' => 'BS Business Administration, 4th year.',
                        'duration' => '3 months',
                        'allowance' => 6000,
                        'work_setup' => WorkSetup::Hybrid,
                        'location' => 'Cebu City',
                        'skills' => [['Market Research', ProficiencyLevel::Beginner], ['Communication', ProficiencyLevel::Intermediate], ['Project Management', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'Logistics Coordination Intern',
                        'programs' => ['BSBA'],
                        'description' => 'For BS Business Administration students majoring in Operations Management. Support client logistics projects.',
                        'responsibilities' => ['Track shipment schedules', 'Prepare logistics cost reports', 'Coordinate with third-party couriers'],
                        'requirements' => 'BS Business Administration, Operations Management major, 4th year.',
                        'duration' => '3 months',
                        'allowance' => 6000,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Cebu City',
                        'skills' => [['Inventory Management', ProficiencyLevel::Beginner], ['Microsoft Excel', ProficiencyLevel::Intermediate], ['Problem Solving', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'Customer Relations Intern',
                        'programs' => ['BSBA'],
                        'description' => 'For BS Business Administration students. Support client account coordination and issue resolution.',
                        'responsibilities' => ['Respond to client account inquiries', 'Escalate unresolved issues', 'Update account records'],
                        'requirements' => 'BS Business Administration, 4th year. Good communication skills.',
                        'duration' => '3 months',
                        'allowance' => 6000,
                        'work_setup' => WorkSetup::Hybrid,
                        'location' => 'Cebu City',
                        'skills' => [['Customer Service', ProficiencyLevel::Intermediate], ['Communication', ProficiencyLevel::Intermediate], ['Problem Solving', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'Strategic Planning Intern',
                        'programs' => ['BSBA'],
                        'description' => 'For BS Business Administration students. Support management with research for strategic initiatives.',
                        'responsibilities' => ['Gather market and competitor data', 'Prepare summary presentations', 'Assist with project timelines'],
                        'requirements' => 'BS Business Administration, 4th year. Strong analytical thinking.',
                        'duration' => '3 months',
                        'allowance' => 6500,
                        'work_setup' => WorkSetup::Hybrid,
                        'location' => 'Cebu City',
                        'skills' => [['Market Research', ProficiencyLevel::Beginner], ['Project Management', ProficiencyLevel::Beginner], ['Problem Solving', ProficiencyLevel::Intermediate]],
                    ],
                    [
                        'title' => 'Procurement Intern',
                        'programs' => ['BSBA'],
                        'description' => 'For BS Business Administration students majoring in Operations or Financial Management. Support vendor sourcing and purchase orders.',
                        'responsibilities' => ['Request and compare supplier quotations', 'Prepare purchase orders', 'Track delivery timelines'],
                        'requirements' => 'BS Business Administration, Operations or Financial Management major, 4th year.',
                        'duration' => '3 months',
                        'allowance' => 6000,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Cebu City',
                        'skills' => [['Microsoft Excel', ProficiencyLevel::Intermediate], ['Attention to Detail', ProficiencyLevel::Intermediate], ['Communication', ProficiencyLevel::Beginner]],
                    ],
                ],
            ],

            // ---------------------------------------------------------------
            // BSMA — Reyes & Co. CPAs (5) + Cebu Audit & Tax Advisors (5)
            // ---------------------------------------------------------------
            [
                'employer' => [
                    'email' => 'internships@reyesco-cpas.test',
                    'company_name' => 'Reyes & Co. CPAs',
                    'industry' => 'Accounting and Audit',
                    'contact_person' => 'Antonio Reyes',
                    'address' => 'Osmeña Blvd., Cebu City',
                    'description' => 'Accounting firm providing audit, tax and advisory services to local businesses.',
                ],
                'internships' => [
                    [
                        'title' => 'Accounting and Audit Intern',
                        'programs' => ['BSMA', 'BSBA'],
                        'description' => 'For BS Management Accounting students. Assist the audit and tax teams with client engagements.',
                        'responsibilities' => ['Prepare working papers', 'Reconcile accounts and vouch transactions', 'Assist with tax filings'],
                        'requirements' => 'BS Management Accounting, 4th year. Strong Excel skills.',
                        'duration' => '3 months',
                        'allowance' => 6500,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Cebu City',
                        'skills' => [['Bookkeeping', ProficiencyLevel::Intermediate], ['Microsoft Excel', ProficiencyLevel::Intermediate], ['Attention to Detail', ProficiencyLevel::Intermediate]],
                    ],
                    [
                        'title' => 'Internal Audit Intern',
                        'programs' => ['BSMA'],
                        'description' => 'For BS Management Accounting students. Support internal control testing for client engagements.',
                        'responsibilities' => ['Test internal control samples', 'Document audit findings', 'Assist with walkthrough interviews'],
                        'requirements' => 'BS Management Accounting, 4th year.',
                        'duration' => '3 months',
                        'allowance' => 6500,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Cebu City',
                        'skills' => [['Attention to Detail', ProficiencyLevel::Advanced], ['Bookkeeping', ProficiencyLevel::Intermediate], ['Problem Solving', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'Tax Compliance Intern',
                        'programs' => ['BSMA'],
                        'description' => 'For BS Management Accounting students. Assist with BIR filings and tax compliance reviews.',
                        'responsibilities' => ['Prepare tax computation worksheets', 'Organize supporting documents for filings', 'Track compliance deadlines'],
                        'requirements' => 'BS Management Accounting, 4th year. Familiarity with Philippine tax forms is a plus.',
                        'duration' => '3 months',
                        'allowance' => 6500,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Cebu City',
                        'skills' => [['Bookkeeping', ProficiencyLevel::Intermediate], ['Attention to Detail', ProficiencyLevel::Intermediate], ['Microsoft Excel', ProficiencyLevel::Intermediate]],
                    ],
                    [
                        'title' => 'Payroll Processing Intern',
                        'programs' => ['BSMA'],
                        'description' => 'For BS Management Accounting students. Support payroll computation for client companies.',
                        'responsibilities' => ['Compute payroll deductions and contributions', 'Reconcile payroll records', 'Prepare payslip reports'],
                        'requirements' => 'BS Management Accounting, 4th year.',
                        'duration' => '3 months',
                        'allowance' => 6000,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Cebu City',
                        'skills' => [['Microsoft Excel', ProficiencyLevel::Intermediate], ['Attention to Detail', ProficiencyLevel::Intermediate], ['Bookkeeping', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'Financial Analysis Intern',
                        'programs' => ['BSMA'],
                        'description' => 'For BS Management Accounting students. Help prepare financial statement analysis for client advisory reports.',
                        'responsibilities' => ['Prepare financial ratio analysis', 'Summarize trends in client financial statements', 'Support advisory report drafts'],
                        'requirements' => 'BS Management Accounting, 4th year. Comfortable reading financial statements.',
                        'duration' => '3 months',
                        'allowance' => 6500,
                        'work_setup' => WorkSetup::Hybrid,
                        'location' => 'Cebu City',
                        'skills' => [['Microsoft Excel', ProficiencyLevel::Advanced], ['Attention to Detail', ProficiencyLevel::Intermediate], ['Problem Solving', ProficiencyLevel::Intermediate]],
                    ],
                ],
            ],
            [
                'employer' => [
                    'email' => 'careers@cebuaudittax.test',
                    'company_name' => 'Cebu Audit & Tax Advisors',
                    'industry' => 'Accounting and Audit',
                    'contact_person' => 'Evelyn Chua',
                    'address' => 'Juan Luna Ave., Cebu City',
                    'description' => 'Independent audit and tax advisory firm serving small and medium enterprises.',
                ],
                'internships' => [
                    [
                        'title' => 'Cost Accounting Intern',
                        'programs' => ['BSMA'],
                        'description' => 'For BS Management Accounting students. Support cost tracking and variance analysis for manufacturing clients.',
                        'responsibilities' => ['Record production costs', 'Prepare cost variance reports', 'Assist with inventory costing'],
                        'requirements' => 'BS Management Accounting, 4th year.',
                        'duration' => '3 months',
                        'allowance' => 6200,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Cebu City',
                        'skills' => [['Bookkeeping', ProficiencyLevel::Intermediate], ['Microsoft Excel', ProficiencyLevel::Intermediate], ['Attention to Detail', ProficiencyLevel::Intermediate]],
                    ],
                    [
                        'title' => 'Bookkeeping Services Intern',
                        'programs' => ['BSMA'],
                        'description' => 'For BS Management Accounting students. Maintain books of accounts for small business clients.',
                        'responsibilities' => ['Record daily transactions in the ledger', 'Reconcile bank statements', 'Prepare monthly trial balances'],
                        'requirements' => 'BS Management Accounting, 4th year.',
                        'duration' => '3 months',
                        'allowance' => 6000,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Cebu City',
                        'skills' => [['Bookkeeping', ProficiencyLevel::Advanced], ['Attention to Detail', ProficiencyLevel::Intermediate], ['Microsoft Excel', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'Budget Analyst Intern',
                        'programs' => ['BSMA'],
                        'description' => 'For BS Management Accounting students. Assist clients with annual budget preparation and tracking.',
                        'responsibilities' => ['Consolidate department budget requests', 'Track actual spending against budget', 'Flag variances for review'],
                        'requirements' => 'BS Management Accounting, 4th year.',
                        'duration' => '3 months',
                        'allowance' => 6200,
                        'work_setup' => WorkSetup::Hybrid,
                        'location' => 'Cebu City',
                        'skills' => [['Microsoft Excel', ProficiencyLevel::Intermediate], ['Attention to Detail', ProficiencyLevel::Intermediate], ['Problem Solving', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'Accounts Payable and Receivable Intern',
                        'programs' => ['BSMA'],
                        'description' => 'For BS Management Accounting students. Process client billing and vendor payments.',
                        'responsibilities' => ['Process vendor invoices for payment', 'Follow up on outstanding receivables', 'Reconcile subsidiary ledgers'],
                        'requirements' => 'BS Management Accounting, 4th year.',
                        'duration' => '3 months',
                        'allowance' => 6000,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Cebu City',
                        'skills' => [['Bookkeeping', ProficiencyLevel::Intermediate], ['Attention to Detail', ProficiencyLevel::Intermediate], ['Communication', ProficiencyLevel::Beginner]],
                    ],
                    [
                        'title' => 'Treasury and Cash Management Intern',
                        'programs' => ['BSMA'],
                        'description' => 'For BS Management Accounting students. Support daily cash position monitoring for client accounts.',
                        'responsibilities' => ['Track daily cash positions', 'Prepare cash flow summaries', 'Assist with bank reconciliations'],
                        'requirements' => 'BS Management Accounting, 4th year.',
                        'duration' => '3 months',
                        'allowance' => 6200,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Cebu City',
                        'skills' => [['Bookkeeping', ProficiencyLevel::Intermediate], ['Microsoft Excel', ProficiencyLevel::Intermediate], ['Attention to Detail', ProficiencyLevel::Advanced]],
                    ],
                ],
            ],
        ];
    }
}
