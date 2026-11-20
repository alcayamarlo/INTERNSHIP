@extends('layouts.app')

@section('title', $program->exists ? 'Edit program' : 'Add program')

@section('content')
<div class="sb-dash">
<x-page-header :title="$program->exists ? 'Edit program' : 'Add program'" subtitle="Set the program details, available majors, required internship hours, and assigned coordinator." />

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ $program->exists ? route($academicsRoute.'programs.update', $program) : route($academicsRoute.'programs.store') }}">
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
                <div class="col-12" data-coordinator-assignment>
                    <span class="form-label d-block">Coordinators</span>
                    @if($coordinatorChoices !== null)
                        <input type="hidden" name="coordinators_submitted" value="1">
                        @php $chosen = array_map('intval', (array) old('coordinators', $program->coordinators->pluck('user_id')->all())); @endphp
                        <div class="d-flex flex-column gap-1" role="group" aria-label="Coordinators of this department">
                            @forelse($coordinatorChoices as $choice)
                                @php $elsewhere = $choice->coordinator?->program && $choice->coordinator->program_id !== $program->id; @endphp
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="coordinators[]" value="{{ $choice->id }}" id="coord{{ $choice->id }}" data-coordinator-box @checked(in_array($choice->id, $chosen, true))>
                                    <label class="form-check-label" for="coord{{ $choice->id }}">{{ $choice->name }}@if($elsewhere) <span class="text-muted small">(now in {{ $choice->coordinator->program->code }}: moves here)</span>@endif</label>
                                </div>
                            @empty
                                <p class="text-muted small mb-0">No coordinator accounts yet. Create one under Users first.</p>
                            @endforelse
                        </div>
                        @error('coordinators')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        @error('coordinators.*')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        <div class="form-text">Choose one: a program has one coordinator. Only users with the coordinator role are listed. Unticking a coordinator leaves them without a department until you assign one.</div>
                        <script>
                            (function () {
                                var boxes = document.querySelectorAll('[data-coordinator-box]');
                                function limit() { var n = 0; boxes.forEach(function (b) { if (b.checked) n++; }); boxes.forEach(function (b) { b.disabled = !b.checked && n >= {{ \App\Models\Coordinator::MAX_PER_DEPARTMENT }}; }); }
                                boxes.forEach(function (b) { b.addEventListener('change', limit); }); limit();
                            })();
                        </script>
                    @else
                    <p class="mb-1" data-assigned-coordinator>{{ $program->coordinatorNames() ?? 'No coordinator assigned' }}</p>
                    <div class="form-text">Each program has one coordinator. An admin assigns them from Users when creating or editing the coordinator's account.</div>
                    @endif
                </div>
                <div class="col-12">
                    <label class="form-label" for="majors">Majors</label>
                    <textarea id="majors" name="majors" rows="5" class="form-control @error('majors') is-invalid @enderror" aria-describedby="majorHelp">{{ old('majors', $program->majors->pluck('name')->join("\n")) }}</textarea>
                    <div id="majorHelp" class="form-text">Enter one major per line. Leave blank when the program has no majors.</div>
                    @error('majors')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-4">Save program</button>
            <a href="{{ route($academicsRoute.'index') }}" class="btn btn-outline-secondary mt-4">Cancel</a>
        </form>
    </div>
</div>
</div>
@endsection
