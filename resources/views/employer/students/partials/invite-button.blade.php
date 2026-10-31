@php($invitedFor = $pendingInvites[$student['student_id']] ?? [])
<button type="button" class="btn btn-primary btn-sm" data-invite-student="{{ $student['student_id'] }}" data-invite-name="{{ $student['name'] }}">
    <i class="bi bi-envelope-plus" aria-hidden="true"></i> Invite to apply
</button>
@if($invitedFor)
    <small class="text-muted d-block mt-1">Invited to: {{ implode(', ', $invitedFor) }}</small>
@endif
