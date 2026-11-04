<?php

namespace App\Services;

use App\Models\InternshipFlag;
use App\Models\Placement;

/**
 * What the coordinator checks before deploying a student: the company is verified, it has a valid
 * MOA with the school, the internship is open to the student's program, and no coordinator marked
 * the posting unsuitable.
 */
class PlacementValidation
{
    /** @return list<array{key: string, label: string, passed: bool, detail: string, blocking: bool}> */
    public function checks(Placement $placement): array
    {
        $placement->loadMissing(['employer', 'internship.programs', 'student']);
        $employer = $placement->employer;
        $programs = $placement->internship?->programs ?? collect();
        $programMatch = $programs->isEmpty() || $programs->contains('id', $placement->student?->program_id);
        $flag = InternshipFlag::query()->where('internship_id', $placement->internship_id)->with('coordinator.user')->first();

        return [
            [
                'key' => 'company_verified',
                'label' => 'Company verified',
                'passed' => (bool) $employer?->isVerified(),
                'detail' => $employer?->isVerified() ? 'Verified by an admin' : 'The company is not verified yet',
                'blocking' => true,
            ],
            [
                'key' => 'moa',
                'label' => 'MOA with the school',
                'passed' => (bool) $employer?->hasValidMoa(),
                'detail' => $employer?->moa_expires_on
                    ? $employer->moaStatus().' (until '.$employer->moa_expires_on->format('M d, Y').')'
                    : 'No MOA on file',
                'blocking' => false,
            ],
            [
                'key' => 'program_match',
                'label' => 'Open to the student\'s program',
                'passed' => $programMatch,
                'detail' => $programs->isEmpty() ? 'Open to all programs' : ($programMatch ? 'Program included' : 'Not listed for this program'),
                'blocking' => false,
            ],
            [
                'key' => 'not_flagged',
                'label' => 'Posting not marked unsuitable',
                'passed' => $flag === null,
                'detail' => $flag ? 'Marked by '.($flag->coordinator?->user?->name ?? 'a coordinator').': '.$flag->reason : 'No concerns recorded',
                'blocking' => false,
            ],
        ];
    }

    public function hasBlockingFailure(array $checks): bool
    {
        return collect($checks)->contains(fn (array $check) => $check['blocking'] && ! $check['passed']);
    }

    public function hasWarnings(array $checks): bool
    {
        return collect($checks)->contains(fn (array $check) => ! $check['blocking'] && ! $check['passed']);
    }
}
