@extends('layouts.app')

@section('title', $profile['name'])

@section('content')
@php($student = $profile)
<x-page-header :title="$profile['name']" :subtitle="collect([$profile['program'], $profile['year_level']])->filter()->implode(' · ')">
    <a href="{{ route('employer.students.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to students</a>
    <div>@include('employer.students.partials.invite-button')</div>
</x-page-header>

<div class="alert alert-light border small"><i class="bi bi-shield-lock" aria-hidden="true"></i> Contact details are hidden. They appear on the applicant page after the student accepts your invitation or applies.</div>

<div class="row g-4">
    <div class="col-lg-8">
        @if($profile['career_objectives'])
            <div class="card mb-4"><div class="card-header"><h2>Career objectives</h2></div><div class="card-body"><p class="mb-0">{{ $profile['career_objectives'] }}</p></div></div>
        @endif

        @if($profile['certificates'])
            <div class="card mb-4">
                <div class="card-header"><h2>Certificates</h2></div>
                <ul class="list-group list-group-flush">
                    @foreach($profile['certificates'] as $certificate)
                        <li class="list-group-item">
                            <strong>{{ $certificate['name'] }}</strong>
                            <span class="d-block small text-muted">{{ collect([$certificate['type'], $certificate['skill_type'], $certificate['level']])->filter()->implode(' · ') }}</span>
                            @if($certificate['skills'])<span class="d-block small">Skills learned: {{ implode(' • ', $certificate['skills']) }}</span>@endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if($profile['experiences'])
            <div class="card mb-4">
                <div class="card-header"><h2>Experience</h2></div>
                <ul class="list-group list-group-flush">
                    @foreach($profile['experiences'] as $experience)
                        <li class="list-group-item"><strong>{{ $experience['position'] }}</strong><span class="d-block small text-muted">{{ $experience['organization'] }} · {{ $experience['period'] }}</span></li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <div class="col-lg-4">
        @if($profile['photo_url'])
            <img src="{{ $profile['photo_url'] }}" alt="" class="img-fluid rounded mb-4" style="max-width:160px">
        @endif
        @if($profile['academic'])
            <div class="card mb-4"><div class="card-header"><h2>Academic</h2></div><div class="card-body">
                @include('employer.applicants.partials.definition-list', ['heading' => null, 'rows' => $profile['academic']])
            </div></div>
        @endif
        @if($profile['awards'])
            <div class="card mb-4"><div class="card-header"><h2>Honors / awards</h2></div><div class="card-body small">
                @foreach($profile['awards'] as $group)
                    <h3 class="h6 mt-2">{{ $group['label'] }}</h3>
                    <ul class="list-unstyled mb-2">
                        @foreach($group['awards'] as $award)
                            <li class="mb-1">{!! nl2br(e($award['title'])) !!}@if($award['meta'] !== '')<span class="d-block text-muted">{{ $award['meta'] }}</span>@endif</li>
                        @endforeach
                    </ul>
                @endforeach
            </div></div>
        @endif
        @if($profile['skills'])
            <div class="card mb-4"><div class="card-header"><h2>Skills</h2></div><div class="card-body"><p class="mb-0 small">{{ implode(' • ', $profile['skills']) }}</p></div></div>
        @endif
    </div>
</div>

@include('employer.students.partials.invite-modal')
@endsection
