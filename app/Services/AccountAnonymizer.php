<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Appraisal;
use App\Models\Evaluation;
use App\Models\InternshipApplication;
use App\Models\JournalEntry;
use App\Models\Placement;
use App\Models\Student;
use App\Models\User;
use App\Support\UploadDisks;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Anonymises a student's account when an admin approves its deletion (RA 10173: the right to erasure).
 *
 * REMOVED: name, email, phone, school ID and every contact and personal field; the profile picture, 2x2 ID photo,
 * resumes, certificates, portfolio and competency files and their records; skills, experience, awards, references;
 * messages (both sides of those conversations) and notifications; the name, school ID, phone and address frozen in
 * the header of weekly reports, monthly and final evaluations; every session. The password becomes random and the
 * account is deactivated and marked anonymised: with no email and no school ID there is nothing to sign in with.
 *
 * KEPT, now anonymous: the OJT placements with their dates and required hours, the time records and hours, the weekly
 * reports and the monthly and final evaluations with their scores and comments, the application status and history
 * (their free text is cleared), program, year level and school year (for the school's statistics), and the audit and
 * data access logs, from which the student's email and school ID are removed (a failed sign-in log stores what was typed).
 * These are the school's records of an OJT that took place; nothing in them names the student any more.
 *
 * The student's name is also replaced, in the free text that stays (evaluation and report comments, time record notes), in
 * other people's notifications, and queued jobs that mention them are dropped (a queued email would still name them).
 *
 * Cached copies of the student's records (the coordinator, employer and admin dashboards cache the recent applications and
 * students, with names and emails in them) are cleared too: this class saves without model events, which is what normally
 * clears them (App\Observers\DashboardCacheObserver).
 *
 * Never delete an internship application: placements are tied to it with ON DELETE CASCADE, so the OJT hours would go too.
 * Irreversible. The caller makes an encrypted backup first (Admin\DeletionRequestController does).
 */
class AccountAnonymizer
{
    /** Rows that exist only to describe the person: removed. table => the file column on it, if any. */
    private const REMOVED_TABLES = [
        'certificates' => 'file_path',
        'portfolios' => 'file_path',
        'resumes' => 'file_path',
        'student_competencies' => 'evidence_path',
        'student_competency_scores' => null,
        'student_experiences' => null,
        'student_skills' => null,
        'student_awards' => null,
        'character_references' => null,
        'student_change_requests' => null,
        'verification_activities' => null,
        'recommendation_feedback' => null,
        'employer_shortlists' => null,
        'internship_invitations' => null,
    ];

    /** Fields of the student record cleared to null; the rest of the record is the program and OJT hours that stay. */
    private const STUDENT_FIELDS = [
        'first_name', 'last_name', 'middle_name', 'suffix', 'gender', 'about_me', 'profile_picture', 'id_photo_path',
        'address', 'city', 'province', 'zip_code', 'provincial_address', 'date_of_birth', 'place_of_birth', 'citizenship',
        'languages', 'civil_status', 'religion', 'height_feet', 'height_inches', 'weight_kg',
        'linkedin_url', 'facebook_url', 'github_url',
        'emergency_contact_name', 'emergency_contact_relationship', 'emergency_contact_address', 'emergency_contact_phone',
        'elementary_school', 'elementary_year_graduated', 'high_school', 'high_school_year_graduated',
        'senior_high_school', 'senior_high_year_graduated', 'honors_awards', 'gwa', 'expected_graduation',
        'student_id_number', 'privacy_consent_at', 'profile_verified_at', 'profile_verified_by_user_id',
    ];

    /** Free text in the records that stay (their OJT): where the student's name may appear, written by a supervisor or coordinator. */
    private const KEPT_TEXT = [
        'evaluations' => ['strengths', 'improvements', 'comments', 'coordinator_comment'],
        'appraisals' => ['supervisor_comment', 'student_comment'],
        'journal_entries' => ['title', 'accomplishments', 'learnings', 'challenges', 'actions_taken', 'supervisor_comment', 'coordinator_comment'],
        'attendance_logs' => ['task_summary', 'reviewer_remarks'],
    ];

    public function __construct(private ActivityLogService $activityLog) {}

    public function canAnonymise(User $user): bool
    {
        return $user->isRole(UserRole::Student) && $user->anonymised_at === null;
    }

    /** What the confirmation screen tells the admin will happen (counts only). */
    public function preview(User $user): array
    {
        $student = $user->student;
        $removed = [];
        $files = 0;
        if ($student) {
            foreach (self::REMOVED_TABLES as $table => $fileColumn) {
                if (! Schema::hasTable($table)) {
                    continue;
                }
                $query = DB::table($table)->where('student_id', $student->id);
                $removed[$table] = (clone $query)->count();
                if ($fileColumn) {
                    $files += (clone $query)->whereNotNull($fileColumn)->count();
                }
            }
            $files += (int) filled($student->profile_picture) + (int) filled($student->id_photo_path);
        }
        $files += (int) filled($user->avatar);
        $messages = Schema::hasTable('messages') ? DB::table('messages')->where('sender_id', $user->id)->orWhere('receiver_id', $user->id)->count() : 0;
        $files += Schema::hasTable('messages') ? DB::table('messages')->where(fn ($q) => $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id))->whereNotNull('attachment_path')->count() : 0;

        $placements = $student ? Placement::where('student_id', $student->id) : null;

        return [
            'records_removed' => array_sum($removed),
            'by_table' => array_filter($removed),
            'files' => $files,
            'messages' => $messages,
            'placements_kept' => $placements?->count() ?? 0,
            'active_placements' => $placements ? (clone $placements)->whereIn('status', [Placement::STATUS_ACTIVE, Placement::STATUS_PENDING_VALIDATION])->count() : 0,
            'hours_kept' => $student ? round((float) DB::table('attendance_logs')->whereIn('placement_id', Placement::where('student_id', $student->id)->select('id'))->sum('hours'), 1) : 0,
        ];
    }

    /**
     * Anonymises the account. Returns counts for the request record (no personal data in them).
     *
     * @return array<string, mixed>
     */
    public function anonymise(User $user, ?User $by = null): array
    {
        if (! $this->canAnonymise($user)) {
            throw new InvalidArgumentException('Only a student account that is not anonymised yet can be anonymised.');
        }

        $paths = [];
        $summary = ['removed' => [], 'files_deleted' => 0, 'messages_deleted' => 0, 'snapshots_scrubbed' => 0, 'log_entries_scrubbed' => 0, 'mentions_scrubbed' => 0, 'jobs_dropped' => 0];

        DB::transaction(function () use ($user, &$paths, &$summary) {
            $student = Student::withTrashed()->where('user_id', $user->id)->first();

            // 0. The audit log keeps what happened, not who: the email and school ID are taken out of it. (Before they are cleared.)
            $summary['log_entries_scrubbed'] = $this->scrubLogs($user, $student);
            [$summary['mentions_scrubbed'], $summary['jobs_dropped']] = $this->scrubMentions($user, $student);

            // 1. Rows that only describe the person, and their files.
            if ($student) {
                foreach (self::REMOVED_TABLES as $table => $fileColumn) {
                    if (! Schema::hasTable($table)) {
                        continue;
                    }
                    $query = DB::table($table)->where('student_id', $student->id);
                    if ($fileColumn) {
                        array_push($paths, ...(clone $query)->whereNotNull($fileColumn)->pluck($fileColumn)->all());
                    }
                    $count = $query->delete();
                    if ($count) {
                        $summary['removed'][$table] = $count;
                    }
                }

                // 2. The OJT stays. Free text a student wrote in an application is cleared; the application is NOT deleted.
                DB::table('internship_applications')->where('student_id', $student->id)->update(['cover_letter' => null, 'employer_notes' => null, 'resume_id' => null]);

                // 3. The name, school ID, phone and address frozen on the forms.
                $summary['snapshots_scrubbed'] = $this->scrubSnapshots($student);

                // 4. The student record: identity and contact fields cleared; program and OJT hours stay.
                $paths[] = $student->profile_picture;
                $paths[] = $student->id_photo_path;
                $clear = array_fill_keys(array_values(array_filter(self::STUDENT_FIELDS, fn ($column) => Schema::hasColumn('students', $column))), null);
                $student->forceFill($clear + [
                    'student_id_locked' => false, 'profile_completion' => 0, 'privacy_consent' => false, 'discoverable' => false,
                    'show_civil_status' => false, 'show_religion' => false, 'show_height' => false, 'show_weight' => false,
                ])->saveQuietly();
            }

            // 5. Messages (both sides), notifications, sessions.
            $messages = DB::table('messages')->where(fn ($q) => $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id));
            array_push($paths, ...(clone $messages)->whereNotNull('attachment_path')->pluck('attachment_path')->all());
            $summary['messages_deleted'] = $messages->delete();
            DB::table('message_reactions')->where('user_id', $user->id)->delete();
            DB::table('notifications')->where('user_id', $user->id)->delete();
            DB::table('two_factor_trusted_browsers')->where('user_id', $user->id)->delete();
            DB::table(config('session.table', 'sessions'))->where('user_id', $user->id)->delete();

            // 6. The account: nothing left to sign in with.
            $paths[] = $user->avatar;
            $user->forceFill([
                'name' => config('privacy.anonymised_name'), 'email' => null, 'phone' => null, 'avatar' => null,
                'password' => Hash::make(Str::random(64)), 'remember_token' => null,
                'email_verified_at' => null, 'verification_code' => null, 'verification_code_expires_at' => null, 'verification_attempts' => 0,
                'is_active' => false, 'email_notifications' => false, 'sms_notifications' => false, 'must_change_password' => false,
                'last_login_at' => null, 'failed_logins' => 0, 'last_failed_login_at' => null, 'locked_until' => null,
                'two_factor_enabled' => false, 'two_factor_recovery_codes' => null, 'two_factor_code_hash' => null,
                'two_factor_code_expires_at' => null, 'two_factor_attempts' => 0, 'two_factor_skip_once' => false,
                'archived_at' => now(), 'anonymised_at' => now(),
            ])->saveQuietly();
        });

        // Files go after the database commit: a failed delete leaves a stray file, never a record pointing at nothing.
        $summary['files_deleted'] = $this->deleteFiles($paths);

        $student = Student::withTrashed()->where('user_id', $user->id)->first();
        $this->forgetCachedCopies($user, $student);
        $summary['placements_kept'] = $student ? Placement::where('student_id', $student->id)->count() : 0;
        $summary['hours_kept'] = $student ? round((float) DB::table('attendance_logs')->whereIn('placement_id', Placement::where('student_id', $student->id)->select('id'))->sum('hours'), 1) : 0;

        $this->activityLog->log($by, 'account_anonymised', ['user_id' => $user->id] + array_diff_key($summary, ['removed' => 1]) + ['records_removed' => array_sum($summary['removed'])]);

        return $summary;
    }

    /**
     * Replaces the student's name in the free text of the records that stay, and in everyone's notifications, and drops queued
     * jobs (emails, notifications) that mention their name, email or school ID. Returns [text fields changed, jobs dropped].
     *
     * @return array{0: int, 1: int}
     */
    private function scrubMentions(User $user, ?Student $student): array
    {
        $names = array_values(array_unique(array_filter([trim((string) $user->name), trim($student?->first_name.' '.$student?->last_name)], fn ($name) => strlen($name) >= 5 && $name !== config('privacy.anonymised_name'))));
        $replacement = (string) config('privacy.anonymised_name');
        $replace = fn (?string $text) => $text === null ? null : (string) str_ireplace($names, $replacement, $text);
        $changed = 0;

        $update = function (string $table, $rows, array $columns) use ($replace, &$changed) {
            foreach ($rows as $row) {
                $new = [];
                foreach ($columns as $column) {
                    $clean = $replace($row->{$column});
                    if ($clean !== $row->{$column}) {
                        $new[$column] = $clean;
                    }
                }
                if ($new) {
                    DB::table($table)->where('id', $row->id)->update($new);
                    $changed++;
                }
            }
        };

        if ($student && $names) {
            $placementIds = Placement::where('student_id', $student->id)->pluck('id');
            foreach (self::KEPT_TEXT as $table => $columns) {
                $update($table, DB::table($table)->whereIn('placement_id', $placementIds)->get(['id', ...$columns]), $columns);
            }
            $update('placements', DB::table('placements')->where('student_id', $student->id)->get(['id', 'clearance_remarks', 'validation_remarks']), ['clearance_remarks', 'validation_remarks']);
            $update('application_status_histories', DB::table('application_status_histories')->whereIn('application_id', DB::table('internship_applications')->where('student_id', $student->id)->select('id'))->get(['id', 'notes']), ['notes']);
        }

        // Notices to other people ("X submitted a weekly report") name the student too.
        foreach ($names as $name) {
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $name).'%';
            $rows = DB::table('notifications')->where(fn ($q) => $q->where('title', 'like', $like)->orWhere('message', 'like', $like)->orWhere('data', 'like', $like))->get(['id', 'title', 'message', 'data']);
            $update('notifications', $rows, ['title', 'message', 'data']);
        }

        // Queued jobs (an email waiting to be sent) keep the text they were made with.
        $needles = array_values(array_filter([...$names, $user->email, $student?->student_id_number], fn ($value) => is_string($value) && strlen($value) >= 5));
        $dropped = 0;
        foreach ($needles as $needle) {
            foreach (array_unique([$needle, substr((string) json_encode($needle), 1, -1)]) as $variant) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $variant).'%';
                foreach (['jobs', 'failed_jobs'] as $table) {
                    if (Schema::hasTable($table)) {
                        $dropped += DB::table($table)->where('payload', 'like', $like)->delete();
                    }
                }
            }
        }

        return [$changed, $dropped];
    }

    /** The dashboards cache records that name the student. The model events that clear them do not run here, so clear them now. */
    private function forgetCachedCopies(User $user, ?Student $student): void
    {
        $invalidator = app(DashboardCacheInvalidator::class);
        $invalidator->userChanged($user);
        if ($student) {
            $invalidator->studentChanged($student);
            InternshipApplication::query()->where('student_id', $student->id)->get()->each(fn (InternshipApplication $application) => $invalidator->applicationChanged($application));
        }
    }

    /**
     * Removes the student's email, school ID and name from the details of audit log entries: the ones about them, and the
     * ones that merely mention them (a failed sign-in stores the text that was typed). The entries themselves stay.
     */
    private function scrubLogs(User $user, ?Student $student): int
    {
        $needles = array_values(array_filter([$user->email, $student?->student_id_number, $user->phone], fn ($value) => is_string($value) && strlen($value) >= 5));
        $count = 0;
        $query = DB::table('system_logs')->whereNotNull('details')->where(function ($q) use ($user, $needles) {
            $q->where('user_id', $user->id);
            foreach ($needles as $needle) {
                // The needle as it sits inside the stored JSON text (escaped, without the surrounding quotes), anywhere in a value.
                $inJson = substr((string) json_encode($needle, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), 1, -1);
                $q->orWhere('details', 'like', '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $inJson).'%');
            }
        });
        $query->orderBy('id')->chunkById(200, function ($rows) use ($needles, &$count) {
            foreach ($rows as $row) {
                $details = json_decode((string) $row->details, true);
                if (! is_array($details)) {
                    continue;
                }
                $clean = $this->scrubLogValue($details, $needles);
                if ($clean !== $details) {
                    DB::table('system_logs')->where('id', $row->id)->update(['details' => json_encode($clean, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)]);
                    $count++;
                }
            }
        });

        return $count;
    }

    private function scrubLogValue(array $data, array $needles): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->scrubLogValue($value, $needles);
            } elseif (is_string($value) && collect($needles)->contains(fn ($needle) => stripos($value, $needle) !== false)) {
                $data[$key] = '[removed]';
            } elseif (is_string($key) && in_array($key, ['name', 'email', 'student_name'], true)) {
                $data[$key] = '[removed]';
            }
        }

        return $data;
    }

    /** Replaces the identity keys in the frozen header of every weekly report, monthly and final evaluation, and certificate. */
    private function scrubSnapshots(Student $student): int
    {
        $count = 0;
        $placements = Placement::where('student_id', $student->id)->get();
        foreach ($placements as $placement) {
            foreach ([JournalEntry::class => ['header_snapshot', 'header_overrides'], Appraisal::class => ['header_snapshot'], Evaluation::class => ['header_snapshot']] as $model => $columns) {
                $model::where('placement_id', $placement->id)->get()->each(function ($row) use ($columns, &$count) {
                    foreach ($columns as $column) {
                        if (is_array($row->{$column})) {
                            $row->{$column} = $this->scrub($row->{$column});
                            $count++;
                        }
                    }
                    $row->saveQuietly();
                });
            }
            if (is_array($placement->certificate_snapshot)) {
                $placement->certificate_snapshot = $this->scrub($placement->certificate_snapshot);
                $placement->saveQuietly();
                $count++;
            }
        }

        return $count;
    }

    /** Recursively blanks identity values: names become "Former student", IDs and contact details become empty. */
    private function scrub(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->scrub($value);
            } elseif (is_string($key) && preg_match('/(^|_)(full_?name|student_?name|name)$/i', $key) && ! preg_match('/(company|supervisor|evaluator|adviser|reviewer)/i', $key)) {
                $data[$key] = config('privacy.anonymised_name');
            } elseif (is_string($key) && preg_match('/(student_?id|school_?id|contact|phone|mobile|email|address)/i', $key)) {
                $data[$key] = '';
            }
        }

        return $data;
    }

    /** @return int how many files were actually deleted */
    private function deleteFiles(array $paths): int
    {
        $deleted = 0;
        $disks = array_values(array_unique([UploadDisks::public(), UploadDisks::private(), 'public', 'private', 'local']));
        foreach (array_unique(array_filter($paths, fn ($path) => is_string($path) && $path !== '')) as $path) {
            foreach ($disks as $disk) {
                try {
                    if (Storage::disk($disk)->exists($path)) {
                        Storage::disk($disk)->delete($path);
                        $deleted++;
                    }
                } catch (\Throwable) {
                    // a disk that is not configured here: nothing to delete on it
                }
            }
        }

        return $deleted;
    }
}
