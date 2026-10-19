@extends('layouts.app')

@section('title', $competency->name)

@section('content')

@php
    $proficiencyTone = match ($competency->proficiency_level->value) {
        'expert' => 'success',
        'advanced' => 'info',
        'intermediate' => 'warning',
        default => 'neutral',
    };
@endphp

<x-page-header :title="$competency->name" :subtitle="$competency->category->label().' competency'">
    <a href="{{ route('student.competencies.index') }}" class="btn btn-outline-secondary"><i class="bi bi-chevron-left" aria-hidden="true"></i> Back to Competencies</a>
    <a href="{{ route('student.competencies.edit', $competency) }}" class="btn btn-primary"><i class="bi bi-pencil" aria-hidden="true"></i> Edit Competency</a>
</x-page-header>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">

                <div class="d-flex flex-wrap gap-2 mb-4">
                    <span class="sb-tag">{{ $competency->category->label() }}</span>
                    <x-status-badge :status="$competency->proficiency_level->value" :tone="$proficiencyTone" :label="$competency->proficiency_level->label()" />
                    @if($competency->verification_status === 'verified')
                        <x-status-badge status="verified" label="Verified" />
                    @endif
                </div>

                @if($competency->description)
                    <div class="mb-4">
                        <h2 class="h6">Description</h2>
                        <p class="text-muted mb-0">{{ $competency->description }}</p>
                    </div>
                @endif

                <div class="row">
                    @if($competency->obtained_at)
                        <div class="col-md-6 mb-3">
                            <div class="text-muted small">Date Obtained</div>
                            <p class="mb-0">{{ $competency->obtained_at->format('M d, Y') }}</p>
                        </div>
                    @endif

                    <div class="col-md-6 mb-3">
                        <div class="text-muted small">Added On</div>
                        <p class="mb-0">{{ $competency->created_at->format('M d, Y') }}</p>
                    </div>
                </div>

                <div class="sb-tile mt-3">
                    <h3><i class="bi bi-speedometer2" aria-hidden="true"></i> Proficiency Details</h3>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Current Level:</strong> {{ $competency->proficiency_level->label() }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-0"><strong>Score:</strong> {{ $competency->proficiency_level->score() }}/100</p>
                        </div>
                    </div>

                    <div class="mt-2">
                        <div class="text-muted small mb-1">Level progression</div>
                        <div class="progress" role="progressbar" aria-label="Proficiency score" aria-valuenow="{{ $competency->proficiency_level->score() }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar" style="width: {{ $competency->proficiency_level->score() }}%"></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="alert alert-info mt-4">
            <strong><i class="bi bi-info-circle" aria-hidden="true"></i> Pro Tip:</strong> Keep your competencies up to date. Employers use this information to match you with relevant internship opportunities.
        </div>
    </div>
</div>
@endsection
