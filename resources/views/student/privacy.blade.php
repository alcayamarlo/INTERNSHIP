@extends('layouts.app')

@section('title', 'Privacy and my data')

@section('content')
<div class="sb-dash">
<x-page-header title="Privacy and my data" subtitle="What the school keeps about you, and how to ask for your account to be deleted." />

<div class="card">
    <div class="card-body">
        <h2 class="h6">What we keep</h2>
        <p class="small mb-2">Your profile, documents, applications and OJT records are used to place and supervise your internship. Sensitive fields are stored encrypted. The <a href="{{ route('pages.privacy') }}" target="_blank" rel="noopener">privacy notice</a> explains who can see them and for how long. You can correct most details under <a href="{{ route('student.profile.edit') }}">My Profile</a>.</p>
        <p class="small text-muted mb-0">An account that is not used for {{ $retentionYears }} years is listed for an administrator to review. Nothing is deleted automatically.</p>
    </div>
</div>

<div class="card" data-deletion-card>
    <div class="card-body">
        <h2 class="h6">Delete my account</h2>

        @if($pending)
            <div class="alert alert-info mb-3" role="status" data-deletion-pending>
                <i class="bi bi-hourglass-split" aria-hidden="true"></i>
                Your request was sent on {{ $pending->created_at->timezone('Asia/Manila')->format('M d, Y h:i A') }} and is waiting for an administrator. Your account works as usual until it is decided.
            </div>
            <form method="POST" action="{{ route('student.privacy.deletion-cancel') }}" data-confirm="Cancel your deletion request? Your account stays as it is." data-confirm-title="Cancel request" data-confirm-label="Cancel request">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline-secondary">Cancel my request</button>
            </form>
        @else
            <p class="small">If you ask to delete your account and an administrator approves it, this happens and <strong>cannot be undone</strong>:</p>
            <ul class="small">
                <li><strong>Removed:</strong> your name, email, phone, school ID and profile details; your picture, ID photo, resumes, certificates, portfolio and competency files{{ $preview['records_removed'] ? " ({$preview['records_removed']} records now)" : '' }}; your messages ({{ $preview['messages'] }} now) and notifications. You will no longer be able to sign in.</li>
                <li><strong>Kept, without your name:</strong> the OJT record itself: {{ $preview['placements_kept'] }} placement{{ $preview['placements_kept'] === 1 ? '' : 's' }}, {{ $preview['hours_kept'] }} hours, evaluations and reports. They are anonymous and cannot be traced to you.</li>
                <li>Backups made before the deletion still hold your old data until they are replaced (about 30 days).</li>
            </ul>
            @if($preview['active_placements'] > 0)
                <div class="alert alert-warning small" role="alert" data-active-ojt-warning><i class="bi bi-exclamation-triangle" aria-hidden="true"></i> You have an OJT in progress. Deleting your account now would end your access to its records. Talk to your coordinator first.</div>
            @endif
            <form method="POST" action="{{ route('student.privacy.deletion-request') }}" class="mt-3" style="max-width: 36rem">
                @csrf
                <div class="mb-3">
                    <label class="form-label" for="deletionReason">Why? <span class="text-muted fw-normal">(optional)</span></label>
                    <textarea id="deletionReason" name="reason" rows="2" maxlength="500" class="form-control @error('reason') is-invalid @enderror">{{ old('reason') }}</textarea>
                    @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input @error('acknowledge') is-invalid @enderror" type="checkbox" name="acknowledge" value="1" id="deletionAcknowledge" required>
                    <label class="form-check-label" for="deletionAcknowledge">I understand that my account will be deleted and this cannot be undone.</label>
                    @error('acknowledge')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="mb-3">
                    <label class="form-label" for="deletionPassword">Your password</label>
                    <input id="deletionPassword" type="password" name="password" class="form-control @error('password') is-invalid @enderror" autocomplete="current-password" required>
                    @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-outline-danger"><i class="bi bi-person-x" aria-hidden="true"></i> Request account deletion</button>
            </form>
        @endif
    </div>
</div>

@if($history->isNotEmpty())
<div class="card">
    <div class="card-body">
        <h2 class="h6">Earlier requests</h2>
        @foreach($history as $item)
            <div class="small">{{ $item->created_at->timezone('Asia/Manila')->format('M d, Y') }} · {{ ucfirst($item->status) }}@if($item->decision_note) · {{ $item->decision_note }}@endif</div>
        @endforeach
    </div>
</div>
@endif
</div>
@endsection
