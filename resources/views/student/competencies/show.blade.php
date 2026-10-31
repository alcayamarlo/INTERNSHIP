@extends('layouts.app')

@section('title', $competency->name)

@section('content')
<x-page-header :title="$competency->name" :subtitle="$competency->issuing_organization ?: 'Certificate'">
    <a href="{{ route('student.competencies.index') }}" class="btn btn-outline-secondary"><i class="bi bi-chevron-left" aria-hidden="true"></i> Back to My Certificates</a>
    <a href="{{ route('student.competencies.index', ['edit' => $competency->id]) }}" class="btn btn-primary"><i class="bi bi-pencil" aria-hidden="true"></i> Edit certificate</a>
</x-page-header>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2 mb-4">
                    @if($competency->certificate_type)<span class="sb-tag">{{ $competency->certificate_type->label() }}</span>@endif
                    @if($competency->skill_type)<span class="sb-tag">{{ $competency->skill_type->label() }}</span>@endif
                    <span class="sb-tag sb-tag--brand">{{ $competency->proficiency_level->label() }}</span>
                </div>

                @if($competency->learnedSkills->isNotEmpty())
                    <h2 class="h6">Skills you learned</h2>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        @foreach($competency->learnedSkills as $learnedSkill)
                            <span class="sb-tag">{{ $learnedSkill->name }}</span>
                        @endforeach
                    </div>
                @endif

                <dl class="row mb-0">
                    <dt class="col-sm-4">Who gave it?</dt>
                    <dd class="col-sm-8">{{ $competency->issuing_organization ?: 'Not added' }}</dd>
                    <dt class="col-sm-4">Date you received it</dt>
                    <dd class="col-sm-8">{{ $competency->obtained_at?->format('M d, Y') ?? 'Not added' }}</dd>
                    @if($competency->description)
                        <dt class="col-sm-4">Short note</dt>
                        <dd class="col-sm-8">{{ $competency->description }}</dd>
                    @endif
                    <dt class="col-sm-4">Your certificate</dt>
                    <dd class="col-sm-8">
                        @if($competency->evidence_path)
                            <a href="{{ route('student.competencies.file', $competency) }}" target="_blank" rel="noopener">{{ $competency->evidence_name ?: basename($competency->evidence_path) }}</a>
                        @else
                            No file attached
                        @endif
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>
@endsection
