@extends('layouts.public')

@php
    $roles = [
        'students' => ['title' => 'Students', 'cta' => 'Student sign in', 'items' => ['Manage your profile, competencies, and portfolio', 'Submit and follow internship applications', 'Track your readiness checklist']],
        'coordinators' => ['title' => 'Coordinators', 'cta' => 'Coordinator sign in', 'items' => ['Review student profiles and evidence', 'Follow your assigned students', 'Generate placement reports']],
    ];
    $r = $roles[$role];
@endphp

@section('title', $r['title'])

@section('content')
<section class="public-section sb-pg">
    <div class="sb-pg-head">
        <div>
            <p class="eyebrow">Who it's for</p>
            <h1 class="public-page-title">{{ $r['title'] }}</h1>
        </div>
        <p class="sb-pg-sub">Each account sees tools aligned to its role and responsibilities.</p>
    </div>
    @include('public.pages._tabs', ['active' => $role])
    <ul class="sb-pg-list">
        @foreach ($r['items'] as $item)
            <li><svg class="public-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="m5 12 4.5 4.5L19 7"></path></svg><span>{{ $item }}</span></li>
        @endforeach
    </ul>
    <div class="sb-pg-cta">
        <a class="public-button public-button-primary" href="{{ route('login') }}">{{ $r['cta'] }}</a>
        <a class="public-button" href="{{ route('internships.index') }}">Browse internships</a>
    </div>
</section>
@endsection
