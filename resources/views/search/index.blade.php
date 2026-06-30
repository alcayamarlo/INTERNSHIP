@extends('layouts.app')

@section('title', 'Search')

@section('content')
<div class="mb-4">
    <h1 class="h3 mb-1">Search</h1>
    <p class="text-muted mb-0">Find students, companies, skills, and internships</p>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('search') }}">
            <div class="input-group input-group-lg">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" class="form-control" name="q" value="{{ $query }}" placeholder="Search (min. 2 characters)..." autofocus>
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>
    </div>
</div>

@if(strlen($query) >= 2)
    <div class="row g-4">
        @if($results['internships']->count())
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white"><h5 class="mb-0"><i class="bi bi-briefcase"></i> Internships</h5></div>
                <ul class="list-group list-group-flush">
                    @foreach($results['internships'] as $internship)
                        <li class="list-group-item">
                            <strong>{{ $internship->title }}</strong>
                            <br><small class="text-muted">{{ $internship->employer->company_name }}</small>
                            @auth
                                @if(auth()->user()->isRole(\App\Enums\UserRole::Student))
                                    <br><a href="{{ route('student.internships.show', $internship) }}" class="small">View details</a>
                                @endif
                            @endauth
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        @if($results['students']->count())
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white"><h5 class="mb-0"><i class="bi bi-mortarboard"></i> Students</h5></div>
                <ul class="list-group list-group-flush">
                    @foreach($results['students'] as $student)
                        <li class="list-group-item">
                            <strong>{{ $student->user->name }}</strong>
                            <br><small class="text-muted">{{ $student->user->email }}</small>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        @if($results['companies']->count())
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white"><h5 class="mb-0"><i class="bi bi-building"></i> Companies</h5></div>
                <ul class="list-group list-group-flush">
                    @foreach($results['companies'] as $company)
                        <li class="list-group-item">
                            <strong>{{ $company->company_name }}</strong>
                            @if($company->industry)<br><small class="text-muted">{{ $company->industry }}</small>@endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        @if($results['skills']->count())
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white"><h5 class="mb-0"><i class="bi bi-tags"></i> Skills</h5></div>
                <ul class="list-group list-group-flush">
                    @foreach($results['skills'] as $skill)
                        <li class="list-group-item">{{ $skill->name }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        @if($results['competencies']->count())
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header bg-white"><h5 class="mb-0"><i class="bi bi-award"></i> Competencies</h5></div>
                <ul class="list-group list-group-flush">
                    @foreach($results['competencies'] as $competency)
                        <li class="list-group-item">{{ $competency->name }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        @endif

        @if(collect($results)->every(fn($c) => $c->isEmpty()))
            <div class="col-12">
                <div class="alert alert-light text-center">No results found for "<strong>{{ $query }}</strong>".</div>
            </div>
        @endif
    </div>
@elseif(strlen($query) > 0)
    <div class="alert alert-info">Please enter at least 2 characters to search.</div>
@endif
@endsection
