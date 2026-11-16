{{-- One posting in the detail panel (index.blade.php): actions, facts, competencies,
     programs and the latest applicants. Used for the first render and each row's <template>. --}}
@php
    $meta = array_filter([
        $internship->location ?: 'Location not set',
        $internship->slots ? $internship->slots.' '.\Illuminate\Support\Str::plural('slot', $internship->slots) : null,
        $internship->duration,
        $internship->allowance ? '₱'.number_format((float) $internship->allowance, 2).' allowance' : null,
    ]);
@endphp
<div class="sb-detail" data-posting-detail="{{ $internship->id }}">
    <div class="sb-detail-head">
        <h2 class="sb-detail-title">{{ $internship->title }}</h2>
        <span class="sb-row-actions sb-detail-actions">
            <a href="{{ route('employer.internships.edit', $internship) }}" class="btn btn-sm btn-outline-secondary" title="Edit posting" aria-label="Edit {{ $internship->title }}">
                <i class="bi bi-pencil" aria-hidden="true"></i>
            </a>
            @if($internship->status === 'open')
                <form action="{{ route('employer.internships.close', $internship) }}" method="POST" class="d-inline"
                      data-confirm="Close &quot;{{ $internship->title }}&quot;? Students can no longer apply to it." data-confirm-title="Close posting" data-confirm-label="Close posting">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Close posting" aria-label="Close {{ $internship->title }}">
                        <i class="bi bi-lock" aria-hidden="true"></i>
                    </button>
                </form>
            @endif
            <form action="{{ route('employer.internships.destroy', $internship) }}" method="POST" class="d-inline" data-confirm="Delete this internship? This cannot be undone." data-confirm-title="Delete internship">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete posting" aria-label="Delete {{ $internship->title }}">
                    <i class="bi bi-trash" aria-hidden="true"></i>
                </button>
            </form>
        </span>
    </div>
    <p class="sb-detail-meta">{{ implode(' · ', $meta) }}</p>
    @if($internship->hidden_at)
        <p class="sb-detail-alert"><span class="badge text-bg-danger">Hidden by an administrator</span> {{ $internship->hidden_reason }}</p>
    @endif

    <div class="sb-tiles">
        <div class="sb-tile">
            <span class="sb-tile-label">Applicants</span>
            <span class="sb-tile-value">{{ $internship->applications_count }}</span>
        </div>
        <div class="sb-tile">
            <span class="sb-tile-label">Deadline</span>
            <span class="sb-tile-value">{{ $internship->expires_at ? $internship->expires_at->timezone('Asia/Manila')->format('M d, Y') : 'No deadline' }}</span>
            @if($internship->expires_at?->isPast())<span class="sb-tile-note">Passed</span>@endif
        </div>
    </div>

    <section class="sb-block">
        <h3 class="sb-block-title">Competencies</h3>
        @if($internship->requirementsList->isNotEmpty())
            <ul class="sb-tags">
                @foreach($internship->requirementsList as $requirement)
                    <li>{{ $requirement->requirement_name }} · {{ $requirement->required_level?->label() ?? ucfirst((string) $requirement->getRawOriginal('required_level')) }}</li>
                @endforeach
            </ul>
        @else
            <p class="sb-note">No competencies listed.</p>
        @endif
    </section>

    <section class="sb-block">
        <h3 class="sb-block-title">Open to programs</h3>
        <ul class="sb-tags">
            @forelse($internship->programs as $program)
                <li>{{ $program->display_name }}</li>
            @empty
                <li>All programs</li>
            @endforelse
        </ul>
    </section>

    <section class="sb-block">
        <div class="sb-block-head">
            <h3 class="sb-block-title">Applicants</h3>
            @if($internship->applications_count)
                <a href="{{ route('employer.applicants.index') }}" class="sb-block-link">View all applicants</a>
            @endif
        </div>
        @if($internship->applications->isNotEmpty())
            <ul class="sb-rows">
                @foreach($internship->applications as $application)
                    <li>
                        <a href="{{ route('employer.applicants.show', $application) }}">{{ $application->student?->user?->name ?? 'Student' }}</a>
                        <span class="sb-note">{{ ($application->applied_at ?? $application->created_at)?->timezone('Asia/Manila')->format('M d, Y') }}</span>
                        <x-status-badge :status="$application->status->value" :label="$application->status->companyLabel()" />
                    </li>
                @endforeach
            </ul>
        @else
            <p class="sb-note">No applicants yet. Students who match will see this posting first.</p>
        @endif
    </section>
</div>
