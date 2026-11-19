{{-- Placement monitoring table. Expects $placements (paginator) and $showRoute (route name taking a placement).
     The coordinator page also passes $dailyLog (OjtReminderService::dailyLogState), which adds "Remind to log time". --}}
<div class="card" data-live-region="placement-table" data-live-topics="placements">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle sb-table-stack">
            <thead>
                <tr>
                    <th scope="col">Student</th>
                    <th scope="col">Company / internship</th>
                    <th scope="col">Started</th>
                    <th scope="col">Hours</th>
                    <th scope="col">Evaluation</th>
                    <th scope="col">Status</th>
                    <th scope="col"><span class="visually-hidden">Actions</span></th>
                </tr>
            </thead>
            <tbody>
                @forelse($placements as $placement)
                    @php $percent = $placement->progressPercent(); @endphp
                    <tr>
                        <td class="sb-stack-block"><span class="sb-row-title">{{ $placement->student?->user?->name ?? '—' }}</span><div class="sb-row-sub text-nowrap">{{ $placement->student?->student_id_number }}</div></td>
                        <td data-label="Company" class="sb-stack-block">{{ $placement->employer?->company_name ?? '—' }}<div class="sb-row-sub">{{ $placement->internship?->title }}</div></td>
                        <td data-label="Started" class="text-nowrap">{{ $placement->start_date?->format('M d, Y') }}</td>
                        <td data-label="Hours" class="sb-stack-block sb-progress-cell">
                            <div class="small">{{ number_format($placement->approvedHours(), 1) }} / {{ $placement->required_hours }} h</div>
                            <div class="progress progress--thin" role="progressbar" aria-label="Hours progress" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar" style="width: {{ $percent }}%"></div></div>
                        </td>
                        <td data-label="Evaluation">{{ $placement->evaluation ? number_format((float) $placement->evaluation->average_score, 2).' / 5' : '—' }}</td>
                        <td data-label="Status" class="sb-stack-block"><x-status-badge :status="$placement->isCleared() ? 'verified' : (($placement->isCompleted() || $placement->isPendingValidation()) ? 'pending' : 'active')" :label="$placement->isCompleted() && ! $placement->isCleared() ? 'For clearance' : $placement->statusLabel()" />@if($risk = $placement->riskReason())<span class="sb-status sb-status--danger mt-1" title="{{ $risk }}"><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> At risk</span><span class="sb-row-sub">{{ $risk }}</span>@endif
                            @php $due = collect($deadlines[$placement->id] ?? []); $overdueCount = $due->where('overdue', true)->count(); $next = $due->where('overdue', false)->sortBy(fn ($item) => $item['due_on']->timestamp)->first(); @endphp
                            @if($overdueCount)<span class="sb-status sb-status--danger mt-1" title="{{ $due->where('overdue', true)->pluck('label')->implode(', ') }}">{{ $overdueCount }} overdue</span>@elseif($next)<span class="sb-status sb-status--warning mt-1" title="{{ $next['label'] }}">Due {{ $next['due_on']->format('M j') }}</span>@endif</td>
                        <td class="sb-stack-block text-end text-nowrap" style="width:1%">
                            @php $log = isset($dailyLog) ? ($dailyLog[$placement->id] ?? null) : null; @endphp
                            @if($log && $log['expected'] && ! $log['logged'])
                                @if($log['next'])
                                    <button type="button" class="btn btn-sm btn-outline-secondary text-nowrap" disabled title="Reminded {{ $log['last']->timezone('Asia/Manila')->format('g:i A') }}. You can remind again after {{ $log['next']->timezone('Asia/Manila')->format('g:i A') }}."><i class="bi bi-bell" aria-hidden="true"></i> Again {{ $log['next']->timezone('Asia/Manila')->format('g:i A') }}</button>
                                @else
                                    <form method="POST" action="{{ route('coordinator.placements.remind-daily-log', $placement) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary text-nowrap" title="No time logged today"><i class="bi bi-bell" aria-hidden="true"></i> Remind to log time</button>
                                    </form>
                                @endif
                            @endif
                            <a href="{{ route($showRoute, $placement) }}" class="btn btn-sm btn-outline-primary text-nowrap"><i class="bi bi-eye" aria-hidden="true"></i> View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="sb-stack-block"><x-empty-state icon="bi-clock-history" title="No placements found" message="Placements open automatically when employers accept student applications." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($placements->hasPages())<div class="card-footer">{{ $placements->links() }}</div>@endif
</div>
