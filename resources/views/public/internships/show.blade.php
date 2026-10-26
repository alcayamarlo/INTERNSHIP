@extends('layouts.public')

@section('title', $internship->title)

@section('content')
@php
    $viewer = auth()->user();
    $canApply = ! $viewer || $viewer->isRole(\App\Enums\UserRole::Student);
@endphp
<section class="public-section">
    <div class="public-detail">
        <x-breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Internships', 'url' => route('internships.index')],
            ['label' => $internship->title],
        ]" />

        @if(session('info'))
            <p class="public-notice" role="status">{{ session('info') }}</p>
        @endif

        <h1 class="public-page-title">{{ $internship->title }}</h1>
        <p class="public-job-company"><i class="bi bi-building" aria-hidden="true"></i> {{ $internship->employer?->company_name }}</p>

        <ul class="public-job-meta">
            @if($internship->location)<li><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $internship->location }}</li>@endif
            @if($internship->work_setup)<li><i class="bi bi-laptop" aria-hidden="true"></i> {{ $internship->work_setup->label() }}</li>@endif
            @if($internship->duration)<li><i class="bi bi-clock" aria-hidden="true"></i> {{ $internship->duration }}</li>@endif
            @if($internship->expires_at)<li><i class="bi bi-calendar-event" aria-hidden="true"></i> Apply by {{ $internship->expires_at->format('M d, Y') }}</li>@endif
        </ul>

        <div class="public-job-actions">
            @if($canApply)
                <a class="public-button public-button-primary" href="{{ route('internships.apply', $internship) }}">Apply</a>
                @guest<span class="public-apply-note">You will be asked to log in first.</span>@endguest
            @else
                <span class="public-apply-note">Only students can apply</span>
            @endif
        </div>

        <div class="public-detail-body">
            <h2>Description</h2>
            <p>{!! nl2br(e($internship->description)) !!}</p>

            @if($internship->responsibilities)
                <h2>Responsibilities</h2>
                <p>{!! nl2br(e($internship->responsibilities)) !!}</p>
            @endif

            @if($internship->requirements)
                <h2>General requirements</h2>
                <p>{!! nl2br(e($internship->requirements)) !!}</p>
            @endif

            @if($internship->requirementsList->count())
                <h2>Competency requirements</h2>
                <ul>
                    @foreach($internship->requirementsList as $req)
                        <li>{{ $req->requirement_name }}</li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</section>
@endsection
