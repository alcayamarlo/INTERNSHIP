@extends('layouts.app')

@section('title', 'Sent Invitations')

@section('content')
<x-page-header title="Sent Invitations" subtitle="Students you invited to apply. Times are in Philippine time.">
    <a href="{{ route('employer.students.index') }}" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Browse students</a>
</x-page-header>

<p class="small text-muted">Sent today: {{ $sentToday }} of {{ $dailyLimit }}</p>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Student</th><th>Internship</th><th>Status</th><th>Sent</th><th>Expires / responded</th><th><span class="visually-hidden">Actions</span></th></tr>
            </thead>
            <tbody>
                @forelse($invitations as $invitation)
                    @php($summary = $studentSummaries[$invitation->id])
                    <tr>
                        <td>{{ $summary['name'] }}<small class="d-block text-muted">{{ $summary['program'] }}</small></td>
                        <td>{{ $invitation->internship?->title }}</td>
                        <td><span class="badge {{ $invitation->status->badgeClass() }}">{{ $invitation->status->label() }}</span></td>
                        <td>{{ $invitation->created_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}</td>
                        <td>
                            @if($invitation->responded_at)
                                {{ $invitation->responded_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}
                            @else
                                {{ $invitation->expires_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}
                            @endif
                        </td>
                        <td>
                            @if($invitation->application_id)
                                <a href="{{ route('employer.applicants.show', $invitation->application_id) }}" class="btn btn-sm btn-outline-primary">View application</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">No invitations sent yet. <a href="{{ route('employer.students.index') }}">Browse students</a> to invite one.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invitations->hasPages())<div class="card-footer">{{ $invitations->links() }}</div>@endif
</div>
@endsection
