@extends('layouts.app')

@section('title', $competency->name)

@section('content')
<div class="mb-4">
    <a href="{{ route('student.competencies.index') }}" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-chevron-left"></i> Back to Competencies
    </a>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h1 class="h3 mb-2">{{ $competency->name }}</h1>
                        <div class="mb-3">
                            <span class="badge bg-light text-dark me-2">{{ $competency->category->label() }}</span>
                            <span class="badge" style="background-color: {{ getProficiencyColor($competency->proficiency_level->value) }}">
                                {{ $competency->proficiency_level->label() }}
                            </span>
                        </div>
                    </div>
                    <div class="btn-group" role="group">
                        <a href="{{ route('student.competencies.edit', $competency) }}" class="btn btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                    </div>
                </div>

                @if($competency->description)
                    <div class="mb-4">
                        <h5>Description</h5>
                        <p class="text-muted">{{ $competency->description }}</p>
                    </div>
                @endif

                <div class="row">
                    @if($competency->obtained_at)
                        <div class="col-md-6 mb-3">
                            <h6 class="text-muted small">Date Obtained</h6>
                            <p class="mb-0">{{ $competency->obtained_at->format('M d, Y') }}</p>
                        </div>
                    @endif

                    <div class="col-md-6 mb-3">
                        <h6 class="text-muted small">Added On</h6>
                        <p class="mb-0">{{ $competency->created_at->format('M d, Y') }}</p>
                    </div>
                </div>

                <!-- Proficiency Level Details -->
                <div class="mt-4 p-3 bg-light rounded">
                    <h6 class="mb-3"><i class="bi bi-speedometer2"></i> Proficiency Details</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Current Level:</strong> {{ $competency->proficiency_level->label() }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0"><strong>Score:</strong> {{ $competency->proficiency_level->score() }}/100</p>
                        </div>
                    </div>

                    <!-- Level Progression -->
                    <div class="mt-3">
                        <h6 class="small text-muted">Level Progression:</h6>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar" style="width: {{ $competency->proficiency_level->score() }}%; background-color: {{ getProficiencyColor($competency->proficiency_level->value) }}"></div>
                        </div>
                        <small class="text-muted d-block mt-2">{{ $competency->proficiency_level->score() }}/100</small>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <a href="{{ route('student.competencies.edit', $competency) }}" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Edit Competency
                    </a>
                    <a href="{{ route('student.competencies.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Back
                    </a>
                </div>
            </div>
        </div>

        <!-- Info Box -->
        <div class="alert alert-info mt-4">
            <strong><i class="bi bi-info-circle"></i> Pro Tip:</strong> Keep your competencies up to date. Employers use this information to match you with relevant internship opportunities.
        </div>
    </div>
</div>
@endsection

@php
function getProficiencyColor($level) {
    return match($level) {
        'beginner' => '#6B7280',
        'intermediate' => '#F59E0B',
        'advanced' => '#3B82F6',
        'expert' => '#10B981',
        default => '#6B7280',
    };
}
@endphp
