@extends('layouts.app')

@section('title', 'Intern · '.($placement->student?->user?->name ?? 'OJT'))

@section('content')
<x-page-header :title="$placement->student?->user?->name ?? 'Intern'" subtitle="OJT supervision record">
    <a href="{{ route('employer.interns.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back</a>
    @if($placement->student?->user)
        <a href="{{ route('messages.show', $placement->student->user) }}" class="btn btn-outline-primary"><i class="bi bi-chat-dots" aria-hidden="true"></i> Message</a>
    @endif
</x-page-header>

@include('ojt.partials.progress', ['placement' => $placement])

@if($placement->isActive())
    <div class="card mb-4">
        <div class="card-header"><h2 class="h6 mb-0"><i class="bi bi-calendar-range" aria-hidden="true"></i> OJT schedule</h2></div>
        <div class="card-body">
            <form method="POST" action="{{ route('employer.interns.schedule', $placement) }}" class="row g-2 align-items-end">
                @csrf @method('PUT')
                <div class="col-md-4">
                    <label class="form-label" for="start_date">Start date</label>
                    <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $placement->start_date->toDateString()) }}" class="form-control @error('start_date') is-invalid @enderror" required>
                    @error('start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="end_date">Expected end date</label>
                    <input type="date" id="end_date" name="end_date" value="{{ old('end_date', $placement->end_date?->toDateString()) }}" class="form-control @error('end_date') is-invalid @enderror">
                    @error('end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4"><button type="submit" class="btn btn-outline-primary w-100">Save schedule</button></div>
            </form>
        </div>
    </div>
@endif

@include('ojt.partials.attendance', ['placement' => $placement, 'mode' => 'employer'])
@include('ojt.partials.journals', ['placement' => $placement, 'mode' => 'employer'])

<div class="card mb-4">
    <div class="card-header"><h2 class="h6 mb-0"><i class="bi bi-clipboard-check" aria-hidden="true"></i> Performance evaluation</h2></div>
    <div class="card-body">
        @if($placement->isCleared())
            <p class="text-muted">The coordinator has cleared this OJT, so the evaluation is locked.</p>
            @include('ojt.partials.evaluation', ['placement' => $placement])
        @else
            @php $evaluation = $placement->evaluation; @endphp
            <p class="small text-muted">Rate each criterion from 1 (poor) to 5 (outstanding). You can update the evaluation until the coordinator clears the OJT.</p>
            <form method="POST" action="{{ route('employer.interns.evaluation.store', $placement) }}">
                @csrf
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th scope="col">Criterion</th>
                                @foreach(\App\Models\Evaluation::SCALE as $value => $label)
                                    <th scope="col" class="text-center small">{{ $value }}<div class="text-muted fw-normal">{{ $label }}</div></th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(\App\Models\Evaluation::CRITERIA as $key => $label)
                                @php $current = (int) old('scores.'.$key, $evaluation?->scores[$key] ?? 0); @endphp
                                <tr>
                                    <td>{{ $label }} @error('scores.'.$key)<div class="text-danger small">{{ $message }}</div>@enderror</td>
                                    @foreach(\App\Models\Evaluation::SCALE as $value => $scaleLabel)
                                        <td class="text-center">
                                            <input class="form-check-input" type="radio" name="scores[{{ $key }}]" value="{{ $value }}" aria-label="{{ $label }}: {{ $value }} {{ $scaleLabel }}" @checked($current === $value) required>
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <label class="form-label" for="strengths">Strengths</label>
                        <textarea id="strengths" name="strengths" rows="2" class="form-control" maxlength="2000">{{ old('strengths', $evaluation?->strengths) }}</textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="improvements">Areas for improvement</label>
                        <textarea id="improvements" name="improvements" rows="2" class="form-control" maxlength="2000">{{ old('improvements', $evaluation?->improvements) }}</textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="comments">Overall comments</label>
                        <textarea id="comments" name="comments" rows="2" class="form-control" maxlength="2000">{{ old('comments', $evaluation?->comments) }}</textarea>
                    </div>
                    <div class="col-12">
                        <input type="hidden" name="would_hire" value="0">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="would_hire" name="would_hire" value="1" @checked(old('would_hire', $evaluation?->would_hire))>
                            <label class="form-check-label" for="would_hire">We would consider hiring this intern</label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">{{ $evaluation ? 'Update evaluation' : 'Submit evaluation' }}</button>
            </form>
        @endif
    </div>
</div>
@endsection
