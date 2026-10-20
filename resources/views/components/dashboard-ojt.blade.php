{{-- Role-aware OJT summary card for dashboards. --}}
@php
    use App\Enums\UserRole;
    use App\Models\AttendanceLog;
    use App\Models\Placement;

    $user = auth()->user();
    $card = null;

    if ($user?->isRole(UserRole::Student) && $user->student) {
        $placement = $user->student->placements()->with(['internship', 'employer', 'evaluation'])
            ->orderByRaw("case when status = 'active' then 0 else 1 end")->latest()->first();
        $card = ['type' => 'student', 'placement' => $placement];
    } elseif ($user?->isRole(UserRole::Employer) && $user->employer) {
        $placementIds = $user->employer->placements()->pluck('id');
        $card = [
            'type' => 'stats',
            'href' => route('employer.interns.index'),
            'stats' => [
                ['Ongoing interns', $user->employer->placements()->where('status', Placement::STATUS_ACTIVE)->count()],
                ['DTR entries to review', AttendanceLog::whereIn('placement_id', $placementIds)->where('status', AttendanceLog::STATUS_PENDING)->count()],
                ['Evaluations due', $user->employer->placements()->where('status', Placement::STATUS_ACTIVE)->whereDoesntHave('evaluation')->count()],
            ],
        ];
    } elseif ($user?->isRole(UserRole::Coordinator) && $user->coordinator) {
        $scoped = Placement::whereIn('student_id', $user->coordinator->scopedStudentsQuery()->select('id'));
        $card = [
            'type' => 'stats',
            'href' => route('coordinator.placements.index'),
            'stats' => [
                ['Students on OJT', (clone $scoped)->where('status', Placement::STATUS_ACTIVE)->count()],
                ['Awaiting clearance', (clone $scoped)->where('status', Placement::STATUS_COMPLETED)->whereNull('cleared_at')->count()],
                ['Cleared', (clone $scoped)->whereNotNull('cleared_at')->count()],
            ],
        ];
    } elseif ($user?->isRole(UserRole::Admin)) {
        $card = [
            'type' => 'stats',
            'href' => route('admin.placements.index'),
            'stats' => [
                ['Ongoing OJT', Placement::where('status', Placement::STATUS_ACTIVE)->count()],
                ['Awaiting clearance', Placement::where('status', Placement::STATUS_COMPLETED)->whereNull('cleared_at')->count()],
                ['Cleared', Placement::whereNotNull('cleared_at')->count()],
            ],
        ];
    }
@endphp

@if($card)
    <div class="card mb-4" data-dashboard-ojt>
        <div class="card-header">
            <h2><i class="bi bi-clock-history" aria-hidden="true"></i> OJT Progress</h2>
            <a href="{{ $card['type'] === 'student' ? route('student.ojt.index') : $card['href'] }}" class="sb-card-link">Open →</a>
        </div>
        <div class="card-body">
            @if($card['type'] === 'student')
                @if($placement = $card['placement'])
                    @php $percent = $placement->progressPercent(); @endphp
                    <div class="d-flex justify-content-between small mb-1">
                        <span>{{ $placement->internship?->title }} · {{ $placement->employer?->company_name }}</span>
                        <span><strong>{{ number_format($placement->approvedHours(), 1) }}</strong> / {{ $placement->required_hours }} h ({{ $percent }}%)</span>
                    </div>
                    <div class="progress" style="height: 10px;" role="progressbar" aria-label="OJT hours progress" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                        <div class="progress-bar {{ $percent >= 100 ? 'bg-success' : '' }}" style="width: {{ $percent }}%"></div>
                    </div>
                    <div class="small text-muted mt-2">Status: {{ $placement->statusLabel() }}@if($placement->isCleared()) — your certificate is ready.@endif</div>
                @else
                    <p class="text-muted mb-0">Your OJT record opens once an employer accepts your application.</p>
                @endif
            @else
                <div class="row text-center g-3">
                    @foreach($card['stats'] as [$label, $value])
                        <div class="col-4">
                            <div class="fs-4 fw-bold">{{ $value }}</div>
                            <div class="small text-muted">{{ $label }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endif
