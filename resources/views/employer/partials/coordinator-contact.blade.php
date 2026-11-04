{{-- The student's OJT coordinator, so the company can coordinate with the school. --}}
@php($coordinator = $student?->assignedCoordinator())
<div class="card mb-4" data-coordinator-contact>
    <div class="card-header"><h2 class="h6 mb-0"><i class="bi bi-mortarboard" aria-hidden="true"></i> OJT coordinator</h2></div>
    <div class="card-body">
        @if($coordinator?->user)
            <p class="mb-1"><strong>{{ $coordinator->user->name }}</strong></p>
            <p class="small text-muted mb-3">{{ collect([$coordinator->department, $coordinator->institution?->name])->filter()->implode(' · ') ?: 'OJT coordinator' }}</p>
            @can('message.send', $coordinator->user)
                <a href="{{ route('messages.show', $coordinator->user) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-chat-dots" aria-hidden="true"></i> Message coordinator</a>
            @endcan
        @else
            <p class="text-muted small mb-0">No coordinator is assigned to this student yet.</p>
        @endif
    </div>
</div>
