@extends('layouts.public')

@section('title', 'Browse internships')

@section('content')
<section class="public-section">
    <div class="public-section-heading">
        <div class="eyebrow">Open internships</div>
        <h1 class="public-page-title">Browse internships</h1>
        <p>Anyone can browse. Log in as a student to apply.</p>
    </div>

    <form method="GET" class="public-filter" role="search" aria-label="Filter internships">
        <input type="search" name="search" value="{{ request('search') }}" placeholder="Search title or company" aria-label="Search title or company">
        <input type="text" name="location" value="{{ request('location') }}" placeholder="Location" aria-label="Location">
        <select name="setup" aria-label="Work setup">
            <option value="">Any setup</option>
            @foreach($setups as $setup)
                <option value="{{ $setup->value }}" @selected(request('setup') === $setup->value)>{{ $setup->label() }}</option>
            @endforeach
        </select>
        <button type="submit" class="public-button public-button-primary">Filter</button>
        <a href="{{ route('internships.index') }}" class="public-button">Clear</a>
    </form>

    @if($internships->count())
        <div class="public-job-grid">
            @foreach($internships as $internship)
                @include('partials.public-internship-card', ['internship' => $internship])
            @endforeach
        </div>

        @if($internships->hasPages())
            <nav class="public-pager" aria-label="Pagination">
                @if($internships->onFirstPage())
                    <span class="public-button is-disabled" aria-disabled="true">Previous</span>
                @else
                    <a class="public-button" href="{{ $internships->previousPageUrl() }}" rel="prev">Previous</a>
                @endif
                <span class="public-pager-info">Page {{ $internships->currentPage() }} of {{ $internships->lastPage() }}</span>
                @if($internships->hasMorePages())
                    <a class="public-button" href="{{ $internships->nextPageUrl() }}" rel="next">Next</a>
                @else
                    <span class="public-button is-disabled" aria-disabled="true">Next</span>
                @endif
            </nav>
        @endif
    @else
        <p class="public-empty">No open internships right now. Check back soon.</p>
    @endif
</section>
@endsection
