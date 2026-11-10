{{-- One invitation in the detail panel: progress stepper, posting, dates, and actions.
     Used for the first render and for each row's <template> (index.blade.php). --}}
@use('App\Enums\ApplicationStatus')
@use('App\Enums\InvitationStatus')
@php
    $internship = $invitation->internship;
    $employer = $internship->employer;
    $display = $displayStatus($invitation);
    $invitedAt = $invitation->created_at->timezone('Asia/Manila');
    $expiresAt = $invitation->expires_at->timezone('Asia/Manila');
    $respondedAt = $invitation->responded_at?->timezone('Asia/Manila');
    $appStatus = $invitation->application?->status;
    $outcome = match (true) {
        in_array($appStatus, [ApplicationStatus::Accepted, ApplicationStatus::Completed], true) => ['Hired', 'done'],
        $appStatus === ApplicationStatus::Rejected => ['Not selected', 'negative'],
        $appStatus === ApplicationStatus::Withdrawn => ['Withdrawn', 'negative'],
        default => null,
    };

    // Invited -> Your response -> Company review -> Outcome. $current is the step it waits on (null: none),
    // $progress how far the line is filled (0 to 1, as on the application page).
    $invited = ['label' => 'Invited', 'note' => $invitedAt->format('M j'), 'state' => 'done'];
    [$steps, $current, $progress] = match (true) {
        $display === InvitationStatus::Pending => [[
            $invited,
            ['label' => 'Your response', 'note' => 'Waiting for you', 'state' => 'pending'],
            ['label' => 'Company review', 'note' => 'After you accept', 'state' => 'pending'],
            ['label' => 'Outcome', 'note' => 'Hired or not', 'state' => 'pending'],
        ], 1, 1 / 3],
        $display === InvitationStatus::Expired => [[
            $invited,
            ['label' => 'Your response', 'note' => 'Expired', 'state' => 'negative'],
            ['label' => 'Company review', 'note' => 'Not sent', 'state' => 'pending'],
            ['label' => 'Outcome', 'note' => '—', 'state' => 'pending'],
        ], 1, 1 / 3],
        $display === InvitationStatus::Declined => [[
            $invited,
            ['label' => 'Your response', 'note' => 'Declined', 'state' => 'done'],
            ['label' => 'Company review', 'note' => 'Declined', 'state' => 'pending'],
            ['label' => 'Outcome', 'note' => 'Declined', 'state' => 'pending'],
        ], null, 1 / 3],
        $outcome !== null => [[
            $invited,
            ['label' => 'Your response', 'note' => 'Accepted', 'state' => 'done'],
            ['label' => 'Company review', 'note' => 'Done', 'state' => 'done'],
            ['label' => 'Outcome', 'note' => $outcome[0], 'state' => $outcome[1]],
        ], 3, 1],
        default => [[
            $invited,
            ['label' => 'Your response', 'note' => 'Accepted'.($respondedAt ? ' '.$respondedAt->format('M j') : ''), 'state' => 'done'],
            ['label' => 'Company review', 'note' => $appStatus?->label() ?? 'In review', 'state' => 'pending'],
            ['label' => 'Outcome', 'note' => 'Waiting', 'state' => 'pending'],
        ], 2, 2 / 3],
    };
    $stateText = ['done' => 'completed', 'negative' => 'stopped', 'pending' => 'not started'];

    // Server-side "days left" for the first paint; the page script keeps it current.
    $secondsLeft = now()->diffInSeconds($invitation->expires_at, false);
    $daysLeft = $secondsLeft >= 86400 ? (int) ceil($secondsLeft / 86400) : null;
    $hoursLeft = $secondsLeft > 0 && $secondsLeft < 86400 ? (int) ceil($secondsLeft / 3600) : null;
    $leftText = $daysLeft ? $daysLeft.' '.\Illuminate\Support\Str::plural('day', $daysLeft).' left'
        : ($hoursLeft ? $hoursLeft.' '.\Illuminate\Support\Str::plural('hour', $hoursLeft).' left' : null);
@endphp
<div class="inv-detail-inner" data-inv-detail="{{ $invitation->id }}">
    <div class="inv-stepper">
        <ol class="ap-steps" style="--ap-progress: {{ round($progress, 3) }}" aria-label="Invitation progress">
            @foreach($steps as $index => $step)
                <li class="ap-step is-{{ $step['state'] }} {{ $index === $current ? 'is-current' : '' }}" @if($index === $current) aria-current="step" @endif>
                    <span class="ap-step-dot" aria-hidden="true">{{ $index + 1 }}</span>
                    <span class="ap-step-label">{{ $step['label'] }}</span>
                    <span class="ap-step-note">{{ $step['note'] }}</span>
                    <span class="visually-hidden">, {{ $index === $current ? 'current step' : $stateText[$step['state']] }}</span>
                </li>
            @endforeach
        </ol>
    </div>

    <div class="inv-head">
        <h2 class="inv-title">{{ $internship->title }}</h2>
        <span class="ap-status ap-status--{{ $pillClass[$display->value] }}">{{ $display->label() }}</span>
    </div>
    <p class="inv-company">{{ $employer->company_name }} <x-verified-badge :employer="$employer" /></p>
    <p class="inv-meta">{{ $internship->location ?: 'Location not set' }}@if($internship->work_setup) · {{ $internship->work_setup->label() }}@endif</p>

    <p class="inv-dates">
        Invited {{ $invitedAt->format('M d, Y h:i A') }}
        @if($display === InvitationStatus::Pending)
            · <span data-expires="{{ $invitation->expires_at->toIso8601String() }}">Expires {{ $expiresAt->format('M d, Y h:i A') }}
                <span data-days-left>@if($leftText)({{ $leftText }})@endif</span></span>
        @elseif($display === InvitationStatus::Expired)
            · <strong>Expired</strong> {{ $expiresAt->format('M d, Y h:i A') }}
        @elseif($respondedAt)
            · {{ $display->label() }} {{ $respondedAt->format('M d, Y h:i A') }}
        @endif
    </p>

    @if($invitation->message)
        <blockquote class="inv-message">{{ $invitation->message }}</blockquote>
    @endif

    <section class="inv-explain">
        <h3>What happens when you accept</h3>
        <p>Accepting applies you to this internship. Your profile and latest resume are sent to {{ $employer->company_name }},
            and you can then message the company. The company reviews your application and tells you the outcome.</p>
    </section>

    <div class="inv-actions">
        <a href="{{ route('student.internships.show', $internship) }}" class="btn btn-outline-secondary">View internship</a>
        @if($invitation->isPending() && ($hasOjt ?? false))
            {{-- One OJT per student: accepting would apply, so it is not offered once placed. Declining still works. --}}
            <form method="POST" action="{{ route('student.invitations.decline', $invitation) }}" data-inv-action="decline" data-inv-id="{{ $invitation->id }}"
                data-confirm="Decline this invitation? The company will be told you declined." data-confirm-title="Decline invitation" data-confirm-label="Decline">
                @csrf
                <button type="submit" class="btn btn-outline-danger">Decline</button>
            </form>
        @elseif($invitation->isPending())
            <form method="POST" action="{{ route('student.invitations.accept', $invitation) }}" data-inv-action="accept" data-inv-id="{{ $invitation->id }}"
                data-confirm="Accept and apply for {{ $internship->title }}? Your profile and latest resume will be sent to {{ $employer->company_name }}."
                data-confirm-title="Accept invitation" data-confirm-label="Accept">
                @csrf
                <button type="submit" class="btn btn-primary" data-inv-accept @disabled(! $hasResume || $display === InvitationStatus::Expired)>Accept</button>
            </form>
            <form method="POST" action="{{ route('student.invitations.decline', $invitation) }}" data-inv-action="decline" data-inv-id="{{ $invitation->id }}"
                data-confirm="Decline this invitation? The company will be told you declined." data-confirm-title="Decline invitation" data-confirm-label="Decline">
                @csrf
                <button type="submit" class="btn btn-outline-danger">Decline</button>
            </form>
        @elseif($invitation->application_id)
            <a href="{{ route('student.applications.show', $invitation->application_id) }}" class="btn btn-outline-primary">View application</a>
        @endif
    </div>
    @if($invitation->isPending() && ($hasOjt ?? false))
        <p class="inv-hint" data-has-ojt><i class="bi bi-lock-fill" aria-hidden="true"></i> {{ \App\Services\ApplicationService::HAS_OJT_MESSAGE }} <a href="{{ route('student.ojt.index') }}">Go to My OJT</a></p>
    @elseif($invitation->isPending() && ! $hasResume && $display !== InvitationStatus::Expired)
        <p class="inv-hint">Generate your <a href="{{ route('student.resume.index') }}">resume</a> first to accept.</p>
    @endif
</div>
