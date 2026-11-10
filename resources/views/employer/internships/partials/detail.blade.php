{{-- One posting in the detail panel (index.blade.php): actions, facts, competencies,
     programs and the latest applicants. Used for the first render and each row's <template>. --}}
@php
    $meta = array_filter([
        $internship->location ?: 'Location not set',
        $internship->work_setup?->label(),
        $internship->duration,
        $internship->allowance ? '₱'.number_format((float) $internship->allowance, 2).' allowance' : null,
    ]);
@endphp
<div class="pp-detail-inner" data-posting-detail="{{ $internship->id }}">
    <div class="pp-head">
        <h2 class="pp-title">{{ $internship->title }}</h2>
        <span class="sb-row-actions pp-actions">
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
    <p class="pp-meta">{{ implode(' · ', $meta) }}</p>
    @if($internship->hidden_at)
        <p class="pp-hidden"><span class="badge text-bg-danger">Hidden by an administrator</span> {{ $internship->hidden_reason }}</p>
    @endif

    <div class="pp-stats">
        <div class="pp-stat">
            <span class="pp-stat-label">Applicants</span>
            <span class="pp-stat-value">{{ $internship->applications_count }}</span>
        </div>
        <div class="pp-stat">
            <span class="pp-stat-label">Deadline</span>
            <span class="pp-stat-value">{{ $internship->expires_at ? $internship->expires_at->timezone('Asia/Manila')->format('M d, Y') : 'No deadline' }}</span>
            @if($internship->expires_at?->isPast())<span class="pp-stat-note">Passed</span>@endif
        </div>
    </div>

    <section class="pp-section">
        <h3 class="pp-section-title">Competencies</h3>
        @if($internship->requirementsList->isNotEmpty())
            <ul class="pp-tags">
                @foreach($internship->requirementsList as $requirement)
                    <li>{{ $requirement->requirement_name }} · {{ $requirement->required_level?->label() ?? ucfirst((string) $requirement->getRawOriginal('required_level')) }}</li>
                @endforeach
            </ul>
        @else
            <p class="pp-muted">No competencies listed.</p>
        @endif
    </section>

    <section class="pp-section">
        <h3 class="pp-section-title">Open to programs</h3>
        <ul class="pp-tags">
            @forelse($internship->programs as $program)
                <li>{{ $program->display_name }}</li>
            @empty
                <li>All programs</li>
            @endforelse
        </ul>
    </section>

    <section class="pp-section">
        <div class="pp-section-head">
            <h3 class="pp-section-title">Applicants</h3>
            @if($internship->applications_count)
                <a href="{{ route('employer.applicants.index') }}" class="pp-link">View all applicants</a>
            @endif
        </div>
        @if($internship->applications->isNotEmpty())
            <ul class="pp-applicants">
                @foreach($internship->applications as $application)
                    <li>
                        <a href="{{ route('employer.applicants.show', $application) }}">{{ $application->student?->user?->name ?? 'Student' }}</a>
                        <span class="pp-muted">{{ ($application->applied_at ?? $application->created_at)?->timezone('Asia/Manila')->format('M d, Y') }}</span>
                        <x-status-badge :status="$application->status->value" :label="$application->status->label()" />
                    </li>
                @endforeach
            </ul>
        @else
            <p class="pp-muted">No applicants yet. Students who match will see this posting first.</p>
        @endif
    </section>
</div>
