<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\AcademicSetting;
use App\Models\Placement;
use App\Models\Student;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The student data printed on every OJT form, resolved from the profile and the placement at render time
 * ({{full_name}}, {{student_id}}, {{course}}, {{department}} ...), plus the completeness check that runs
 * before any form is printed or submitted. A submitted form stores these values as its header_snapshot,
 * so later profile edits never change it.
 */
class OjtDocumentData
{
    /** The fields each form prints; a form is blocked while any of them is blank. */
    public const DOCUMENTS = [
        'weekly_report' => ['full_name', 'company', 'supervisor_name', 'designation'],
        'monthly_evaluation' => ['full_name', 'company', 'supervisor_name', 'designation'],
        'final_evaluation' => ['full_name', 'student_id', 'course', 'contact_number', 'company'],
        'certificate' => ['full_name', 'course', 'company'],
    ];

    public const DOCUMENT_NAMES = [
        'weekly_report' => 'weekly report',
        'monthly_evaluation' => 'monthly evaluation',
        'final_evaluation' => 'final evaluation',
        'certificate' => 'certificate of completion',
    ];

    public const LABELS = [
        'full_name' => 'Name of Trainee (first and last name)',
        'student_id' => 'Student ID',
        'course' => 'Course',
        'year_level' => 'Year level',
        'contact_number' => 'Contact number',
        'company' => 'Company / Agency',
        'supervisor_name' => "Supervisor's name",
        'designation' => 'Designation (department)',
    ];

    /** The trainee details frozen on a document when it is submitted or approved. */
    public const SNAPSHOT_FIELDS = ['full_name', 'student_id', 'course', 'year_level', 'contact_number', 'address', 'company', 'supervisor_name', 'designation'];

    /** Fields the student fixes on the profile page; the others are OJT details. */
    public const PROFILE_FIELDS = ['full_name', 'student_id', 'course', 'year_level', 'contact_number'];

    /** Header fields the student may change on one weekly report; the rest are locked to the profile. */
    public const EDITABLE_FIELDS = ['company', 'supervisor_name', 'designation', 'period_of_evaluation', 'inclusive_dates'];

    /** Fields whose change can also become the OJT's new default ("Also update my profile / placement"). */
    public const SAVE_AS_DEFAULT = ['supervisor_name' => 'supervisor_name', 'designation' => 'department'];

    /** Letters (any language), spaces, periods and hyphens. */
    public const NAME_REGEX = '/^\p{L}[\p{L} .\-]*$/u';

    public const SUFFIXES = ['Jr.', 'Sr.', 'II', 'III', 'IV', 'V'];

    /** "First M. Last Suffix", e.g. "Joshua D. Opsima"; the account name when the name parts are missing. */
    public static function fullName(?Student $student): string
    {
        if (! $student) {
            return '';
        }
        $first = trim((string) $student->first_name);
        $last = trim((string) $student->last_name);
        if ($first === '' || $last === '') {
            return trim((string) $student->user?->name);
        }
        $middle = trim((string) $student->middle_name);
        $initial = $middle !== '' ? mb_strtoupper(mb_substr($middle, 0, 1)).'.' : '';

        return trim(implode(' ', array_filter([$first, $initial, $last, trim((string) $student->suffix)])));
    }

    /** Trims, collapses spaces and title-cases a name: "joshua  d.  opsima" -> "Joshua D. Opsima" (II, III, IV kept). */
    public static function normalizeName(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));
        if ($value === '') {
            return '';
        }
        $value = mb_convert_case(mb_strtolower($value), MB_CASE_TITLE, 'UTF-8');

        return (string) preg_replace_callback('/\b(Ii|Iii|Iv|Vi|Vii|Viii|Ix)\b/u', fn ($match) => mb_strtoupper($match[1]), $value);
    }

    /** "jr" / "JR." -> "Jr.", "iii" -> "III"; anything else is returned trimmed for validation to reject. */
    public static function normalizeSuffix(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }
        $key = mb_strtolower(rtrim($value, '.'));

        return match ($key) {
            'jr' => 'Jr.',
            'sr' => 'Sr.',
            'ii', 'iii', 'iv', 'v' => mb_strtoupper($key),
            default => $value,
        };
    }

    /** The supervisor printed on this OJT's forms: set on the OJT, else the company's contact person or account name. */
    public function defaultSupervisor(Placement $placement): string
    {
        $placement->loadMissing('employer.user');

        return trim((string) ($placement->supervisor_name
            ?: $placement->employer?->contact_person
            ?: $placement->employer?->user?->name));
    }

    /** @return list<string> Supervisor names on record for this OJT, for the "Supervisor's Name" dropdown. */
    public function supervisorOptions(Placement $placement): array
    {
        $placement->loadMissing('employer.user');

        return collect([$placement->supervisor_name, $placement->employer?->contact_person, $placement->employer?->user?->name])
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * The "Designation" dropdown: the admin's list, plus the value already saved (so an older spelling is not lost).
     *
     * @return list<string>
     */
    public function designations(?string $current = null): array
    {
        $list = AcademicSetting::current()->ojtDepartments();
        $current = trim((string) $current);
        if ($current !== '' && ! in_array($current, $list, true)) {
            $list[] = $current;
        }

        return $list;
    }

    /** Live values from the profile and the placement. */
    public function values(Placement $placement): array
    {
        $placement->loadMissing(['student.user', 'employer.user']);
        $student = $placement->student;

        return [
            'full_name' => self::fullName($student),
            'student_id' => trim((string) $student?->student_id_number),
            'course' => trim((string) $student?->program),
            'year_level' => trim((string) $student?->year_level),
            'contact_number' => trim((string) $student?->user?->phone),
            'address' => collect([$student?->address, $student?->city, $student?->province])->map(fn ($part) => trim((string) $part))->filter()->implode(', '),
            'company' => trim((string) $placement->employer?->company_name),
            'supervisor_name' => $this->defaultSupervisor($placement),
            'designation' => trim((string) $placement->department),
            'ojt_start_date' => $placement->start_date?->format('F j, Y') ?? '',
            'ojt_end_date' => $placement->end_date?->format('F j, Y') ?? '',
            'required_hours' => (int) $placement->required_hours,
            'hours_rendered_to_date' => $placement->approvedHours(),
            'today' => now('Asia/Manila')->format('F j, Y'),
        ];
    }

    /** A document's frozen details over the live ones; a detail that was blank when frozen falls back to the live value. */
    public static function withSnapshot(array $live, ?array $snapshot): array
    {
        $frozen = array_filter($snapshot ?? [], fn ($value) => $value !== null && $value !== '');

        return [...$live, ...$frozen];
    }

    /**
     * Week number, "FINALS (Week 13)" and "April 20 - 24, 2026" for the week containing $day. Inclusive Dates
     * follow the days the report covers (Placement::reportDays): weekdays, a logged weekend day, OJT dates only.
     */
    public function weekValues(Placement $placement, CarbonInterface $day): array
    {
        $monday = CarbonImmutable::parse($day->toDateString())->startOfWeek();
        $weekNumber = $placement->weekNumberFor($monday);
        $label = mb_strtoupper(AcademicSetting::current()->evaluationPeriodLabelFor($monday->month));

        return [
            'week_no' => $weekNumber,
            'period_of_evaluation' => $weekNumber ? "{$label} (Week {$weekNumber})" : $label,
            'inclusive_dates' => self::inclusiveDates(...$placement->reportDateRange($monday)),
        ];
    }

    /** The header of a weekly report for that week, before any change by the student. */
    public function weeklyHeader(Placement $placement, CarbonInterface $day): array
    {
        return [...$this->values($placement), ...$this->weekValues($placement, $day)];
    }

    /** "April 20 - 24, 2026", "April 27 - May 1, 2026", "December 29, 2025 - January 2, 2026". */
    public static function inclusiveDates(CarbonInterface $start, ?CarbonInterface $end = null): string
    {
        $start = CarbonImmutable::parse($start->toDateString());
        $end = $end ? CarbonImmutable::parse($end->toDateString()) : $start->startOfWeek()->addDays(4);
        if ($start->isSameDay($end)) {
            return $start->format('F j, Y');
        }
        if (! $start->isSameYear($end)) {
            return $start->format('F j, Y').' - '.$end->format('F j, Y');
        }

        return $start->isSameMonth($end)
            ? $start->format('F j').' - '.$end->format('j, Y')
            : $start->format('F j').' - '.$end->format('F j, Y');
    }

    /**
     * Reads a typed "Inclusive Dates" value and returns it in the standard form, or null when it is not a
     * date range inside that week (Monday to Sunday) and inside the OJT dates when given. Accepts "April 21 - 24, 2026",
     * "April 30 - May 1, 2026", "Dec 29, 2025 - Jan 2, 2026" and a single "April 21, 2026".
     */
    public static function normalizeInclusiveDates(string $text, CarbonInterface $weekDay, ?CarbonInterface $ojtStart = null, ?CarbonInterface $ojtEnd = null): ?string
    {
        $text = trim((string) preg_replace('/\s+/u', ' ', str_replace(['–', '—'], '-', $text)));
        $month = '([A-Za-z]+)\.?';
        $patterns = [
            "/^{$month} (\d{1,2}), ?(\d{4}) ?- ?{$month} (\d{1,2}),? (\d{4})$/" => fn ($m) => [[$m[1], $m[2], $m[3]], [$m[4], $m[5], $m[6]]],
            "/^{$month} (\d{1,2}) ?- ?{$month} (\d{1,2}),? (\d{4})$/" => fn ($m) => [[$m[1], $m[2], $m[5]], [$m[3], $m[4], $m[5]]],
            "/^{$month} (\d{1,2}) ?- ?(\d{1,2}),? (\d{4})$/" => fn ($m) => [[$m[1], $m[2], $m[4]], [$m[1], $m[3], $m[4]]],
            "/^{$month} (\d{1,2}),? (\d{4})$/" => fn ($m) => [[$m[1], $m[2], $m[3]], [$m[1], $m[2], $m[3]]],
        ];
        foreach ($patterns as $pattern => $parts) {
            if (! preg_match($pattern, $text, $matches)) {
                continue;
            }
            [$from, $to] = $parts($matches);
            $start = self::makeDate(...$from);
            $end = self::makeDate(...$to);
            if (! $start || ! $end) {
                return null;
            }
            if ($start->gt($end) && $start->year === $end->year) {
                $start = $start->subYear(); // "December 29 - January 2, 2026"
            }
            $monday = CarbonImmutable::parse($weekDay->toDateString())->startOfWeek();
            if ($start->gt($end) || $start->lt($monday) || $end->gt($monday->addDays(6))
                || ($ojtStart && $start->lt(CarbonImmutable::parse($ojtStart->toDateString())))
                || ($ojtEnd && $end->gt(CarbonImmutable::parse($ojtEnd->toDateString())))) {
                return null;
            }

            return self::inclusiveDates($start, $end);
        }

        return null;
    }

    private static function makeDate(string $month, string $day, string $year): ?CarbonImmutable
    {
        foreach (['F j Y', 'M j Y'] as $format) {
            $date = CarbonImmutable::createFromFormat('!'.$format, ucfirst(strtolower($month)).' '.(int) $day.' '.$year);
            if ($date && $date->format('j') === (string) (int) $day) {
                return $date;
            }
        }

        return null;
    }

    /**
     * Fields the document prints that are blank, as field => label.
     *
     * @param  array<string, mixed>  $values  the values the document will print (live, or its snapshot)
     */
    public function missing(array $values, string $document): array
    {
        return collect(self::DOCUMENTS[$document] ?? [])
            ->filter(fn (string $field) => trim((string) ($values[$field] ?? '')) === '')
            ->mapWithKeys(fn (string $field) => [$field => self::LABELS[$field]])
            ->all();
    }

    public function missingFor(Placement $placement, string $document): array
    {
        return $this->missing($this->values($placement), $document);
    }

    /** "Complete your profile first: Student ID, Designation (department)." (or the trainee's, for other roles). */
    public function blockedMessage(array $missing, ?User $viewer, Placement $placement): string
    {
        $list = implode(', ', array_values($missing));

        return $this->isOwnStudent($viewer, $placement)
            ? "Complete your profile first: {$list}."
            : "The trainee's details are incomplete: {$list}.";
    }

    /**
     * Null when the document can be produced; otherwise the response that stops it: a page for a print
     * or download, or a redirect back with the message for a submission.
     */
    public function blockedResponse(Request $request, Placement $placement, string $document, ?array $values = null): ?Response
    {
        $missing = $this->missing($values ?? $this->values($placement), $document);
        if ($missing === []) {
            return null;
        }
        $viewer = $request->user();
        $message = $this->blockedMessage($missing, $viewer, $placement);

        if (! $request->isMethod('GET')) {
            return back()->withInput()->with('error', $message)->with('profile_missing', array_values($missing));
        }

        return response()->view('ojt.document-blocked', [
            'message' => $message,
            'missing' => $missing,
            'document' => self::DOCUMENT_NAMES[$document] ?? 'document',
            'links' => $this->fixLinks($missing, $viewer, $placement),
        ], 409);
    }

    /** Where the viewer can fix the missing fields: the profile page, My OJT, or the supervisor's intern page. */
    public function fixLinks(array $missing, ?User $viewer, Placement $placement): array
    {
        $links = [];
        $profileMissing = array_intersect(array_keys($missing), self::PROFILE_FIELDS) !== [];
        $ojtMissing = array_diff(array_keys($missing), self::PROFILE_FIELDS) !== [];

        if ($this->isOwnStudent($viewer, $placement)) {
            if ($profileMissing) {
                $links[] = ['label' => 'Go to my profile', 'url' => route('student.profile.edit')];
            }
            if ($ojtMissing) {
                $links[] = ['label' => 'Set my OJT details', 'url' => route('student.ojt.index', ['placement' => $placement->id, 'tab' => 'progress']).'#ojtDetails'];
            }
        } elseif ($viewer?->isRole(UserRole::Employer, UserRole::Supervisor) && $ojtMissing) {
            $links[] = ['label' => 'Set the OJT details', 'url' => route($viewer->role->ojtRoutePrefix().'.interns.show', ['placement' => $placement->id, 'tab' => 'progress'])];
        }

        return $links;
    }

    private function isOwnStudent(?User $viewer, Placement $placement): bool
    {
        return $viewer !== null && $viewer->isRole(UserRole::Student)
            && (int) $viewer->student?->id === (int) $placement->student_id;
    }

    /** Validation rule for a supervisor's name: letters, spaces, periods, hyphens; 2-80 characters. */
    public static function supervisorNameRules(bool $required = true): array
    {
        return [$required ? 'required' : 'nullable', 'string', 'min:2', 'max:80', 'regex:'.self::NAME_REGEX];
    }

    /** Records a change of the OJT's printed defaults (who, when, old and new value). */
    public function logPlacementChange(User $actor, Placement $placement, string $field, ?string $old, ?string $new): void
    {
        if ((string) $old === (string) $new) {
            return;
        }
        app(ActivityLogService::class)->log($actor, 'ojt_document_default_changed', [
            'placement_id' => $placement->id,
            'field' => $field,
            'old' => $old,
            'new' => $new,
        ]);
    }
}
