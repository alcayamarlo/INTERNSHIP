@extends('layouts.app')

@section('title', 'Invitations')

@section('content')
<x-page-header title="Invitations" subtitle="Companies that invited you to apply. Accepting sends your profile and latest resume." />

@if(session('needs_resume'))
    <div class="alert alert-warning"><a href="{{ route('student.resume.index') }}" class="alert-link">Generate your resume</a>, then come back to accept.</div>
@endif

@forelse($invitations as $invitation)
    @php
        $internship = $invitation->internship;
        $lapsed = $invitation->isPending() && $invitation->hasLapsed();
        $status = $lapsed ? \App\Enums\InvitationStatus::Expired : $invitation->status;
    @endphp
    <article class="card mb-3" aria-labelledby="invitation-{{ $invitation->id }}">
        <div class="card-body">
            <div class="d-flex justify-content-between flex-wrap gap-2">
                <div>
                    <h2 class="h5 mb-1" id="invitation-{{ $invitation->id }}">{{ $internship->title }}</h2>
                    <p class="text-muted mb-0">{{ $internship->employer->company_name }} · {{ $internship->location ?: 'Location not set' }}@if($internship->work_setup) · {{ $internship->work_setup->label() }}@endif</p>
                </div>
                <span class="badge {{ $status->badgeClass() }} align-self-start">{{ $status->label() }}</span>
            </div>

            @if($invitation->message)
                <blockquote class="border-start border-3 ps-3 my-3 text-body">{{ $invitation->message }}</blockquote>
            @endif

            <p class="small text-muted mb-3">
                Invited {{ $invitation->created_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}
                @if($status === \App\Enums\InvitationStatus::Pending)
                    · Expires {{ $invitation->expires_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}
                @elseif($invitation->responded_at)
                    · {{ $status->label() }} {{ $invitation->responded_at->timezone('Asia/Manila')->format('M d, Y h:i A') }}
                @endif
            </p>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('student.internships.show', $internship) }}" class="btn btn-outline-secondary btn-sm">View internship</a>
                @if($status === \App\Enums\InvitationStatus::Pending)
                    <form method="POST" action="{{ route('student.invitations.accept', $invitation) }}"
                        data-confirm="Accept and apply for {{ $internship->title }}? Your profile and latest resume will be sent to {{ $internship->employer->company_name }}."
                        data-confirm-title="Accept invitation" data-confirm-label="Accept">
                        @csrf
                        <button type="submit" class="btn btn-primary btn-sm" @disabled(! $hasResume)>Accept</button>
                    </form>
                    <form method="POST" action="{{ route('student.invitations.decline', $invitation) }}"
                        data-confirm="Decline this invitation? The company will be told you declined." data-confirm-title="Decline invitation" data-confirm-label="Decline">
                        @csrf
                        <button type="submit" class="btn btn-outline-danger btn-sm">Decline</button>
                    </form>
                    @unless($hasResume)
                        <small class="text-muted align-self-center">Generate your <a href="{{ route('student.resume.index') }}">resume</a> first to accept.</small>
                    @endunless
                @elseif($invitation->application_id)
                    <a href="{{ route('student.applications.show', $invitation->application_id) }}" class="btn btn-outline-primary btn-sm">View application</a>
                @endif
            </div>
        </div>
    </article>
@empty
    <div class="card"><div class="card-body text-center text-muted py-5">No invitations yet. Keep your profile complete so companies can find you.</div></div>
@endforelse

@if($invitations->hasPages())<div class="mt-3">{{ $invitations->links() }}</div>@endif
@endsection
