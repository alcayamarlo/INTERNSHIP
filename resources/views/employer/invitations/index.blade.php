@extends('layouts.app')

@section('title', 'Sent Invitations')

@section('content')
<div class="sb-dash">
<x-page-header title="Sent Invitations" subtitle="Students you invited to apply. Times are in Philippine time.">
    <a href="{{ route('employer.students.index') }}" class="btn btn-primary"><i class="bi bi-search" aria-hidden="true"></i> Browse students</a>
</x-page-header>

<p class="sb-note">Sent today: {{ $sentToday }} of {{ $dailyLimit }}</p>

<x-table-filters :action="route('employer.invitations.index')" :filters="$filters" :options="$filterOptions"
                 :showing="$invitations->total()" :total="$totalCount" target="#invitationResults" label="Filter sent invitations" />

<div id="invitationResults" data-tf-results>
<span data-tf-meta data-showing="{{ number_format($invitations->total()) }}" data-total="{{ number_format($totalCount) }}" hidden></span>
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
                        <td><div class="sb-person-text"><strong>{{ $summary['name'] }}</strong><span>{{ $summary['program'] }}</span></div></td>
                        <td>{{ $invitation->internship?->title }}</td>
                        <td><x-status-badge :status="$invitation->status->value" :label="$invitation->status->label()" /></td>
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
                    @if($filters->active())
                        <tr><td colspan="6" class="sb-table-empty"><x-empty-state icon="bi-envelope-paper" title="No results found. Try other filters or clear them." /></td></tr>
                    @else
                        <tr><td colspan="6" class="sb-table-empty"><div class="sb-empty"><span class="sb-empty-icon" aria-hidden="true"><i class="bi bi-envelope-paper"></i></span><h3>No invitations sent yet.</h3><p><a href="{{ route('employer.students.index') }}">Browse students</a> to invite one.</p></div></td></tr>
                    @endif
                @endforelse
            </tbody>
        </table>
    </div>
    @if($invitations->hasPages())<div class="card-footer">{{ $invitations->links() }}</div>@endif
</div>
</div>
</div>
@endsection
