@extends('layouts.app')

@section('title', 'Applicants')

@push('styles')
<style>
    .employer-page-shell {
        padding: 8px 0 0;
        color: var(--sb-text);
    }

    .employer-page-header {
        margin-bottom: 1.8rem;
    }

    .employer-page-title {
        margin: 0 0 8px;
        color: var(--sb-text);
        font-size: clamp(2rem, 2.6vw, 3rem);
        line-height: 1.1;
        font-weight: 800;
        letter-spacing: -0.03em;
    }

    .employer-page-subtitle {
        margin: 0;
        color: var(--sb-muted);
        font-size: 1rem;
        font-weight: 500;
    }

    .employer-page-shell .card {
        overflow: hidden;
        border: 1px solid var(--sb-border);
        border-radius: 16px;
        background: var(--sb-surface);
        box-shadow: 0 6px 20px rgba(120, 20, 30, 0.06);
    }

    .employer-page-shell .table-responsive {
        border-radius: 16px;
        overflow: hidden;
    }

    .employer-page-shell .table {
        --bs-table-bg: transparent;
        --bs-table-color: var(--sb-text);
        --bs-table-border-color: var(--sb-border);
        margin: 0;
        table-layout: fixed;
    }

    .employer-page-shell .table thead th {
        background: var(--sb-surface-raised) !important;
        color: var(--sb-muted) !important;
        border-color: var(--sb-border) !important;
        padding: 0.9rem 1rem;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .employer-page-shell .table td {
        border-color: var(--sb-border) !important;
        padding: 1.1rem 1rem;
        color: var(--sb-text) !important;
        vertical-align: middle;
        font-size: 0.95rem;
    }

    .employer-page-shell .table tbody tr:hover {
        background: var(--sb-brand-soft);
    }

    .employer-page-shell .student-name {
        color: var(--sb-text);
        font-weight: 700;
        line-height: 1.3;
    }

    .employer-page-shell .student-program {
        display: block;
        margin-top: 0.25rem;
        color: var(--sb-muted);
        font-size: 0.82rem;
        line-height: 1.4;
    }

    .employer-page-shell .table-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 70px;
        padding: 0.4rem 0.8rem;
        border-radius: 999px;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .employer-page-shell .table-badge.match-blue {
        background: rgba(37, 99, 235, 0.10);
        color: #1d4ed8;
        border: 1px solid rgba(37, 99, 235, 0.28);
    }

    .employer-page-shell .table-badge.match-green,
    .employer-page-shell .table-badge.status-reviewed {
        background: rgba(22, 163, 74, 0.10);
        color: #15803d;
        border: 1px solid rgba(22, 163, 74, 0.32);
    }

    .employer-page-shell .table-badge.status-submitted {
        background: var(--sb-surface-raised);
        color: var(--sb-muted);
        border: 1px solid var(--sb-border);
    }

    .employer-page-shell .review-btn {
        min-height: 36px;
        padding: 0.5rem 1.1rem;
        border: 1px solid var(--sb-brand);
        border-radius: 10px;
        background: var(--sb-brand) !important;
        color: #fff !important;
        font-size: 0.86rem;
        font-weight: 700;
    }

    .employer-page-shell .review-btn:hover {
        border-color: var(--sb-brand-deep);
        background: var(--sb-brand-deep) !important;
    }

    .employer-page-shell .card-footer {
        border-top: 1px solid var(--sb-border);
        background: var(--sb-surface-raised);
        padding: 0.9rem 1rem;
    }

    @media (max-width: 767.98px) {
        .employer-page-shell .table {
            min-width: 760px;
        }
    }
</style>
@endpush

@push('styles')
<style>
    /* List on the left, selected applicant on the right. Review opens the full page. */
    .ap-layout { display: grid; grid-template-columns: minmax(0, 2fr) minmax(360px, 1fr); gap: 28px; align-items: stretch; }
    .ap-list { display: flex; flex-direction: column; }
    .ap-list .table-responsive { flex: 1 1 auto; }
    .ap-layout .table { table-layout: auto; }
    .ap-layout .table td { padding-block: 1rem; }
    .ap-student { display: flex; align-items: baseline; justify-content: space-between; gap: 12px; }
    .ap-student .student-program { margin: 0; text-align: right; }
    .ap-row { cursor: pointer; transition: background-color 140ms ease; }
    .ap-row.is-selected { background: var(--sb-brand-soft); box-shadow: inset 4px 0 0 var(--sb-brand); }
    .ap-row:focus-visible { outline: 3px solid var(--sb-focus, #1d4ed8); outline-offset: -3px; }

    .ap-panel-wrap { display: flex; flex-direction: column; }
    .ap-panel { display: flex; flex: 1 1 auto; flex-direction: column; }
    .ap-panel[hidden] { display: none; }
    .ap-panel-body { flex: 1 1 auto; padding: 28px; }
    .ap-id { display: flex; align-items: center; gap: 18px; padding-bottom: 22px; border-bottom: 1px solid var(--sb-border); }
    .ap-avatar { display: grid; place-items: center; flex: none; width: 82px; height: 82px; border-radius: 50%; overflow: hidden; background: var(--sb-brand); color: #fff; font-size: 28px; font-weight: 800; }
    .ap-avatar img { width: 100%; height: 100%; object-fit: cover; }
    .ap-name { margin: 0; color: var(--sb-text); font-size: 24px; font-weight: 800; line-height: 1.15; letter-spacing: -.01em; }
    .ap-sub { margin: 4px 0 0; color: var(--sb-muted); font-size: 14px; }
    .ap-section { padding: 22px 0; border-bottom: 1px solid var(--sb-border); }
    .ap-section:last-child { border-bottom: 0; }
    .ap-label { margin: 0 0 14px; color: var(--sb-muted); font-size: 12px; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
    .ap-rows { display: grid; gap: 14px; margin: 0; }
    .ap-rows > div { display: grid; grid-template-columns: 110px minmax(0, 1fr); gap: 12px; align-items: baseline; }
    .ap-rows dt { color: var(--sb-muted); font-weight: 500; font-size: 15px; }
    .ap-rows dd { margin: 0; color: var(--sb-text); font-weight: 700; font-size: 15px; }
    .ap-rows dd.ap-big { font-size: 34px; font-weight: 800; line-height: 1; letter-spacing: -.02em; font-variant-numeric: tabular-nums; }
    .ap-rows dd .muted { color: var(--sb-muted); font-weight: 600; }
    .ap-skills { display: flex; flex-wrap: wrap; gap: 6px; }
    .ap-skills span { padding: 4px 11px; border-radius: 999px; background: var(--sb-brand-soft); color: var(--sb-brand-content); font-size: 13px; font-weight: 700; }
    .ap-foot { padding: 20px 28px; border-top: 1px solid var(--sb-border); }
    .ap-foot .review-btn { display: flex; align-items: center; justify-content: center; width: 100%; min-height: 52px; font-size: 1rem; border-radius: 12px; }
    .ap-empty { padding: 48px 24px; text-align: center; color: var(--sb-muted); }
    @media (max-width: 1199.98px) { .ap-layout { grid-template-columns: minmax(0, 1fr); } }

    /* ---- Fit the screen: compact header, one-line rows, panel scrolls inside instead of the page ---- */
    .employer-page-header { margin-bottom: 14px; }
    .employer-page-title { margin: 0 0 2px; font-size: clamp(24px, 2.4vw, 32px); }
    .employer-page-subtitle { font-size: .95rem; }
    .ap-layout { grid-template-columns: minmax(0, 1.7fr) minmax(340px, 1fr); gap: 16px; height: calc(100vh - 170px); min-height: 460px; }
    .ap-list, .ap-panel-wrap { min-height: 0; height: 100%; }
    .ap-list .table-responsive { overflow-y: auto; }
    .ap-layout .table thead th { position: sticky; top: 0; z-index: 1; padding: .7rem 1rem; }
    .ap-layout .table td { padding: .75rem 1rem; }
    .ap-student { display: block; }
    .ap-student .student-name { display: block; }
    .ap-student .student-program { margin: 2px 0 0; text-align: left; }
    .ap-layout .table td:nth-child(2) { min-width: 150px; }
    .ap-layout .table td:nth-child(5), .ap-layout .table th:nth-child(5) { white-space: nowrap; }
    .ap-panel { min-height: 0; height: 100%; }
    .ap-panel-body { padding: 20px 22px; overflow-y: auto; min-height: 0; }
    .ap-id { gap: 14px; padding-bottom: 14px; }
    .ap-avatar { width: 62px; height: 62px; font-size: 22px; }
    .ap-name { font-size: 20px; }
    .ap-sub { font-size: 13px; }
    .ap-section { padding: 14px 0; }
    .ap-label { margin-bottom: 10px; font-size: 11px; }
    .ap-rows { gap: 9px; }
    .ap-rows > div { grid-template-columns: 96px minmax(0, 1fr); }
    .ap-rows dt, .ap-rows dd { font-size: 14px; }
    .ap-rows dd.ap-big { font-size: 26px; }
    .ap-foot { padding: 14px 22px; }
    .ap-foot .review-btn { min-height: 46px; }
    @media (max-width: 1199.98px) { .ap-layout { height: auto; } .ap-list, .ap-panel-wrap, .ap-panel { height: auto; } .ap-panel-body { overflow: visible; } }

    /* ---- Table fits its column: fixed widths, name on 2 lines max, program on 1 line ---- */
    .ap-layout { grid-template-columns: minmax(0, 1.9fr) minmax(320px, 1fr); }
    .ap-layout .table { table-layout: fixed; width: 100%; }
    .ap-layout .table th:nth-child(1) { width: 30%; }
    .ap-layout .table th:nth-child(2) { width: 25%; }
    .ap-layout .table th:nth-child(3) { width: 13%; }
    .ap-layout .table th:nth-child(4) { width: 16%; }
    .ap-layout .table th:nth-child(5) { width: 16%; }
    .ap-layout .table thead th { padding: .7rem .65rem; font-size: .68rem; letter-spacing: .06em; }
    .ap-layout .table td { padding: .7rem .65rem; font-size: .88rem; overflow-wrap: anywhere; }
    .ap-layout .table td:nth-child(2) { min-width: 0; }
    .ap-layout .table-badge { min-width: 0; padding: .3rem .65rem; font-size: .76rem; }
    .ap-student .student-name { line-height: 1.25; }
    .ap-student .student-program { display: block; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; font-size: .76rem; }
    .ap-layout .table td:nth-child(5), .ap-layout .table th:nth-child(5) { white-space: normal; }

    /* final column widths: pills and dates stay on one line, text columns wrap */
    .ap-layout .table th:nth-child(1) { width: 26%; }
    .ap-layout .table th:nth-child(2) { width: 23%; }
    .ap-layout .table th:nth-child(3) { width: 12%; }
    .ap-layout .table th:nth-child(4) { width: 20%; }
    .ap-layout .table th:nth-child(5) { width: 19%; }
    .ap-layout .table td:nth-child(3), .ap-layout .table td:nth-child(4), .ap-layout .table td:nth-child(5) { overflow-wrap: normal; white-space: nowrap; }
    .ap-layout .table-badge { white-space: nowrap; }
    .ap-layout .table td:nth-child(5) { font-size: .84rem; }
</style>
@endpush

@section('content')
<div class="employer-page-shell">
    <div class="employer-page-header">
        <h1 class="employer-page-title">Applicants</h1>
        <p class="employer-page-subtitle">Review student applications</p>
    </div>

    <div class="ap-layout" data-live-region="applicants" data-live-topics="applications">
        <div class="card ap-list">
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
                            @php
                                $matchClass = $app->match_percentage >= 70 ? 'match-green' : 'match-blue';
                                $statusClass = strtolower($app->status->label()) === 'reviewed' ? 'status-reviewed' : 'status-submitted';
                            @endphp
                            <tr class="ap-row {{ $loop->first ? 'is-selected' : '' }}" tabindex="0" data-ap-row="{{ $app->id }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                                <td>
                                    <div class="ap-student">
                                        <span class="student-name">{{ $app->company_profile['name'] }}</span>
                                        <span class="student-program">{{ $app->company_profile['program'] ?? '—' }}</span>
                                    </div>
                                </td>
                                <td>{{ $app->internship->title }}</td>
                                <td><span class="table-badge {{ $matchClass }}">{{ $app->match_percentage }}%</span></td>
                                <td><span class="table-badge {{ $statusClass }}">{{ $app->status->label() }}</span></td>
                                <td class="text-nowrap">{{ $app->applied_at?->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No applicants yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($applications->hasPages())
                <div class="card-footer">{{ $applications->links() }}</div>
            @endif
        </div>

        <div class="card ap-panel-wrap" aria-label="Selected applicant" aria-live="polite">
            @forelse($applications as $app)
                @php
                    $c = $app->company_profile;
                    $matchClass = $app->match_percentage >= 70 ? 'match-green' : 'match-blue';
                    $statusClass = strtolower($app->status->label()) === 'reviewed' ? 'status-reviewed' : 'status-submitted';
                    $initials = collect(preg_split('/\s+/', trim($c['name'])))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
                    $topSkills = array_slice($c['skills'] ?? [], 0, 3);
                @endphp
                <div class="ap-panel" data-ap-panel="{{ $app->id }}" @unless($loop->first) hidden @endunless>
                    <div class="ap-panel-body">
                        <div class="ap-id">
                            <span class="ap-avatar" aria-hidden="true">@if(!empty($c['photo_url']))<img src="{{ $c['photo_url'] }}" alt="">@else{{ $initials }}@endif</span>
                            <div>
                                <h2 class="ap-name">{{ $c['name'] }}</h2>
                                <p class="ap-sub">{{ $c['program'] ?? '—' }}</p>
                            </div>
                        </div>

                        <div class="ap-section">
                            <p class="ap-label">Application</p>
                            <dl class="ap-rows employer-page-shell">
                                <div><dt>Internship</dt><dd>{{ $app->internship->title }}</dd></div>
                                <div><dt>Match</dt><dd class="ap-big">{{ $app->match_percentage }}%</dd></div>
                                <div><dt>Status</dt><dd><span class="table-badge {{ $statusClass }}">{{ $app->status->label() }}</span></dd></div>
                                <div><dt>Applied</dt><dd>{{ $app->applied_at?->format('M d, Y') ?? '—' }}</dd></div>
                            </dl>
                        </div>

                        <div class="ap-section">
                            <p class="ap-label">Student</p>
                            <dl class="ap-rows">
                                <div><dt>Program</dt><dd>{{ $c['program'] ?? '—' }}</dd></div>
                                <div><dt>Year level</dt><dd>{{ $c['year_level'] ?? '—' }}</dd></div>
                                <div><dt>Top skills</dt><dd>
                                    @if($topSkills)
                                        {{ implode(', ', $topSkills) }}
                                    @else
                                        <span class="muted">—</span>
                                    @endif
                                </dd></div>
                            </dl>
                        </div>
                    </div>
                    <div class="ap-foot employer-page-shell">
                        <a href="{{ route('employer.applicants.show', $app) }}" class="btn review-btn">Review</a>
                    </div>
                </div>
            @empty
                <div class="ap-empty">Applicants will appear here once students apply.</div>
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
