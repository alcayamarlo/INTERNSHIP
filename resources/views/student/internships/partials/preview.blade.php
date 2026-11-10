{{--
    Preview of one internship for the Browse Internships split view.
    Rendered once per row inside a <template> (the page script copies it into the preview
    panel or the mobile preview dialog), and once directly for the first row.
    Needs: $internship (with employer, programs, requirementsList, match_percentage), $applied, $resumes.
--}}
@php
    $levelLabel = fn ($level) => $level instanceof \App\Enums\ProficiencyLevel ? $level->label() : ucfirst((string) $level);
    $responsibilities = collect(preg_split('/\r\n|\r|\n/', (string) $internship->responsibilities))
        ->map(fn ($line) => trim(preg_replace('/^\s*[-*•]\s*/u', '', $line)))
        ->filter()
        ->values();
    $facts = [
        ['bi-geo-alt', 'Location', $internship->location ?: 'Not stated'],
        ['bi-laptop', 'Work mode', $internship->work_setup->label()],
        ['bi-clock', 'Duration', $internship->duration ?: 'Not stated'],
        ['bi-cash-coin', 'Allowance', $internship->allowance ? '₱'.number_format($internship->allowance, 2).'/mo' : 'Not stated'],
    ];
@endphp
<div class="ib-pv" data-preview-content data-preview-id="{{ $internship->id }}">
    <div class="ib-pv-scroll">
        <div class="ib-pv-head">
            <div class="ib-pv-heading">
                <h2 class="ib-pv-title">{{ $internship->title }}</h2>
                <p class="ib-pv-company">
                    <i class="bi bi-building" aria-hidden="true"></i>
                    <span>{{ $internship->employer->company_name }}</span>
                    @if($internship->employer?->isVerified())
                        <span class="ib-verified" title="Verified by the school"><i class="bi bi-patch-check-fill" aria-hidden="true"></i> Verified</span>
                    @endif
                </p>
            </div>
            <div class="ib-pv-score" aria-label="{{ $internship->match_percentage }}% match">
                <span class="ib-pv-score-value">{{ $internship->match_percentage }}%</span>
                <span class="ib-pv-score-label">match</span>
            </div>
        </div>

        <dl class="ib-pv-facts">
            @foreach($facts as [$icon, $label, $value])
                <div>
                    <dt><i class="bi {{ $icon }}" aria-hidden="true"></i> {{ $label }}</dt>
                    <dd>{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        <section class="ib-pv-section">
            <h3>Description</h3>
            <p>{!! nl2br(e($internship->description)) !!}</p>
        </section>

        @if($responsibilities->isNotEmpty())
            <section class="ib-pv-section">
                <h3>Responsibilities</h3>
                <ul class="ib-pv-bullets">
                    @foreach($responsibilities as $line)
                        <li>{{ $line }}</li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if($internship->requirementsList->count())
            <section class="ib-pv-section">
                <h3>Competency requirements</h3>
                <ul class="ib-chips">
                    @foreach($internship->requirementsList as $req)
                        <li class="ib-chip">{{ $req->requirement_name }} <span class="ib-chip-level">· {{ $levelLabel($req->required_level) }}</span></li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>

    <div class="ib-pv-actions" data-apply-slot>
        <a href="{{ route('student.internships.show', $internship) }}" class="btn btn-outline-primary">Full details</a>
        @if($applied)
            <a href="{{ route('student.applications.index') }}" class="btn btn-outline-secondary ib-applied" aria-label="Already applied; view My Applications"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Applied · View application</a>
        @elseif($hasOjt ?? false)
            <span class="btn btn-outline-secondary disabled" aria-disabled="true" title="{{ \App\Services\ApplicationService::HAS_OJT_MESSAGE }}" data-has-ojt><i class="bi bi-lock-fill" aria-hidden="true"></i> You already have an OJT</span>
        @elseif($resumes->isNotEmpty())
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#biApplyModal"
                    data-apply-id="{{ $internship->id }}"
                    data-apply-url="{{ route('student.internships.apply', $internship) }}"
                    data-apply-title="{{ $internship->title }}"
                    data-apply-company="{{ $internship->employer->company_name }}"><i class="bi bi-send" aria-hidden="true"></i> Apply</button>
        @else
            <a href="{{ route('student.resume.index') }}" class="btn btn-outline-secondary">Create a resume to apply</a>
        @endif
    </div>
</div>
