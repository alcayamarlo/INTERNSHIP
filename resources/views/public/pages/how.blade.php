@extends('layouts.public')

@section('title', 'How it works')

@section('content')
<section class="public-section sb-pg">
    <div class="sb-pg-head">
        <div>
            <p class="eyebrow">A connected workflow</p>
            <h1 class="public-page-title">From preparation to placement.</h1>
        </div>
        <p class="sb-pg-sub">SkillBridge brings the key steps of internship preparation into one role-aware platform.</p>
    </div>
    @include('public.pages._tabs', ['active' => 'how'])
    <ol class="sb-pg-steps">
        <li><span class="sb-pg-no" aria-hidden="true">01</span><h2>Build your profile</h2><p>Record academic and contact details, describe your goals, and keep your resume and portfolio evidence together.</p></li>
        <li><span class="sb-pg-no" aria-hidden="true">02</span><h2>Develop competencies</h2><p>Review skill categories, document your experience, and submit evidence for coordinator review.</p></li>
        <li><span class="sb-pg-no" aria-hidden="true">03</span><h2>Explore opportunities</h2><p>Browse internships, submit applications, and follow application updates from your student workspace.</p></li>
    </ol>
    <div class="sb-pg-cta">
        <a class="public-button public-button-primary" href="{{ route('login') }}">Sign in to your workspace</a>
        <a class="public-button" href="{{ route('internships.index') }}">Browse internships</a>
    </div>
</section>
@endsection
