@extends('layouts.app')

@section('title', $program->exists ? 'Edit program' : 'Add program')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/admin-academics.css') }}">
@endpush

@section('content')
<div class="admin-academics-page">
<x-page-header :title="$program->exists ? 'Edit program' : 'Add program'" subtitle="Set the program details, available majors, required internship hours, and assigned coordinator." />

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ $program->exists ? route('admin.academics.programs.update', $program) : route('admin.academics.programs.store') }}">
            @csrf
            @if($program->exists) @method('PUT') @endif

            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" for="name">Program name <span class="text-danger" aria-hidden="true">*</span></label>
                    <input id="name" name="name" value="{{ old('name', $program->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="code">Program code <span class="text-danger" aria-hidden="true">*</span></label>
                    <input id="code" name="code" value="{{ old('code', $program->code) }}" class="form-control @error('code') is-invalid @enderror" required>
                    @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="required_internship_hours">Required internship hours <span class="text-danger" aria-hidden="true">*</span></label>
                    <input id="required_internship_hours" name="required_internship_hours" type="number" min="1" max="10000" value="{{ old('required_internship_hours', $program->required_internship_hours) }}" class="form-control @error('required_internship_hours') is-invalid @enderror" required>
                    @error('required_internship_hours')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input type="hidden" name="required_hours_confirmed" value="0">
                        <input class="form-check-input" type="checkbox" id="required_hours_confirmed" name="required_hours_confirmed" value="1" @checked(old('required_hours_confirmed', $program->required_hours_confirmed))>
                        <label class="form-check-label" for="required_hours_confirmed">Hours confirmed by the school</label>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label" for="coordinator_id">Assigned coordinator</label>
                    <select id="coordinator_id" name="coordinator_id" class="form-select @error('coordinator_id') is-invalid @enderror">
                        <option value="">Not assigned</option>
                        @foreach($coordinators as $coordinator)
                            <option value="{{ $coordinator->id }}" @selected((string) old('coordinator_id', $program->coordinator_id) === (string) $coordinator->id)>{{ $coordinator->user?->name ?? 'Coordinator '.$coordinator->id }}</option>
                        @endforeach
                    </select>
                    @error('coordinator_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label class="form-label" for="majors">Majors</label>
                    <textarea id="majors" name="majors" rows="5" class="form-control @error('majors') is-invalid @enderror" aria-describedby="majorHelp">{{ old('majors', $program->majors->pluck('name')->join("\n")) }}</textarea>
                    <div id="majorHelp" class="form-text">Enter one major per line. Leave blank when the program has no majors.</div>
                    @error('majors')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-4">Save program</button>
            <a href="{{ route('admin.academics.index') }}" class="btn btn-outline-secondary mt-4">Cancel</a>
        </form>
    </div>
</div>
</div>
@endsection
