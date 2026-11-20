{{-- Shared by the statistics page and its PDF export. The page passes $twoCol for two summary panels; the PDF keeps the plain table. --}}
@if(!empty($twoCol))
@php
    $panels = [
        ['title' => 'Enrollment and eligibility', 'hint' => 'Who is enrolled and ready to apply', 'icon' => 'bi-people', 'keys' => ['enrolled', 'eligible', 'approved', 'rejected']],
        ['title' => 'Placement and OJT', 'hint' => 'From placement to clearance', 'icon' => 'bi-briefcase', 'keys' => ['placed', 'pending_validation', 'ongoing', 'completed', 'cleared']],
    ];
    $enrolledTotal = max(1, (int) $stats['totals']['enrolled']);
@endphp
<div class="st-panels">
    @foreach($panels as $panel)
        <section class="st-panel">
            <header class="st-panel-head">
                <span class="st-panel-icon" aria-hidden="true"><i class="bi {{ $panel['icon'] }}"></i></span>
                <div class="st-panel-heading">
                    <h2 class="st-panel-title">{{ $panel['title'] }}</h2>
                    <p class="st-panel-hint">{{ $panel['hint'] }}</p>
                </div>
            </header>
            <ul class="st-panel-list">
                @foreach($panel['keys'] as $key)
                    @php
                        $count = (int) $stats['totals'][$key];
                        $share = min(100, (int) round($count / $enrolledTotal * 100));
                    @endphp
                    <li class="st-row {{ $count > 0 ? 'is-active' : 'is-zero' }}">
                        <span class="st-dot" aria-hidden="true"></span>
                        <span class="st-row-main">
                            <span class="st-row-label">{{ $labels[$key] }}</span>
                            <span class="st-row-meta">{{ $key === 'enrolled' ? 'All students in your department' : $share.'% of enrolled' }}</span>
                        </span>
                        <span class="st-count" data-total="{{ $key }}">{{ number_format($count) }}</span>
                        <span class="st-bar" aria-hidden="true"><i style="width: {{ $share }}%"></i></span>
                    </li>
                @endforeach
            </ul>
        </section>
    @endforeach
</div>
@else
<div class="table-responsive">
<table class="table table-sm stats-table">
    <tbody>
        @foreach($labels as $key => $label)
            <tr><th scope="row">{{ $label }}</th><td class="text-end" data-total="{{ $key }}">{{ number_format($stats['totals'][$key]) }}</td></tr>
        @endforeach
        <tr><th scope="row">Placement rate</th><td class="text-end" data-rate="placement">{{ $stats['rates']['placement'] }}%</td></tr>
        <tr><th scope="row">Completion rate</th><td class="text-end" data-rate="completion">{{ $stats['rates']['completion'] }}%</td></tr>
    </tbody>
</table>
</div>
@endif

@if(!empty($twoCol))
<div class="st-panels st-panels--tables">
<section class="st-panel st-panel--table">
    <header class="st-panel-head">
        <span class="st-panel-icon" aria-hidden="true"><i class="bi bi-building"></i></span>
        <div class="st-panel-heading">
            <h2 class="st-panel-title">Partner companies</h2>
            <p class="st-panel-hint">Interns hosted and how supervisors rated them</p>
        </div>
    </header>
<div class="table-responsive st-panel-table">
@else
<h3 class="h6 mt-4">Partner companies</h3>
<div class="table-responsive">
@endif
<table class="table table-sm stats-table">
    <thead><tr><th>Company</th><th class="text-end">Interns</th><th class="text-end">Completed</th><th class="text-end">Avg. evaluation</th><th class="text-end">Would hire</th></tr></thead>
    <tbody>
        @forelse($stats['companies'] as $row)
            <tr>
                <td class="st-name">{{ $row['company'] }}</td>
                <td class="text-end st-num">{{ $row['interns'] }}</td>
                <td class="text-end st-num {{ $row['completed'] ? '' : 'is-zero' }}">{{ $row['completed'] }}</td>
                <td class="text-end">{{ $row['average_score'] !== null ? number_format($row['average_score'], 2).' / 5' : '—' }}</td>
                <td class="text-end">{{ $row['would_hire'] !== null ? $row['would_hire'].'%' : '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="5">No placements yet.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
@if(!empty($twoCol))
</section>
@endif
@if(!empty($twoCol))
</div>
@endif
