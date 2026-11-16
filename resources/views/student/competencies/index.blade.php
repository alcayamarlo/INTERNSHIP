@extends('layouts.app')

@section('title', 'My Certificates')


@section('content')
@php
    $initialCategory = $overview['category_filter'];
    $initialScores = collect($overview['groups'])->mapWithKeys(fn ($group, $key) => [$key => $group['score']])->all();
    $initialFiltered = collect($overview['skills'])->filter(fn ($skill) => ! $initialCategory
        || in_array($initialCategory, $skill['group_keys'], true)
        || ($initialCategory === '__other' && $skill['group_keys'] === []))->values();
    $selectedSkill = $initialFiltered->first();
    $axisLabels = ['technical' => 'Technical', 'soft' => 'Soft skills', 'certification' => 'Certificates'];
@endphp
<div class="sb-dash" id="competenciesOverview"
    data-loading="true"
    aria-busy="true"
    data-category="{{ $initialCategory ?? '' }}"
    data-skills="{{ json_encode($overview['skills']) }}"
    data-groups="{{ json_encode($overview['groups']) }}"
    data-selected-id="{{ $selectedSkill['id'] ?? '' }}"
    data-flash-success="{{ session('success') }}">
    <header class="sb-page-header">
        <div>
            <h1 class="">My Certificates</h1>
            <p class="sb-note">Certificates you earned and the skills you learned in them.</p>
        </div>
        <button type="button" class="btn btn-primary" data-wizard-open>+ Add a certificate</button>
    </header>

    {{-- Profile summary: strength and category scores. The radar chart opens below it. --}}
    <section class="card sb-summary" aria-labelledby="profileStrengthLabel">
        <div class="sb-summary-row">
            <div class="sb-summary-lead">
                <div class="sb-tile-label" id="profileStrengthLabel">Profile strength</div>
                <div class="sb-summary-main">
                    <div class="sb-summary-value" id="profileStrengthLevel">{{ $overview['profile_strength']['level_label'] }}</div>
                    <div class="sb-summary-note sb-num" id="profileStrengthAverage">
                        @if($overview['profile_strength']['average_points'] !== null)
                            {{ number_format($overview['profile_strength']['average_points'] / 25, 1) }} avg
                        @else
                            No counted skills
                        @endif
                    </div>
                </div>
            </div>
            <div class="sb-tiles sb-tiles--inline" aria-label="Category scores">
                @foreach($overview['groups'] as $key => $group)
                    <div class="sb-tile">
                        <span class="sb-tile-label">{{ $group['label'] }}</span>
                        <span class="sb-tile-value sb-num" data-stat-value="{{ $key }}">{{ number_format($initialScores[$key] ?? 0, 1) }}%</span>
                    </div>
                @endforeach
            </div>
            <button type="button" class="btn btn-outline-secondary" id="competencyChartToggle"
                    aria-expanded="false" aria-controls="competencyChartPanel">
                <span data-chart-toggle-label>Show chart</span>
                <i class="bi bi-chevron-down" aria-hidden="true"></i>
            </button>
        </div>

        <div class="sb-chart-panel" id="competencyChartPanel" hidden>
            <h2 class="visually-hidden" id="competencyRadarTitle">Certificate profile</h2>
            <div class="sb-radar-wrap">
                <svg class="sb-radar" id="competencyRadar" viewBox="0 0 400 320" role="img"
                     aria-label="Certificate scores: Technical {{ number_format($initialScores['technical'] ?? 0, 1) }} percent, Soft skills {{ number_format($initialScores['soft'] ?? 0, 1) }} percent, Certificates {{ number_format($initialScores['certification'] ?? 0, 1) }} percent">
                    <g class="sb-radar-grid" id="radarGrid"></g>
                    <g class="sb-radar-axes" id="radarAxes"></g>
                    <polygon id="radarPrimaryShape" class="sb-radar-shape" points="200,150 200,150 200,150"></polygon>
                    <g id="radarVertices"></g>
                </svg>
                <div class="sb-radar-tooltip" id="radarVertexTooltip" role="tooltip" aria-hidden="true"></div>
                <span class="visually-hidden" id="competencyChartAnnouncement" aria-live="polite" aria-atomic="true"></span>
                @foreach($axisLabels as $axis => $label)
                    @php($group = $overview['groups'][$axis])
                    <button type="button" class="sb-radar-axis" data-axis="{{ $axis }}" data-score="{{ $initialScores[$axis] ?? 0 }}"
                            aria-pressed="{{ $initialCategory === $axis ? 'true' : 'false' }}"
                            aria-label="{{ $label }}: {{ number_format($initialScores[$axis] ?? 0, 1) }} percent">
                        <span class="sb-radar-axis-name">{{ $label }}</span>
                        <span class="sb-radar-axis-value sb-num">{{ number_format($initialScores[$axis] ?? 0, 1) }}%</span>
                        <span class="sb-radar-axis-tip" role="tooltip" data-axis-tooltip="{{ $axis }}">{{ $group['points'] }} of {{ $group['target'] * 100 }} points</span>
                    </button>
                @endforeach
            </div>
        </div>
    </section>

    <div class="sb-layout-2">
        <div class="sb-dash">
            <section class="card sb-table-card" aria-labelledby="skillListTitle">
                <h2 class="visually-hidden" id="skillListTitle">Your certificates</h2>
                <div class="sb-pill-tabs" role="group" aria-label="Filter certificates">
                    <button type="button" class="sb-pill-tab" data-category="" aria-pressed="{{ $initialCategory ? 'false' : 'true' }}">All</button>
                    @foreach($overview['groups'] as $key => $group)
                        <button type="button" class="sb-pill-tab" data-category="{{ $key }}" aria-pressed="{{ $initialCategory === $key ? 'true' : 'false' }}">{{ $group['label'] }}</button>
                    @endforeach
                </div>

                <div class="sb-empty {{ $overview['skills'] ? 'd-none' : '' }}" id="competencyEmptyState">
                    <h3 class="sb-empty-title">No certificates yet</h3>
                    <p>You have no certificates yet. Add a certificate you earned, then list the skills you learned in it.</p>
                    <button type="button" class="btn btn-primary" data-wizard-open>+ Add a certificate</button>
                </div>

                <div class="sb-fade-list {{ $initialFiltered->isEmpty() ? 'd-none' : '' }}" id="competencySkillList">
                    <div class="sb-skeletons" aria-hidden="true">
                        <span class="sb-skeleton"></span>
                        <span class="sb-skeleton"></span>
                        <span class="sb-skeleton"></span>
                    </div>
                    <table class="table table-hover align-middle sb-table-stack">
                        <thead>
                            <tr>
                                <th scope="col">Certificate</th>
                                <th scope="col">Type</th>
                                <th scope="col">Category</th>
                                <th scope="col">Level</th>
                            </tr>
                        </thead>
                        <tbody id="competencyTableBody">
                            @foreach($initialFiltered as $skill)
                                @php($isSelected = (string) ($selectedSkill['id'] ?? '') === (string) $skill['id'])
                                <tr class="sb-select-row {{ $isSelected ? 'is-selected' : '' }}" data-skill-id="{{ $skill['id'] }}">
                                    <td data-label="Certificate">
                                        <button type="button" class="sb-row-button" aria-pressed="{{ $isSelected ? 'true' : 'false' }}">{{ $skill['name'] }}</button>
                                        @if($skill['issuing_organization'] ?? null)
                                            <span class="sb-row-sub">{{ $skill['issuing_organization'] }}</span>
                                        @endif
                                    </td>
                                    <td data-label="Type">{{ $skill['type_label'] }}</td>
                                    <td data-label="Category">{{ $skill['skill_type_label'] ?: '—' }}</td>
                                    <td data-label="Level">{{ $skill['proficiency_label'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="sb-empty {{ ($overview['skills'] && $initialFiltered->isEmpty()) ? '' : 'd-none' }}" id="competencyNoResults">
                    <p class="sb-empty-title">No certificates match this filter.</p>
                    <button type="button" class="btn btn-primary" data-wizard-open>+ Add a certificate</button>
                </div>
            </section>

            @if($legacyCertificates->isNotEmpty())
                <section class="card" aria-labelledby="legacyCertificatesTitle" id="legacyCertificates">
                    <h2 class="sb-card-title" id="legacyCertificatesTitle">Older certificates</h2>
                    <p class="sb-note">These came from your Documents tab. Add the type, skill type, level, and skills so they count in your chart and match score.</p>
                    <ul class="sb-rows sb-rows--card">
                        @foreach($legacyCertificates as $legacy)
                            <li class="" data-legacy-id="{{ $legacy['id'] }}">
                                <div>
                                    <span class="sb-row-title">{{ $legacy['title'] }}</span>
                                    <span class="sb-row-sub">{{ $legacy['issuer'] ?: 'Issuer not added' }} · Needs details</span>
                                </div>
                                <div class="sb-actions sb-actions--tight">
                                    @if($legacy['file_url'])
                                        <a class="btn btn-outline-secondary" href="{{ $legacy['file_url'] }}" target="_blank" rel="noopener">View</a>
                                    @endif
                                    <button type="button" class="btn btn-primary" data-wizard-legacy="{{ json_encode($legacy) }}">Add details</button>
                                    <form method="POST" action="{{ $legacy['delete_url'] }}" data-certificate-delete data-confirm="Delete this certificate?" data-confirm-title="Delete certificate">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-secondary">Delete</button>
                                    </form>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <aside class="card" aria-labelledby="competencyDetailTitle">
            <h2 class="sb-card-title" id="competencyDetailTitle">Selected certificate</h2>
            <div class="sb-detail-body" id="competencyDetailContent">
                @if($selectedSkill)
                    <h3 class="sb-detail-title">{{ $selectedSkill['name'] }}</h3>
                @else
                    <div class="sb-note" id="competencyDetailEmpty">Select a certificate to view its details.</div>
                @endif
            </div>
        </aside>
    </div>
    <div class="save-toast" id="certificateToast" role="status" aria-live="polite"></div>
</div>

@include('student.competencies.partials.wizard')
@endsection

@push('scripts')
<script src="{{ asset('js/certificate-wizard.js') }}?v={{ @filemtime(public_path('js/certificate-wizard.js')) }}" defer></script>
@endpush

@push('scripts')
<script>
(() => {
    const root = document.getElementById('competenciesOverview');
    if (!root) return;

    const svgNs = 'http://www.w3.org/2000/svg';
    const skills = JSON.parse(root.dataset.skills || '[]');
    const groups = JSON.parse(root.dataset.groups || '{}');
    const angleByGroup = { technical: -90, soft: 30, certification: 150 };
    const center = { x: 200, y: 150 };
    const radius = 82;
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const duration = (token) => Number.parseFloat(getComputedStyle(root).getPropertyValue(token)) || 0;
    const rowStaggerMs = reduceMotion ? 0 : duration('--sb-delay-row');
    const levelStaggerMs = reduceMotion ? 0 : duration('--sb-delay-level');
    const initialRevealMs = reduceMotion ? 0 : duration('--sb-dur-hero') / 2;
    const radar = document.getElementById('competencyRadar');
    const radarWrap = radar.closest('.sb-radar-wrap');
    const grid = document.getElementById('radarGrid');
    const axes = document.getElementById('radarAxes');
    const primaryShape = document.getElementById('radarPrimaryShape');
    const vertices = document.getElementById('radarVertices');
    const vertexTooltip = document.getElementById('radarVertexTooltip');
    const chartAnnouncement = document.getElementById('competencyChartAnnouncement');
    const list = document.getElementById('competencySkillList');
    const tableBody = document.getElementById('competencyTableBody');
    const noResults = document.getElementById('competencyNoResults');
    const detail = document.getElementById('competencyDetailContent');
    const levels = [
        { key: 'beginner', label: 'Beginner', points: 25 },
        { key: 'intermediate', label: 'Intermediate', points: 50 },
        { key: 'advanced', label: 'Advanced', points: 75 },
        { key: 'expert', label: 'Expert', points: 100 }
    ];
    let category = root.dataset.category || '';
    let selectedId = root.dataset.selectedId || '';
    let currentScores = {};
    let activeTweens = new WeakMap();
    let announceTimer = 0;
    let strengthLabelTimer = 0;
    let listRenderTimer = 0;
    let listRenderVersion = 0;
    let hydrationTimer = 0;

    const finishHydration = () => {
        window.clearTimeout(hydrationTimer);
        root.dataset.loading = 'false';
        root.setAttribute('aria-busy', 'false');
    };

    const groupScore = (group) => Number(groups[group]?.score ?? 0);

    const fmt = (value) => `${Number(value).toFixed(1)}%`;
    let currentAveragePoints = 0;
    const point = (angle, value) => {
        const radians = angle * Math.PI / 180;
        const distance = radius * Math.max(0, Math.min(100, Number(value))) / 100;
        return { x: center.x + Math.cos(radians) * distance, y: center.y + Math.sin(radians) * distance };
    };
    const pointsFor = (value) => Object.keys(angleByGroup)
        .map((key) => point(angleByGroup[key], typeof value === 'number' ? value : (value[key] || 0)))
        .map((pointValue) => `${pointValue.x.toFixed(2)},${pointValue.y.toFixed(2)}`)
        .join(' ');

    const tween = (element, from, to, duration, easing, update) => {
        const previous = activeTweens.get(element);
        if (previous) cancelAnimationFrame(previous);
        if (reduceMotion) {
            update(to);
            return;
        }
        const start = performance.now();
        const frame = (now) => {
            const progress = Math.min(1, (now - start) / duration);
            update(from + (to - from) * easing(progress));
            if (progress < 1) {
                activeTweens.set(element, requestAnimationFrame(frame));
            } else {
                activeTweens.delete(element);
                update(to);
            }
        };
        activeTweens.set(element, requestAnimationFrame(frame));
    };
    const cancelTween = (element) => {
        const previous = activeTweens.get(element);
        if (previous) cancelAnimationFrame(previous);
        activeTweens.delete(element);
    };
    const easeOut = (value) => 1 - Math.pow(1 - value, 3);
    const easeInOut = (value) => value < .5 ? 4 * value * value * value : 1 - Math.pow(-2 * value + 2, 3) / 2;

    const updatePolygon = (shape, scores) => { shape.setAttribute('points', pointsFor(scores)); };
    const buildRadarGrid = () => {
        [33, 67, 100].forEach((value) => {
            const ring = document.createElementNS(svgNs, 'polygon');
            ring.setAttribute('class', `sb-radar-ring${value === 100 ? ' sb-radar-ring--outer' : ''}`);
            ring.setAttribute('points', pointsFor(value));
            grid.append(ring);
        });
        Object.keys(angleByGroup).forEach((key) => {
            const endpoint = point(angleByGroup[key], 100);
            const axis = document.createElementNS(svgNs, 'line');
            axis.setAttribute('class', 'sb-radar-axis-line');
            axis.setAttribute('x1', center.x);
            axis.setAttribute('y1', center.y);
            axis.setAttribute('x2', endpoint.x.toFixed(2));
            axis.setAttribute('y2', endpoint.y.toFixed(2));
            axes.append(axis);
        });
    };
    const announceChart = (scores) => {
        const label = `Certificate scores: Technical ${fmt(scores.technical)}, Soft skills ${fmt(scores.soft)}, Certificates ${fmt(scores.certification)}`;
        radar.setAttribute('aria-label', label);
        chartAnnouncement.textContent = label;
    };
    const vertexTooltipText = (key) => {
        const groupSkills = skills.filter((skill) => (skill.group_keys || []).includes(key));
        const points = groupSkills.reduce((sum, skill) => sum + Number(skill.proficiency_points || 0), 0);
        return `${points} of ${Number(groups[key]?.target || 0) * 100} points`;
    };
    const showVertexTooltip = (key) => {
        const coordinates = point(angleByGroup[key], currentScores[key] || 0);
        const wrapRect = radarWrap.getBoundingClientRect();
        const svgRect = radar.getBoundingClientRect();
        const tooltipWidth = vertexTooltip.offsetWidth;
        const angle = angleByGroup[key] * Math.PI / 180;
        const inwardX = Math.cos(angle) * 36;
        const inwardY = Math.sin(angle) * 36;
        const x = svgRect.left - wrapRect.left + (coordinates.x / 400) * svgRect.width - inwardX;
        const y = svgRect.top - wrapRect.top + (coordinates.y / 320) * svgRect.height - inwardY;
        const safeX = Math.max(tooltipWidth / 2 + 8, Math.min(wrapRect.width - tooltipWidth / 2 - 8, x));
        vertexTooltip.textContent = vertexTooltipText(key);
        vertexTooltip.style.left = `${safeX}px`;
        vertexTooltip.style.top = `${y}px`;
        vertexTooltip.classList.add('is-visible');
    };
    const hideVertexTooltip = () => vertexTooltip.classList.remove('is-visible');

    const renderDetail = (skill) => {
        if (!skill) {
            detail.innerHTML = '<div class="sb-note">Select a certificate to view its details.</div>';
            return;
        }

        detail.innerHTML = '';
        const content = document.createElement('div');
        content.className = 'sb-detail-body is-changing';
        const head = document.createElement('div');
        head.className = 'sb-detail-head';
        const headingWrap = document.createElement('div');
        const name = document.createElement('h3');
        name.className = 'sb-detail-title';
        name.textContent = skill.name;
        headingWrap.append(name);
        if (skill.issuing_organization) {
            const issuer = document.createElement("p");
            issuer.className = "sb-detail-meta";
            issuer.textContent = skill.issuing_organization;
            headingWrap.append(issuer);
        }
        const tags = document.createElement("div");
        tags.className = "sb-chips";
        [skill.type_label, skill.skill_type_label].filter(Boolean).forEach((text) => {
            const tag = document.createElement("span");
            tag.className = "";
            tag.textContent = text;
            tags.append(tag);
        });
        headingWrap.append(tags);
        head.append(headingWrap);
        content.append(head);

        if (skill.description) {
            const description = document.createElement('p');
            description.className = 'sb-detail-text';
            description.textContent = skill.description;
            content.append(description);
        }

        const levelList = document.createElement('div');
        levelList.className = 'sb-levels';
        levelList.setAttribute('aria-label', 'Proficiency levels');
        levels.forEach((level, index) => {
            const row = document.createElement('div');
            row.className = `sb-level${skill.proficiency_key === level.key ? ' is-current' : ''}`;
            const label = document.createElement('span');
            label.className = 'sb-level-name';
            label.textContent = level.label;
            const track = document.createElement('span');
            track.className = 'sb-level-track';
            const fill = document.createElement('span');
            fill.className = 'sb-level-fill';
            fill.style.setProperty('--sb-fill-delay', `${index * levelStaggerMs}ms`);
            fill.style.setProperty('--sb-bar', Number(skill.proficiency_points || 0) >= level.points ? '1' : '0');
            track.append(fill);
            const score = document.createElement('span');
            score.className = 'sb-level-score sb-num';
            score.textContent = String(level.points);
            row.append(label, track, score);
            levelList.append(row);
        });
        content.append(levelList);

        if ((skill.learned_skills || []).length) {
            const learnedTitle = document.createElement("h4");
            learnedTitle.className = "sb-block-title";
            learnedTitle.textContent = "Skills you learned";
            const learned = document.createElement("ul");
            learned.className = "sb-bullets";
            skill.learned_skills.forEach((text) => {
                const item = document.createElement("li");
                item.textContent = text;
                learned.append(item);
            });
            content.append(learnedTitle, learned);
        }

        const evidence = document.createElement("div");
        evidence.className = "sb-file-note";
        evidence.innerHTML = '<svg viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M4 1.75h5l3 3v9.5H4a1 1 0 0 1-1-1v-10a1.5 1.5 0 0 1 1-1.5Z" stroke="currentColor" stroke-width="1.3"/><path d="M9 2v3h3M5.5 8h5M5.5 10.5h5" stroke="currentColor" stroke-width="1.3" stroke-linecap="round"/></svg>';
        const evidenceName = document.createElement("span");
        evidenceName.textContent = skill.evidence_filename || "No file attached";
        evidence.append(evidenceName);
        content.append(evidence);

        const actionWrap = document.createElement("div");
        actionWrap.className = "sb-actions";
        if (skill.file_url) {
            const view = document.createElement("a");
            view.className = "btn btn-outline-secondary";
            view.href = skill.file_url;
            view.target = "_blank";
            view.rel = "noopener";
            view.textContent = "View";
            actionWrap.append(view);
        }
        const edit = document.createElement("button");
        edit.type = "button";
        edit.className = "btn btn-primary";
        edit.dataset.wizardEdit = String(skill.id);
        edit.textContent = "Edit";
        const remove = document.createElement("button");
        remove.type = "button";
        remove.className = "btn btn-outline-danger";
        remove.dataset.certificateDeleteUrl = skill.delete_url;
        remove.dataset.certificateName = skill.name;
        remove.textContent = "Delete";
        actionWrap.append(edit, remove);
        content.append(actionWrap);
        detail.append(content);
        const revealContent = () => {
            content.classList.remove('is-changing');
            content.querySelectorAll('.sb-level-fill').forEach((fill, index) => {
                const level = levels[index];
                fill.style.setProperty('--sb-bar', Number(skill.proficiency_points || 0) >= level.points ? '1' : '0');
            });
        };
        if (reduceMotion) {
            revealContent();
        } else {
            requestAnimationFrame(revealContent);
        }
    };

    const skillVisible = (skill) => {
        return !category
            || (category === '__other' ? !(skill.group_keys || []).length : (skill.group_keys || []).includes(category));
    };

    // While the app-wide entrance runs (html.sb-intro, sb-motion.css rowIn), rows use it
    // instead of this page's own row fade, so they never animate twice.
    const introPlaying = () => document.documentElement.classList.contains('sb-intro');

    const buildRow = (skill) => {
        const row = document.createElement('tr');
        row.className = 'sb-select-row';
        row.dataset.skillId = String(skill.id);
        const nameCell = document.createElement('td');
        nameCell.dataset.label = 'Certificate';
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'sb-row-button';
        button.textContent = skill.name;
        nameCell.append(button);
        if (skill.issuing_organization) {
            const issuer = document.createElement('span');
            issuer.className = 'sb-row-sub';
            issuer.textContent = skill.issuing_organization;
            nameCell.append(issuer);
        }
        const cell = (label, text) => {
            const td = document.createElement('td');
            td.dataset.label = label;
            td.textContent = text || '—';
            return td;
        };
        row.append(nameCell, cell('Type', skill.type_label), cell('Category', skill.skill_type_label), cell('Level', skill.proficiency_label));
        return row;
    };

    const markSelected = () => {
        tableBody?.querySelectorAll('tr[data-skill-id]').forEach((row) => {
            const selected = row.dataset.skillId === String(selectedId);
            row.classList.toggle('is-selected', selected);
            row.querySelector('.sb-row-button')?.setAttribute('aria-pressed', String(selected));
        });
    };

    const renderList = (animate = true, initial = false, selectFirst = false) => {
        if (!list || !tableBody) return;
        window.clearTimeout(listRenderTimer);
        const version = ++listRenderVersion;
        const commit = () => {
            if (version !== listRenderVersion) return;
            const visible = skills.filter(skillVisible);
            const fadeRows = !reduceMotion && !introPlaying();
            const rows = visible.map((skill, index) => {
                const row = buildRow(skill);
                if (fadeRows) {
                    row.style.opacity = '0';
                    row.style.transition = 'opacity var(--sb-dur-crossfade) var(--sb-ease-out)';
                    row.style.transitionDelay = `${(initial ? initialRevealMs : 0) + Math.min(index, 7) * rowStaggerMs}ms`;
                }
                return row;
            });
            tableBody.replaceChildren(...rows);
            const isEmpty = visible.length === 0;
            list.classList.toggle('d-none', isEmpty);
            if (noResults) noResults.classList.toggle('d-none', !isEmpty || skills.length === 0);
            list.classList.remove('is-transitioning');
            list.classList.toggle('is-ready', reduceMotion || !animate);
            // A new filter selects its first row; otherwise keep the selection while it is still shown.
            if (selectFirst || !visible.some((skill) => String(skill.id) === String(selectedId))) {
                selectedId = visible[0]?.id ?? '';
            }
            markSelected();
            renderDetail(skills.find((skill) => String(skill.id) === String(selectedId)));
            const showRows = () => {
                if (version !== listRenderVersion) return;
                list.classList.add('is-ready');
                rows.forEach((row) => { row.style.opacity = '1'; });
            };
            if (fadeRows) {
                requestAnimationFrame(showRows);
            } else {
                showRows();
            }
        };

        if (animate && !reduceMotion && list.querySelector('[data-skill-id]')) {
            list.classList.add('is-transitioning');
            listRenderTimer = window.setTimeout(commit, duration('--sb-dur-list-out'));
            return;
        }
        list.classList.remove('is-transitioning');
        commit();
    };

    const updateStrength = (initial = false) => {
        const counted = skills.filter((skill) => {
            return !category
                || (category === '__other' ? !(skill.group_keys || []).length : (skill.group_keys || []).includes(category));
        });
        const average = counted.length
            ? counted.reduce((sum, skill) => sum + Number(skill.proficiency_points || 0), 0) / counted.length
            : null;
        const levelLabel = average === null ? 'No counted skills'
            : average < 37.5 ? 'Beginner'
                : average < 62.5 ? 'Intermediate'
                    : average < 87.5 ? 'Advanced' : 'Expert';
        const level = document.getElementById('profileStrengthLevel');
        const averageText = document.getElementById('profileStrengthAverage');
        if (level) {
            window.clearTimeout(strengthLabelTimer);
            if (!reduceMotion) level.style.opacity = '0';
            strengthLabelTimer = window.setTimeout(() => {
                level.textContent = levelLabel;
                level.style.opacity = '1';
            }, reduceMotion ? 0 : (initial ? initialRevealMs : duration('--sb-dur-selection')));
        }
        if (averageText) {
            if (average === null) {
                cancelTween(averageText);
                currentAveragePoints = 0;
                averageText.textContent = 'No counted skills';
            } else {
                tween(averageText, currentAveragePoints, average, reduceMotion ? 0 : (initial ? duration('--sb-dur-hero') : duration('--sb-dur-slow')), initial ? easeOut : easeInOut, (value) => {
                    currentAveragePoints = value;
                    averageText.textContent = `${(value / 25).toFixed(1)} avg`;
                });
            }
        }
    };

    const selectSkill = (id) => {
        if (String(id) === String(selectedId)) return;
        selectedId = String(id);
        markSelected();
        renderDetail(skills.find((skill) => String(skill.id) === String(id)));
    };

    // Rows: click anywhere on the row; keyboard focuses the name button, and
    // Arrow Up/Down, Home and End move between rows.
    tableBody?.addEventListener('click', (event) => {
        const row = event.target.closest('tr[data-skill-id]');
        if (row) selectSkill(row.dataset.skillId);
    });
    tableBody?.addEventListener('keydown', (event) => {
        if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key) || !event.target.closest('.sb-row-button')) return;
        const buttons = [...tableBody.querySelectorAll('.sb-row-button')];
        const index = buttons.indexOf(event.target);
        const next = event.key === 'Home' ? 0
            : event.key === 'End' ? buttons.length - 1
                : index + (event.key === 'ArrowDown' ? 1 : -1);
        if (!buttons[next]) return;
        event.preventDefault();
        buttons[next].focus();
        selectSkill(buttons[next].closest('tr').dataset.skillId);
    });

    const setCategory = (key) => {
        finishHydration();
        if (key === category) return;
        category = key;
        document.querySelectorAll('#competenciesOverview [data-category]').forEach((chip) => {
            chip.setAttribute('aria-pressed', String(chip.dataset.category === category));
        });
        document.querySelectorAll('#competenciesOverview [data-axis]').forEach((axis) => {
            axis.setAttribute('aria-pressed', String(axis.dataset.axis === category));
        });
        updateVertices(currentScores);
        if (category) pulseVertex(category);
        updateStrength();
        renderList(true, false, true);
    };

    document.querySelectorAll('#competenciesOverview [data-category]').forEach((button) => {
        button.addEventListener('click', () => setCategory(button.dataset.category));
    });

    // Chart points toggle their filter: a second click returns to All.
    document.querySelectorAll('#competenciesOverview [data-axis]').forEach((button) => {
        button.addEventListener('click', () => setCategory(category === button.dataset.axis ? '' : button.dataset.axis));
    });

    // Radar chart sits in a collapsible panel under the summary strip; the choice is remembered on this browser.
    const chartToggle = document.getElementById('competencyChartToggle');
    const chartPanel = document.getElementById('competencyChartPanel');
    const showChart = (open) => {
        chartPanel.hidden = !open;
        chartToggle.setAttribute('aria-expanded', String(open));
        chartToggle.querySelector('[data-chart-toggle-label]').textContent = open ? 'Hide chart' : 'Show chart';
    };
    if (chartToggle && chartPanel) {
        let chartOpen = false;
        try { chartOpen = localStorage.getItem('sb-certificates-chart') === 'open'; } catch (error) {}
        showChart(chartOpen);
        chartToggle.addEventListener('click', () => {
            const open = chartPanel.hidden;
            showChart(open);
            try { localStorage.setItem('sb-certificates-chart', open ? 'open' : 'closed'); } catch (error) {}
        });
    }

    const initialTargets = Object.keys(angleByGroup).reduce((result, key) => {
        result[key] = groupScore(key);
        return result;
    }, {});
    const initialCurrent = { technical: 0, soft: 0, certification: 0 };
    currentScores = { ...initialCurrent };
    buildRadarGrid();
    updatePolygon(primaryShape, initialCurrent);
    Object.keys(angleByGroup).forEach((key) => {
        const vertex = document.createElementNS(svgNs, 'circle');
        vertex.setAttribute('class', 'sb-radar-vertex');
        vertex.dataset.vertex = key;
        const halo = document.createElementNS(svgNs, 'circle');
        halo.setAttribute('class', 'sb-radar-halo');
        halo.dataset.halo = key;
        const hitArea = document.createElementNS(svgNs, 'circle');
        hitArea.setAttribute('class', 'sb-radar-hit');
        hitArea.setAttribute('aria-hidden', 'true');
        hitArea.dataset.vertexHit = key;
        hitArea.addEventListener('pointerenter', () => showVertexTooltip(key));
        hitArea.addEventListener('pointerleave', hideVertexTooltip);
        hitArea.addEventListener('pointercancel', hideVertexTooltip);
        vertices.append(halo, vertex, hitArea);
    });
    const updateVertices = (scores) => Object.keys(angleByGroup).forEach((key) => {
        const coordinates = point(angleByGroup[key], scores[key] || 0);
        const vertex = vertices.querySelector(`[data-vertex="${key}"]`);
        const halo = vertices.querySelector(`[data-halo="${key}"]`);
        const hitArea = vertices.querySelector(`[data-vertex-hit="${key}"]`);
        [vertex, halo, hitArea].forEach((item) => {
            item?.setAttribute('cx', coordinates.x);
            item?.setAttribute('cy', coordinates.y);
        });
        hitArea?.classList.toggle('is-inactive', Number(scores[key] || 0) <= 0);
        halo?.classList.toggle('is-selected', category === key);
    });
    const pulseVertex = (key) => {
        const vertex = vertices.querySelector(`[data-vertex="${key}"]`);
        if (!vertex || reduceMotion) return;
        vertex.getAnimations().forEach((animation) => animation.cancel());
        vertex.animate(
            [{ transform: 'scale(1)' }, { transform: 'scale(1.35)' }, { transform: 'scale(1)' }],
            {
                duration: duration('--sb-dur-pulse'),
                easing: getComputedStyle(root).getPropertyValue('--sb-ease-out').trim()
            }
        );
    };
    const refreshShapeAndVertices = () => {
        updatePolygon(primaryShape, currentScores);
        updateVertices(currentScores);
    };

    Object.keys(angleByGroup).forEach((key) => {
        currentScores[key] = 0;
        const axisButton = document.querySelector(`#competenciesOverview [data-axis="${key}"]`);
        const currentValue = () => Number(currentScores[key] || 0);
        tween(axisButton || radar, currentValue(), initialTargets[key], duration('--sb-dur-hero'), easeOut, (value) => {
            currentScores[key] = value;
            refreshShapeAndVertices();
            axisButton?.querySelector('.sb-radar-axis-value') && (axisButton.querySelector('.sb-radar-axis-value').textContent = fmt(value));
            const stat = document.querySelector(`#competenciesOverview [data-stat-value="${key}"]`);
            if (stat) stat.textContent = fmt(value);
        });
    });
    radar.classList.add('is-ready');
    updateStrength(true);
    renderList(false, true);
    announceTimer = window.setTimeout(() => announceChart(initialTargets), reduceMotion ? 0 : duration('--sb-dur-hero'));
    if (initialRevealMs > 0) {
        hydrationTimer = window.setTimeout(finishHydration, initialRevealMs + duration('--sb-dur-crossfade'));
    } else {
        finishHydration();
    }

    // The wizard (public/js/certificate-wizard.js) reads current data here and announces saves.
    const emptyState = document.getElementById('competencyEmptyState');
    window.sbCertificates = { find: (id) => skills.find((skill) => String(skill.id) === String(id)) };
    document.addEventListener('certificates:changed', (event) => {
        const overview = event.detail?.overview;
        if (!overview) return;
        finishHydration();
        skills.splice(0, skills.length, ...overview.skills);
        Object.keys(groups).forEach((key) => delete groups[key]);
        Object.assign(groups, overview.groups);
        if (event.detail.selectId) selectedId = String(event.detail.selectId);
        emptyState?.classList.toggle('d-none', skills.length > 0);
        const targets = {};
        Object.keys(angleByGroup).forEach((key) => {
            targets[key] = groupScore(key);
            const axisButton = document.querySelector(`#competenciesOverview [data-axis="${key}"]`);
            const tooltip = document.querySelector(`#competenciesOverview [data-axis-tooltip="${key}"]`);
            if (tooltip) tooltip.textContent = vertexTooltipText(key);
            tween(axisButton || radar, Number(currentScores[key] || 0), targets[key], duration('--sb-dur-slow'), easeInOut, (value) => {
                currentScores[key] = value;
                refreshShapeAndVertices();
                axisButton?.querySelector('.sb-radar-axis-value') && (axisButton.querySelector('.sb-radar-axis-value').textContent = fmt(value));
                const stat = document.querySelector(`#competenciesOverview [data-stat-value="${key}"]`);
                if (stat) stat.textContent = fmt(value);
            });
        });
        updateStrength();
        renderList(false);
        announceChart(targets);
    });
})();
</script>
@endpush
