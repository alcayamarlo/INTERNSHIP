@extends('layouts.app')

@section('title', 'Applicants')

@section('content')
<link rel="stylesheet" href="{{ asset('css/applicant-side-card.css') }}?v={{ @filemtime(public_path('css/applicant-side-card.css')) }}">
{{-- Table on the left, the selected applicant on the right; Review opens the full page. Shared components only. --}}
<div class="sb-dash">

    <x-page-header title="Applicants" subtitle="Review student applications" />

    <x-table-filters :action="route('employer.applicants.index')" :filters="$filters" :options="$filterOptions"
                     :showing="$applications->total()" :total="$totalCount" target="#applicantResults" label="Filter applicants" />

    <div class="sb-split-table" id="applicantResults" data-tf-results data-live-region="applicants" data-live-topics="applications">
        <span data-tf-meta data-showing="{{ number_format($applications->total()) }}" data-total="{{ number_format($totalCount) }}" hidden></span>

        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Internship</th>
                            <th>Match</th>
                            <th>Status</th>
                            <th>Applied</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($applications as $app)
                            <tr class="sb-select-row {{ $loop->first ? 'is-selected' : '' }}" tabindex="0" data-ap-row="{{ $app->id }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                <td>
                                    <div class="sb-person-text">
                                        <strong>{{ $app->company_profile['name'] }}</strong>
                                        <span>{{ $app->company_profile['program'] ?? '—' }}</span>
                                    </div>
                                </td>
                                <td>{{ $app->internship->title }}</td>
                                <td><span class="sb-tag">{{ $app->match_percentage }}%</span></td>
                                <td><x-status-badge :status="$app->status->value" :label="$app->status->companyLabel()" /></td>
                                <td class="text-nowrap">{{ $app->applied_at?->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="sb-table-empty">
                                    <x-empty-state icon="bi-people" :title="$filters->active() ? 'No results found. Try other filters or clear them.' : 'No applicants yet.'" />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($applications->hasPages())
                <div class="card-footer">{{ $applications->links() }}</div>
            @endif
        </div>

        <div class="card sb-side-panel apc-card" aria-label="Selected applicant" aria-live="polite">
            @forelse($applications as $app)
                @php
                    $c = $app->company_profile;
                    $initials = collect(preg_split('/\s+/', trim($c['name'])))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
                    $topSkills = array_slice($c['skills'] ?? [], 0, 3);
                @endphp
                @php
                    $statusKey = $app->status->value;
                    $pillTone = match (true) {
                        in_array($statusKey, ['accepted', 'completed'], true) => 'dark',
                        in_array($statusKey, ['rejected', 'withdrawn'], true) => 'danger',
                        $statusKey === 'interview' => 'info',
                        in_array($statusKey, ['applied', 'submitted', 'reviewed'], true) => 'warn',
                        default => 'neutral',
                    };
                    $allSkills = array_values($c['skills'] ?? []);
                    $shownSkills = array_slice($allSkills, 0, 3);
                    $moreSkills = max(0, count($allSkills) - 3);
                    $identityLine = trim(($c['program'] ?? '').(! empty($c['year_level']) ? ' · '.$c['year_level'] : ''));
                    $matchWidth = max(0, min(100, (int) $app->match_percentage));
                @endphp
                <div class="sb-side-panel-item apc-item" data-ap-panel="{{ $app->id }}" @unless($loop->first) hidden @endunless>
                    <div class="apc-head">
                        <div class="apc-band" aria-hidden="true"></div>
                        <div class="apc-identity">
                            <span class="apc-avatar" aria-hidden="true">@if(!empty($c['photo_url']))<img src="{{ $c['photo_url'] }}" alt="">@else{{ $initials }}@endif</span>
                            <h2 class="apc-name">{{ $c['name'] }}</h2>
                            @if($identityLine !== '')<p class="apc-sub">{{ $identityLine }}</p>@endif
                            <span class="apc-pill apc-pill--{{ $pillTone }}">{{ $app->status->companyLabel() }}</span>
                        </div>
                    </div>
                    <dl class="apc-details">
                        <div class="apc-row"><dt>Applied for</dt><dd class="apc-strong">{{ $app->internship->title }}</dd></div>
                        <div class="apc-row"><dt>Applied</dt><dd>{{ $app->applied_at?->format('M d, Y · h:i A') ?? '—' }}</dd></div>
                        <div class="apc-row">
                            <dt>Match score</dt>
                            <dd class="apc-score"><span class="apc-score__num">{{ $app->match_percentage }}%</span><span class="apc-meter" aria-hidden="true"><span class="apc-meter__fill" style="width: {{ $matchWidth }}%"></span></span></dd>
                        </div>
                        @if($shownSkills)
                            <div class="apc-row">
                                <dt>Top skills</dt>
                                <dd><ul class="apc-chips">@foreach($shownSkills as $skill)<li>{{ $skill }}</li>@endforeach @if($moreSkills > 0)<li class="apc-chips__more">+{{ $moreSkills }}</li>@endif</ul></dd>
                            </div>
                        @endif
                    </dl>
                    <div class="apc-actions">
                        <a href="{{ route('employer.applicants.show', $app) }}" class="btn btn-primary apc-review">Review</a>
                    </div>
                </div>
            @empty
                <div class="card-body">
                    <x-empty-state icon="bi-person" :title="$filters->active() ? 'No results found.' : 'Applicants will appear here once students apply.'" />
                </div>
            @endforelse
        </div>
    </div>
</div>

<script>
    (function () {
        // Delegated, so it keeps working if the live region is refreshed.
        function select(id) {
            document.querySelectorAll('[data-ap-row]').forEach(function (r) {
                var on = r.getAttribute('data-ap-row') === id;
                r.classList.toggle('is-selected', on);
                r.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            document.querySelectorAll('[data-ap-panel]').forEach(function (p) { p.hidden = p.getAttribute('data-ap-panel') !== id; });
        }
        document.addEventListener('click', function (e) {
            var row = e.target.closest('[data-ap-row]');
            if (row) select(row.getAttribute('data-ap-row'));
        });
        document.addEventListener('keydown', function (e) {
            if (e.key !== 'Enter' && e.key !== ' ') return;
            var row = e.target.closest && e.target.closest('[data-ap-row]');
            if (row && e.target === row) { e.preventDefault(); select(row.getAttribute('data-ap-row')); }
        });
    })();
</script>
@endsection
