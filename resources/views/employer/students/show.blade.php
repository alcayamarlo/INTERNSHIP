@extends('layouts.app')

@section('title', $profile['name'])

@section('content')
@php($student = $profile)
<x-page-header :title="$profile['name']" :subtitle="collect([$profile['program'], $profile['year_level']])->filter()->implode(' · ')">
    <a href="{{ route('employer.students.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to students</a>
    @include('employer.students.partials.shortlist-button')
    <div>@include('employer.students.partials.invite-button')</div>
</x-page-header>

<div class="sb-stack">
    <div class="sb-callout"><p><i class="bi bi-shield-lock" aria-hidden="true"></i> Contact details are hidden. They appear on the applicant page after the student accepts your invitation or applies.</p></div>

    <div class="sb-layout-2">
        <div class="sb-stack">
            @if($profile['about_me'])
                <section class="card"><div class="card-header"><h2>About me</h2></div><div class="card-body"><p class="mb-0">{{ $profile['about_me'] }}</p></div></section>
            @endif

            @if($profile['certificates'])
                <section class="card">
                    <div class="card-header"><h2>Certificates</h2></div>
                    <ul class="sb-rows sb-rows--card">
                        @foreach($profile['certificates'] as $certificate)
                            <li>
                                <div class="sb-grow">
                                    <span class="sb-row-title">{{ $certificate['name'] }}</span>
                                    <span class="sb-row-sub">{{ collect([$certificate['type'], $certificate['skill_type'], $certificate['level']])->filter()->implode(' · ') }}</span>
                                    @if($certificate['skills'])<small class="d-block mt-1">Skills learned: {{ implode(' • ', $certificate['skills']) }}</small>@endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if($profile['experiences'])
                <section class="card">
                    <div class="card-header"><h2>Experience</h2></div>
                    <ul class="sb-rows sb-rows--card">
                        @foreach($profile['experiences'] as $experience)
                            <li><div class="sb-grow"><span class="sb-row-title">{{ $experience['position'] }}</span><span class="sb-row-sub">{{ $experience['organization'] }} · {{ $experience['period'] }}</span></div></li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if(! $profile['about_me'] && ! $profile['certificates'] && ! $profile['experiences'])
                <section class="card"><div class="card-body"><p class="sb-note">This student has not added an About me, certificates or experience yet.</p></div></section>
            @endif
        </div>

        <aside class="sb-stack">
            @if($profile['photo_url'])
                <img src="{{ $profile['photo_url'] }}" alt="" class="sb-photo">
            @endif
            @if($profile['academic'])
                <section class="card"><div class="card-header"><h2>Academic</h2></div><div class="card-body">
                    @include('employer.applicants.partials.definition-list', ['heading' => null, 'rows' => $profile['academic']])
                </div></section>
            @endif
            @if($profile['awards'])
                <section class="card"><div class="card-header"><h2>Honors / awards</h2></div><div class="card-body small">
                    @foreach($profile['awards'] as $group)
                        <h3 class="sb-block-title {{ $loop->first ? '' : 'mt-3' }}">{{ $group['label'] }}</h3>
                        <ul class="list-unstyled mb-0">
                            @foreach($group['awards'] as $award)
                                <li class="mb-1">{!! nl2br(e($award['title'])) !!}@if($award['meta'] !== '')<span class="sb-row-sub">{{ $award['meta'] }}</span>@endif</li>
                            @endforeach
                        </ul>
                    @endforeach
                </div></section>
            @endif
            @if($profile['skills'])
                <section class="card"><div class="card-header"><h2>Skills</h2></div><div class="card-body"><p class="mb-0 small">{{ implode(' • ', $profile['skills']) }}</p></div></section>
            @endif
        </aside>
    </div>
</div>

@include('employer.students.partials.invite-modal')
@endsection
