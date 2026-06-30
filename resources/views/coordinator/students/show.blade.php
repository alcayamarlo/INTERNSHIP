@extends('layouts.app')

@section('title', $student->user->name)

@section('content')
<div class="mb-3">
    <a href="{{ route('coordinator.students.index') }}" class="text-decoration-none"><i class="bi bi-arrow-left"></i> Back to Students</a>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                @if($student->profile_picture)
                    <img src="{{ Storage::url($student->profile_picture) }}" class="rounded-circle mb-3" width="100" height="100" style="object-fit:cover;">
                @else
                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center mb-3" style="width:100px;height:100px;">
                        <i class="bi bi-person fs-1 text-muted"></i>
                    </div>
                @endif
                <h4>{{ $student->user->name }}</h4>
                <p class="text-muted">{{ $student->user->email }}</p>
                <p class="small">{{ $student->institution?->name ?? 'No institution' }}</p>
                <div class="progress mb-2" style="height:8px;">
                    <div class="progress-bar bg-success" style="width:{{ $student->profile_completion ?? 0 }}%"></div>
                </div>
                <small>Profile {{ $student->profile_completion ?? 0 }}% complete</small>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header bg-white"><h5 class="mb-0">Profile Details</h5></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6"><strong>Program:</strong> {{ $student->program ?? '—' }}</div>
                    <div class="col-md-6"><strong>Year Level:</strong> {{ $student->year_level ?? '—' }}</div>
                    <div class="col-md-6"><strong>Student ID:</strong> {{ $student->student_id_number ?? '—' }}</div>
                    <div class="col-md-6"><strong>Phone:</strong> {{ $student->user->phone ?? '—' }}</div>
                    @if($student->career_objectives)
                        <div class="col-12 mt-3"><strong>Career Objectives:</strong><p class="mb-0">{{ $student->career_objectives }}</p></div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-white"><h5 class="mb-0">Competencies ({{ $student->competencies->count() }})</h5></div>
            <div class="card-body">
                @forelse($student->competencies as $comp)
                    <span class="badge bg-primary me-1 mb-1">{{ $comp->name }} — {{ $comp->proficiency_level->label() }}</span>
                @empty
                    <p class="text-muted mb-0">No competencies recorded.</p>
                @endforelse
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-white"><h5 class="mb-0">Applications</h5></div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light"><tr><th>Internship</th><th>Company</th><th>Match</th><th>Status</th><th>Applied</th></tr></thead>
                    <tbody>
                        @forelse($student->applications as $app)
                            <tr>
                                <td>{{ $app->internship->title }}</td>
                                <td>{{ $app->internship->employer->company_name }}</td>
                                <td>{{ $app->match_percentage }}%</td>
                                <td><span class="badge {{ $app->status->badgeClass() }}">{{ $app->status->label() }}</span></td>
                                <td>{{ $app->applied_at?->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-3">No applications.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
