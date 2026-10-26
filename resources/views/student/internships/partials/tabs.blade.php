{{-- Section tabs for Internships. $active: 'browse' | 'applications' --}}
<nav class="sb-section-tabs" aria-label="Internships sections">
    <a href="{{ route('student.internships.index') }}" class="sb-section-tab {{ $active === 'browse' ? 'active' : '' }}" @if($active === 'browse') aria-current="page" @endif>
        <i class="bi bi-search" aria-hidden="true"></i> Browse
    </a>
    <a href="{{ route('student.applications.index') }}" class="sb-section-tab {{ $active === 'applications' ? 'active' : '' }}" @if($active === 'applications') aria-current="page" @endif>
        <i class="bi bi-send" aria-hidden="true"></i> My Applications
    </a>
</nav>
