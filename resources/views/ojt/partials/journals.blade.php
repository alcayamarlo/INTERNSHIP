{{--
    Weekly Activity Narrative Reports. Expects $placement and $mode: student | employer | coordinator | readonly.
    Older journals (accomplishments-style) still render their original fields.
--}}
@php
    $mode ??= 'readonly';
    $weeklyReports = app(\App\Services\WeeklyReportService::class);
@endphp

<div class="card mb-4" data-live-region="ojt-journals-{{ $placement->id }}" data-live-topics="placements">
    <div class="card-header"><h2 class="h6 mb-0"><i class="bi bi-journal-text" aria-hidden="true"></i> Weekly Reports</h2></div>
    <div class="card-body">
        @forelse($placement->journalEntries as $entry)
            @php $rows = $weeklyReports->dailyRows($entry); @endphp
            <article class="border rounded p-3 mb-3" aria-labelledby="journalTitle-{{ $entry->id }}">
                <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                    <div>
                        <strong id="journalTitle-{{ $entry->id }}">{{ $entry->title }}</strong>
                        <div class="small text-muted">{{ $entry->inclusiveDates() }}</div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <x-status-badge :status="$entry->isCertified() ? 'approved' : 'pending'" :label="$entry->isCertified() ? 'Certified' : 'Submitted'" />
                        <a href="{{ route('ojt.journals.print', $entry) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener"><i class="bi bi-printer" aria-hidden="true"></i> Print</a>
                    </div>
                </div>

                <div class="table-responsive mb-2">
                    <table class="table table-sm mb-0 small">
                        <thead><tr><th scope="col">Date</th><th scope="col">Day</th><th scope="col">Activity</th><th scope="col" class="text-end">Hours</th></tr></thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    <td class="text-nowrap">{{ $row['date_label'] }}</td>
                                    <td>{{ $row['day'] }}</td>
                                    <td>{{ $row['activity'] !== '' ? $row['activity'] : '—' }}</td>
                                    <td class="text-end text-nowrap">{{ $row['hours_label'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($entry->challenges)<div class="small mt-1"><strong>Difficulties encountered:</strong> {!! nl2br(e($entry->challenges)) !!}</div>@endif
                @if($entry->actions_taken)<div class="small mt-1"><strong>Actions taken:</strong> {!! nl2br(e($entry->actions_taken)) !!}</div>@endif
                @if($entry->learnings)<div class="small mt-1"><strong>Learning insights:</strong> {!! nl2br(e($entry->learnings)) !!}</div>@endif
                @if($entry->accomplishments)<div class="small mt-1"><strong>Accomplishments:</strong> {!! nl2br(e($entry->accomplishments)) !!}</div>@endif
                @if(! empty($entry->header_overrides))
                    @php $headerLabels = ['company' => 'Company / Agency', 'supervisor_name' => "Supervisor's Name", 'designation' => 'Designation', 'period_of_evaluation' => 'Period of Evaluation', 'inclusive_dates' => 'Inclusive Dates']; @endphp
                    <div class="small mt-2 text-muted"><i class="bi bi-pencil-square" aria-hidden="true"></i> Changed on this report only:
                        @foreach($entry->header_overrides as $field => $change)
                            <span class="d-block ms-3">{{ $headerLabels[$field] ?? $field }}: “{{ $change['value'] ?? '' }}” (default “{{ $change['default'] ?? '' }}”) by {{ $change['by_name'] ?? 'the student' }}@if(! empty($change['at'])), {{ \Carbon\Carbon::parse($change['at'])->timezone('Asia/Manila')->format('M j, Y g:i A') }}@endif</span>
                        @endforeach
                    </div>
                @endif

                @if($entry->isCertified())
                    <div class="small mt-2 text-muted"><i class="bi bi-patch-check" aria-hidden="true"></i> Certified true and correct by {{ $entry->reviewer?->name ?? 'the supervisor' }}@if($entry->reviewed_at) on {{ $entry->reviewed_at->timezone('Asia/Manila')->format('F j, Y') }}@endif.</div>
                @endif
                @if($entry->supervisor_comment)
                    <div class="small mt-2 p-2 rounded" style="background: rgba(13,110,253,.08);"><i class="bi bi-chat-quote" aria-hidden="true"></i> <strong>Supervisor:</strong> {{ $entry->supervisor_comment }}</div>
                @endif
                @if($entry->coordinator_review_status)
                    <div class="small mt-2 p-2 rounded {{ $entry->isReturnedByCoordinator() ? 'border border-danger' : 'border' }}" data-coordinator-review>
                        <i class="bi bi-mortarboard" aria-hidden="true"></i> <strong>Coordinator:</strong>
                        {{ $entry->isReturnedByCoordinator() ? 'Returned' : 'Reviewed' }}@if($entry->coordinator_comment) · {{ $entry->coordinator_comment }}@endif
                        @if($entry->isReturnedByCoordinator() && $mode === 'student')<span class="d-block text-muted">Delete this report and submit a corrected one for the same week.</span>@endif
                    </div>
                @endif

                @if($mode === 'student' && (! $entry->isCertified() || $entry->isReturnedByCoordinator()) && $placement->isActive())
                    <form method="POST" action="{{ route('student.ojt.journals.destroy', [$placement, $entry]) }}" class="mt-2" data-confirm="Delete this weekly report?" data-confirm-title="Delete report">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                    </form>
                @elseif($mode === 'employer' && ! $entry->isCertified())
                    <form method="POST" action="{{ route('employer.interns.journals.review', [$placement, $entry]) }}" class="mt-2">
                        @csrf @method('PATCH')
                        <label class="form-label small" for="comment-{{ $entry->id }}">Comment (optional)</label>
                        <textarea id="comment-{{ $entry->id }}" name="supervisor_comment" rows="2" class="form-control form-control-sm mb-2" maxlength="2000"></textarea>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-patch-check" aria-hidden="true"></i> Certify true and correct</button>
                    </form>
                @elseif($entry->isCertified() && ! $placement->isCleared() && in_array($mode, ['employer', 'coordinator'], true))
                    <form method="POST" action="{{ $mode === 'employer' ? route('employer.interns.journals.reopen', [$placement, $entry]) : route('coordinator.reviews.journals.reopen', $entry) }}" class="mt-2"
                          data-confirm="Reopen this certification? The supervisor will need to certify the report again." data-confirm-title="Reopen certification">
                        @csrf @if($mode === 'employer') @method('PATCH') @endif
                        <button type="submit" class="btn btn-sm btn-outline-danger">Reopen certification</button>
                    </form>
                @endif
            </article>
        @empty
            <x-empty-state icon="bi-journal-text" title="No weekly reports yet" message="Weekly activity narrative reports appear here once the student submits them." />
        @endforelse
    </div>
</div>
