@extends('layouts.app')

@section('title', 'Students')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Students</h1>
        <p class="text-muted mb-0">Monitor student profiles and progress</p>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr><th>Name</th><th>Email</th><th>Institution</th><th>Program</th><th>Competencies</th><th>Profile</th><th>Actions</th></tr>
            </thead>
            <tbody>
                @forelse($students as $student)
                    <tr>
                        <td><strong>{{ $student->user->name }}</strong></td>
                        <td>{{ $student->user->email }}</td>
                        <td>{{ $student->institution?->name ?? '—' }}</td>
                        <td>{{ $student->program ?? '—' }}</td>
                        <td>{{ $student->competencies->count() }}</td>
                        <td>
                            <div class="progress" style="width:80px;height:6px;">
                                <div class="progress-bar bg-success" style="width:{{ $student->profile_completion ?? 0 }}%"></div>
                            </div>
                            <small>{{ $student->profile_completion ?? 0 }}%</small>
                        </td>
                        <td><a href="{{ route('coordinator.students.show', $student) }}" class="btn btn-sm btn-primary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">No students found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($students->hasPages())<div class="card-footer">{{ $students->links() }}</div>@endif
</div>
@endsection
