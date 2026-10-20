{{-- Placement monitoring table. Expects $placements (paginator) and $showRoute (route name taking a placement). --}}
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
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
                        <td><strong>{{ $placement->student?->user?->name ?? '—' }}</strong><div class="small text-muted">{{ $placement->student?->student_id_number }}</div></td>
                        <td>{{ $placement->employer?->company_name ?? '—' }}<div class="small text-muted">{{ $placement->internship?->title }}</div></td>
                        <td class="text-nowrap">{{ $placement->start_date?->format('M d, Y') }}</td>
                        <td style="min-width: 150px;">
                            <div class="small">{{ number_format($placement->approvedHours(), 1) }} / {{ $placement->required_hours }} h</div>
                            <div class="progress" style="height: 6px;" role="progressbar" aria-label="Hours progress" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100"><div class="progress-bar {{ $percent >= 100 ? 'bg-success' : '' }}" style="width: {{ $percent }}%"></div></div>
                        </td>
                        <td>{{ $placement->evaluation ? number_format((float) $placement->evaluation->average_score, 2).' / 5' : '—' }}</td>
                        <td><x-status-badge :status="$placement->isCleared() ? 'verified' : ($placement->isCompleted() ? 'pending' : 'active')" :label="$placement->isCompleted() && ! $placement->isCleared() ? 'For clearance' : $placement->statusLabel()" /></td>
                        <td><a href="{{ route($showRoute, $placement) }}" class="btn btn-sm btn-outline-primary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7"><x-empty-state icon="bi-clock-history" title="No placements found" message="Placements open automatically when employers accept student applications." /></td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($placements->hasPages())<div class="card-footer">{{ $placements->links() }}</div>@endif
</div>
