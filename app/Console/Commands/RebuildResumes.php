<?php

namespace App\Console\Commands;

use App\Models\Resume;
use App\Services\CompanyVisibleProfile;
use App\Services\ResumeBuilderService;
use App\Services\ResumeContent;
use Illuminate\Console\Command;

/**
 * Re-renders saved resumes in the current format (the Appendix H layout), so employers stop receiving copies that carry
 * personal data (birth date, civil status, religion, home address, ...).
 *
 * A version 3 or 4 resume keeps what the student wrote (and loses any personal data). A version 1 or 2 resume
 * has no structure to carry over, so it is rebuilt from the student's current profile.
 */
class RebuildResumes extends Command
{
    protected $signature = 'resumes:rebuild {--dry-run : Show what would change without changing anything}';

    protected $description = 'Re-render saved resumes in the current format, without personal data';

    public function handle(ResumeBuilderService $builder, ResumeContent $content, CompanyVisibleProfile $profile): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $counts = ['current' => 0, 'updated' => 0, 'fromProfile' => 0];
        $tooLong = [];
        $failed = [];

        Resume::with('student.user')->orderBy('id')->chunkById(50, function ($resumes) use (&$counts, &$tooLong, &$failed, $builder, $content, $profile, $dryRun) {
            foreach ($resumes as $resume) {
                $student = $resume->student;
                $version = is_array($resume->content) ? (int) ($resume->content['version'] ?? 1) : 0;
                if (! $student) {
                    continue;
                }
                if ($version >= ResumeContent::EDITABLE_VERSION) {
                    $counts['current']++;

                    continue;
                }

                $fromProfile = ! in_array($version, ResumeContent::UPGRADABLE_VERSIONS, true);
                try {
                    $new = $fromProfile ? $profile->resume($student) : $content->upgrade($resume->content, $student);
                    $pages = $builder->layout($new, $student)['pages'];
                    if ($pages > ResumeBuilderService::MAX_PAGES) {
                        $tooLong[] = "{$student->user?->name} (resume #{$resume->id}, {$pages} pages)";

                        continue;
                    }
                    if (! $dryRun) {
                        $builder->updateContent($resume, $new);
                    }
                    $counts['updated']++;
                    $counts['fromProfile'] += (int) $fromProfile;
                } catch (\Throwable $exception) {
                    $failed[] = "resume #{$resume->id}: ".$exception->getMessage();
                }
            }
        });

        $verb = $dryRun ? 'Would update' : 'Updated';
        $this->info("{$verb} {$counts['updated']} resume(s) ({$counts['fromProfile']} rebuilt from the profile). {$counts['current']} already current.");
        if ($tooLong !== []) {
            $this->warn('Too long for 2 pages, left as they are (ask the student to shorten their profile, then generate again):');
            foreach ($tooLong as $line) {
                $this->line('  - '.$line);
            }
        }
        foreach ($failed as $line) {
            $this->error($line);
        }

        return $failed === [] ? self::SUCCESS : self::FAILURE;
    }
}
