@extends('layouts.app')

@section('title', 'Applicants')

@section('content')
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

        <div class="card sb-side-panel" aria-label="Selected applicant" aria-live="polite">
            @forelse($applications as $app)
                @php
                    $c = $app->company_profile;
                    $initials = collect(preg_split('/\s+/', trim($c['name'])))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
                    $topSkills = array_slice($c['skills'] ?? [], 0, 3);
                @endphp
                <div class="sb-side-panel-item" data-ap-panel="{{ $app->id }}" @unless($loop->first) hidden @endunless>
                    <div class="card-body">
                        <div class="sb-person">
                            <span class="sb-initials sb-initials--lg" aria-hidden="true">@if(!empty($c['photo_url']))<img src="{{ $c['photo_url'] }}" alt="">@else{{ $initials }}@endif</span>
                            <div class="sb-person-text">
                                <h2 class="sb-detail-title">{{ $c['name'] }}</h2>
                                <span>{{ $c['program'] ?? '—' }}</span>
                            </div>
                        </div>
                        <div class="sb-block">
                            <p class="sb-block-title">Application</p>
                            <dl class="sb-fields">
                                <div><dt>Internship</dt><dd>{{ $app->internship->title }}</dd></div>
                                <div><dt>Match</dt><dd class="is-big">{{ $app->match_percentage }}%</dd></div>
                                <div><dt>Status</dt><dd><x-status-badge :status="$app->status->value" :label="$app->status->companyLabel()" /></dd></div>
                                <div><dt>Applied</dt><dd>{{ $app->applied_at?->format('M d, Y') ?? '—' }}</dd></div>
                            </dl>
                        </div>
                        <div class="sb-block">
                            <p class="sb-block-title">Student</p>
                            <dl class="sb-fields">
                                <div><dt>Program</dt><dd>{{ $c['program'] ?? '—' }}</dd></div>
                                <div><dt>Year level</dt><dd>{{ $c['year_level'] ?? '—' }}</dd></div>
                                <div><dt>Top skills</dt><dd>
                                    @if($topSkills)
                                        {{ implode(', ', $topSkills) }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </dd></div>
                            </dl>
                        </div>
                    </div>
                    <div class="card-footer">
                        <a href="{{ route('employer.applicants.show', $app) }}" class="btn btn-primary w-100">Review</a>
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
