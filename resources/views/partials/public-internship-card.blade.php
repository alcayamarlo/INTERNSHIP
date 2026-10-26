{{-- Public-safe internship card. Expects $internship (with employer). --}}
@php
    $viewer = auth()->user();
    $canApply = ! $viewer || $viewer->isRole(\App\Enums\UserRole::Student);
@endphp
<article class="public-job-card reveal">
    <h3><a href="{{ route('internships.show', $internship) }}">{{ $internship->title }}</a></h3>
    <p class="public-job-company"><i class="bi bi-building" aria-hidden="true"></i> {{ $internship->employer?->company_name }}</p>
    <ul class="public-job-meta">
        @if($internship->location)<li><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $internship->location }}</li>@endif
        @if($internship->work_setup)<li><i class="bi bi-laptop" aria-hidden="true"></i> {{ $internship->work_setup->label() }}</li>@endif
        @if($internship->duration)<li><i class="bi bi-clock" aria-hidden="true"></i> {{ $internship->duration }}</li>@endif
        @if($internship->expires_at)<li><i class="bi bi-calendar-event" aria-hidden="true"></i> Apply by {{ $internship->expires_at->format('M d, Y') }}</li>@endif
    </ul>
    <div class="public-job-actions">
        <a class="public-button" href="{{ route('internships.show', $internship) }}">View details</a>
        @if($canApply)
            <a class="public-button public-button-primary" href="{{ route('internships.apply', $internship) }}">Apply</a>
        @else
            <span class="public-apply-note">Only students can apply</span>
        @endif
    </div>
</article>
