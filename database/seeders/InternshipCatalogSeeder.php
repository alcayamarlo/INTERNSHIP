<?php

namespace Database\Seeders;

use App\Enums\ProficiencyLevel;
use App\Enums\UserRole;
use App\Enums\WorkSetup;
use App\Models\Employer;
use App\Models\Internship;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Ten internship postings spread across the academic programs (BSIT, BSHM,
 * BSTM, BSBA majors, BSMA), each under a fitting partner company.
 * Safe to re-run: companies and postings are matched by email and title.
 */
class InternshipCatalogSeeder extends Seeder
{
    public function run(): void
    {
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
                        'description' => 'For BS Information Technology students. Build features for our customer mobile app.',
                        'responsibilities' => ['Implement screens and API integrations', 'Fix bugs reported by QA', 'Join daily stand-ups'],
                        'requirements' => 'BS Information Technology, 4th year. Knowledge of JavaScript or Flutter.',
                        'duration' => '3 months',
                        'allowance' => 8000,
                        'work_setup' => WorkSetup::Hybrid,
                        'location' => 'Makati City',
                        'skills' => [['JavaScript', ProficiencyLevel::Intermediate], ['Mobile Development', ProficiencyLevel::Beginner], ['Git Version Control', ProficiencyLevel::Beginner]],
                    ],
                ],
            ],
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
                        'description' => 'For BS Hospitality Management students. Rotate through the hotel restaurant, bar and banquet operations.',
                        'responsibilities' => ['Serve guests following service standards', 'Set up banquet functions', 'Assist with inventory counts'],
                        'requirements' => 'BS Hospitality Management, 4th year. Food safety training is a plus.',
                        'duration' => '600 hours',
                        'allowance' => 5000,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Cebu City',
                        'skills' => [['Food and Beverage Service', ProficiencyLevel::Beginner], ['Teamwork', ProficiencyLevel::Intermediate], ['Customer Service', ProficiencyLevel::Beginner]],
                    ],
                ],
            ],
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
                        'description' => 'For BS Tourism Management students. Help plan and run local tours and travel events.',
                        'responsibilities' => ['Coordinate with tour guides and suppliers', 'Prepare tour materials', 'Assist guests during tours'],
                        'requirements' => 'BS Tourism Management, 4th year. Willing to travel within Cebu.',
                        'duration' => '500 hours',
                        'allowance' => 4500,
                        'work_setup' => WorkSetup::Hybrid,
                        'location' => 'Cebu Province',
                        'skills' => [['Event Planning', ProficiencyLevel::Beginner], ['Tour Guiding', ProficiencyLevel::Beginner], ['Teamwork', ProficiencyLevel::Intermediate]],
                    ],
                ],
            ],
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
                        'description' => 'For BS Business Administration students majoring in Operations or Financial Management. Support the distribution hub.',
                        'responsibilities' => ['Monitor inventory levels', 'Prepare delivery and cost reports', 'Suggest process improvements'],
                        'requirements' => 'BS Business Administration, Operations or Financial Management major, 4th year.',
                        'duration' => '3 months',
                        'allowance' => 6000,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Mandaue City',
                        'skills' => [['Inventory Management', ProficiencyLevel::Beginner], ['Microsoft Excel', ProficiencyLevel::Intermediate], ['Problem Solving', ProficiencyLevel::Intermediate]],
                    ],
                ],
            ],
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
                        'description' => 'For BS Management Accounting students. Assist the audit and tax teams with client engagements.',
                        'responsibilities' => ['Prepare working papers', 'Reconcile accounts and vouch transactions', 'Assist with tax filings'],
                        'requirements' => 'BS Management Accounting, 4th year. Strong Excel skills.',
                        'duration' => '3 months',
                        'allowance' => 6500,
                        'work_setup' => WorkSetup::Onsite,
                        'location' => 'Cebu City',
                        'skills' => [['Bookkeeping', ProficiencyLevel::Intermediate], ['Microsoft Excel', ProficiencyLevel::Intermediate], ['Attention to Detail', ProficiencyLevel::Intermediate]],
                    ],
                ],
            ],
        ];
    }
}
