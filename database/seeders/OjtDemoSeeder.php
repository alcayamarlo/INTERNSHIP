<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\UserRole;
use App\Models\AcademicSetting;
use App\Models\Announcement;
use App\Models\AttendanceLog;
use App\Models\Employer;
use App\Models\Evaluation;
use App\Models\Institution;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Models\JournalEntry;
use App\Models\Placement;
use App\Models\Program;
use App\Models\Student;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Demo data covering every OJT stage: one ongoing intern with pending DTR
 * entries and journals, and one intern who is completed and cleared.
 */
class OjtDemoSeeder extends Seeder
{
    public function run(): void
    {
        $employer = Employer::whereHas('user', fn ($query) => $query->where('email', 'employer@skillbridge.test'))->first();
        $institution = Institution::first();
        $admin = User::where('email', 'admin@skillbridge.test')->first();
        $coordinatorUser = User::where('email', 'coordinator@skillbridge.test')->first();
        $internship = $employer?->internships()->where('title', 'Web Developer Intern')->first();

        if (! $employer || ! $institution || ! $internship) {
            return;
        }

        $requiredHours = AcademicSetting::current()->required_ojt_hours ?: 300;

        $ongoing = $this->placementFor($internship, $institution, 'Ben Ramos', 'student2@skillbridge.test', 'SCC-24-00000002', now()->subWeeks(6));
        $this->seedLogs($ongoing, 25, approvedUntil: 20, supervisor: $employer->user);
        $this->seedJournals($ongoing, 5, acknowledgedUntil: 4, supervisor: $employer->user);

        $completed = $this->placementFor($internship, $institution, 'Carla Lim', 'student3@skillbridge.test', 'SCC-24-00000003', now()->subWeeks(12));
        $this->seedLogs($completed, (int) ceil($requiredHours / 8), approvedUntil: PHP_INT_MAX, supervisor: $employer->user);
        $this->seedJournals($completed, 8, acknowledgedUntil: PHP_INT_MAX, supervisor: $employer->user);
        $scores = collect(Evaluation::CRITERIA)->keys()->mapWithKeys(fn ($key, $index) => [$key => $index % 3 === 0 ? 4 : 5])->all();
        $completed->evaluation()->create([
            'evaluator_user_id' => $employer->user_id,
            'scores' => $scores,
            'average_score' => round(array_sum($scores) / count($scores), 2),
            'strengths' => 'Fast learner, writes clean and well-tested code.',
            'improvements' => 'Could speak up more during planning meetings.',
            'comments' => 'A reliable member of the team throughout the OJT.',
            'would_hire' => true,
            'submitted_at' => now()->subDays(3),
        ]);
        $completed->update([
            'status' => Placement::STATUS_COMPLETED,
            'completed_at' => now()->subDays(3),
            'end_date' => now()->subDays(3)->toDateString(),
            'cleared_at' => now()->subDay(),
            'cleared_by_user_id' => $coordinatorUser?->id,
            'clearance_remarks' => 'Requirements complete.',
            'certificate_number' => 'OJT-'.now()->format('Y').'-'.str_pad((string) $completed->id, 5, '0', STR_PAD_LEFT),
        ]);
        $completed->application->update(['status' => ApplicationStatus::Completed]);

        if ($admin) {
            Announcement::create([
                'user_id' => $admin->id,
                'title' => 'OJT requirements reminder',
                'content' => 'Students on OJT: log your daily time record every working day and submit a weekly journal every Friday. Supervisors, please review entries weekly.',
                'target_role' => null,
                'published_at' => now()->subDays(2),
            ]);
        }

        // Demo accounts sign in without the email-code step.
        User::where('email', 'like', '%@skillbridge.test')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    private function placementFor(Internship $internship, Institution $institution, string $name, string $email, string $studentId, Carbon $start): Placement
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'role' => UserRole::Student,
        ]);

        $student = Student::create([
            'user_id' => $user->id,
            'institution_id' => $institution->id,
            'student_id_number' => $studentId,
            'program_id' => Program::where('code', 'BSIT')->value('id'),
            'program' => 'BS Information Technology (BSIT)',
            'year_level' => '4th Year',
            'profile_completion' => 60,
        ]);

        $application = InternshipApplication::create([
            'internship_id' => $internship->id,
            'student_id' => $student->id,
            'status' => ApplicationStatus::Accepted,
            'match_percentage' => 82,
            'applied_at' => $start->copy()->subWeeks(2),
            'cover_letter' => 'I would love to join your development team.',
        ]);

        return Placement::create([
            'application_id' => $application->id,
            'student_id' => $student->id,
            'employer_id' => $internship->employer_id,
            'internship_id' => $internship->id,
            'start_date' => $start->toDateString(),
            'required_hours' => AcademicSetting::current()->required_ojt_hours ?: 300,
            'status' => Placement::STATUS_ACTIVE,
        ]);
    }

    private function seedLogs(Placement $placement, int $days, int $approvedUntil, User $supervisor): void
    {
        $date = $placement->start_date->copy();
        $tasks = ['Fixed UI bugs on the dashboard', 'Wrote feature tests for the API', 'Attended sprint planning and code review', 'Built a reporting page with Laravel', 'Refactored database queries'];

        for ($day = 0; $day < $days; $day++) {
            while ($date->isWeekend()) {
                $date->addDay();
            }
            if ($date->isFuture()) {
                break;
            }

            $approved = $day < $approvedUntil;
            $placement->attendanceLogs()->create([
                'work_date' => $date->toDateString(),
                'time_in' => '08:00',
                'time_out' => '17:00',
                'break_minutes' => 60,
                'hours' => 8,
                'task_summary' => $tasks[$day % count($tasks)],
                'status' => $approved ? AttendanceLog::STATUS_APPROVED : AttendanceLog::STATUS_PENDING,
                'reviewed_by_user_id' => $approved ? $supervisor->id : null,
                'reviewed_at' => $approved ? $date->copy()->addDay() : null,
            ]);
            $date->addDay();
        }
    }

    private function seedJournals(Placement $placement, int $weeks, int $acknowledgedUntil, User $supervisor): void
    {
        for ($week = 0; $week < $weeks; $week++) {
            $weekStart = $placement->start_date->copy()->startOfWeek()->addWeeks($week);
            if ($weekStart->isFuture()) {
                break;
            }

            $acknowledged = $week < $acknowledgedUntil;
            $placement->journalEntries()->create([
                'week_start' => $weekStart->toDateString(),
                'title' => 'Week '.($week + 1).' reflection',
                'accomplishments' => 'Completed assigned tickets and joined daily stand-ups.',
                'learnings' => 'Learned more about Laravel testing and team workflows.',
                'challenges' => 'Estimating tasks accurately.',
                'status' => $acknowledged ? JournalEntry::STATUS_ACKNOWLEDGED : JournalEntry::STATUS_SUBMITTED,
                'supervisor_comment' => $acknowledged ? 'Good progress this week.' : null,
                'reviewed_by_user_id' => $acknowledged ? $supervisor->id : null,
                'reviewed_at' => $acknowledged ? $weekStart->copy()->addDays(5) : null,
            ]);
        }
    }
}
