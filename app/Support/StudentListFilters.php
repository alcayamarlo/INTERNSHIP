<?php

namespace App\Support;

use App\Models\Internship;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Shared filters for employer lists of students (Applicants, Sent Invitations): name search,
 * internship, status, and program. All filters combine (AND). Pair with <x-table-filters>.
 *
 * $statusGroups maps a filter key to the stored status values it covers, for example
 * ['under_review' => ['reviewed', 'interview']], so one filter option can span several statuses.
 */
class StudentListFilters
{
    /** @var array{q: string, internship: ?int, status: ?string, program: ?string} */
    public readonly array $values;

    /**
     * @param  array<string, array{label: string, statuses: list<string>}>  $statusGroups
     */
    public function __construct(Request $request, private array $statusGroups)
    {
        $status = (string) $request->query('status', '');
        $internship = $request->query('internship');

        // Unknown or malformed values are ignored rather than rejected, so a stale link still opens.
        $this->values = [
            'q' => mb_substr(trim((string) $request->query('q', '')), 0, 100),
            'internship' => is_numeric($internship) ? (int) $internship : null,
            'status' => array_key_exists($status, $this->statusGroups) ? $status : null,
            'program' => mb_substr(trim((string) $request->query('program', '')), 0, 150) ?: null,
        ];
    }

    public function active(): bool
    {
        return $this->values['q'] !== '' || $this->values['internship'] || $this->values['status'] || $this->values['program'];
    }

    /** Applies every active filter to a query on a table with student_id, internship_id and status columns. */
    public function apply(Builder $query): Builder
    {
        $v = $this->values;

        return $query
            ->when($v['q'] !== '', fn (Builder $q) => $q->whereHas('student.user', function (Builder $user) use ($v) {
                // LIKE is case-insensitive with the app's MySQL collation (and SQLite in tests).
                $user->where('name', 'like', '%'.addcslashes($v['q'], '%_\\').'%');
            }))
            ->when($v['internship'], fn (Builder $q) => $q->where('internship_id', $v['internship']))
            ->when($v['status'], fn (Builder $q) => $q->whereIn('status', $this->statusGroups[$v['status']]['statuses']))
            ->when($v['program'], fn (Builder $q) => $q->whereHas('student', fn (Builder $s) => $s->where('program', $v['program'])));
    }

    /**
     * Options for the dropdowns, taken from the unfiltered list so every choice can match something.
     *
     * @return array{internships: Collection<int, string>, statuses: array<string, string>, programs: Collection<int, string>}
     */
    public function options(Builder $unfiltered): array
    {
        return [
            'internships' => Internship::withTrashed()
                ->whereIn('id', (clone $unfiltered)->select('internship_id'))
                ->orderBy('title')
                ->pluck('title', 'id'),
            'statuses' => array_map(fn (array $group) => $group['label'], $this->statusGroups),
            'programs' => Student::query()
                ->whereIn('id', (clone $unfiltered)->select('student_id'))
                ->whereNotNull('program')
                ->where('program', '!=', '')
                ->distinct()
                ->orderBy('program')
                ->pluck('program'),
        ];
    }
}
