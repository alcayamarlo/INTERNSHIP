@extends('layouts.app')

@section('title', $employer->company_name)

@section('content')
<x-page-header :title="$employer->company_name" :subtitle="$employer->industry ?: 'Partner company'">
    <x-verified-badge :employer="$employer" />
    @include('partials.report-button', ['type' => 'company', 'id' => $employer->id, 'label' => $employer->company_name])
    <a href="{{ route('coordinator.companies.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> All companies</a>
    @if($employer->user)
        @can('message.send', $employer->user)
            <a href="{{ route('messages.show', $employer->user) }}" class="btn btn-primary"><i class="bi bi-chat-dots" aria-hidden="true"></i> Message company</a>
        @endcan
    @endif
</x-page-header>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><h2>Contact</h2></div>
            <div class="card-body small">
                <dl class="row mb-0">
                    <dt class="col-5">Contact person</dt><dd class="col-7">{{ $employer->contact_person ?: '—' }}</dd>
                    <dt class="col-5">Email</dt><dd class="col-7 text-break">{{ $employer->user?->email ?: '—' }}</dd>
                    <dt class="col-5">Phone</dt><dd class="col-7">{{ $employer->user?->phone ?: '—' }}</dd>
                    <dt class="col-5">Address</dt><dd class="col-7">{{ $employer->address ?: '—' }}</dd>
                    <dt class="col-5">Website</dt><dd class="col-7 text-break">{{ $employer->website ?: '—' }}</dd>
                </dl>
            </div>
        </div>

        <form method="POST" action="{{ route('coordinator.companies.moa.update', $employer) }}" class="card mb-4" data-moa-form>
            @csrf
            @method('PUT')
            <div class="card-header"><h2>MOA with the school</h2></div>
            <div class="card-body">
                <p class="small mb-3"><span class="badge {{ $employer->hasValidMoa() ? 'text-bg-success' : 'text-bg-secondary' }}">{{ $employer->moaStatus() }}</span></p>
                <label class="form-label" for="moaSignedOn">Signed on</label>
                <input id="moaSignedOn" type="date" name="moa_signed_on" class="form-control mb-2 @error('moa_signed_on') is-invalid @enderror" value="{{ old('moa_signed_on', $employer->moa_signed_on?->toDateString()) }}">
                @error('moa_signed_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <label class="form-label" for="moaExpiresOn">Expires on</label>
                <input id="moaExpiresOn" type="date" name="moa_expires_on" class="form-control @error('moa_expires_on') is-invalid @enderror" value="{{ old('moa_expires_on', $employer->moa_expires_on?->toDateString()) }}">
                @error('moa_expires_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <small class="form-text text-muted">Coordinators get a reminder 30 days before it expires.</small>
            </div>
            <div class="card-footer text-end"><button type="submit" class="btn btn-primary btn-sm">Save MOA dates</button></div>
        </form>
    </div>

    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header"><h2>Open postings</h2></div>
            <ul class="list-group list-group-flush">
                @forelse($internships as $internship)
                    @php($flag = $internship->flags->first())
                    <li class="list-group-item" data-internship="{{ $internship->id }}">
                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <div>
                                <strong>{{ $internship->title }}</strong>
                                <small class="d-block text-muted">Programs: {{ $internship->programs->pluck('code')->implode(', ') ?: 'All' }}</small>
                                @if($flag)<small class="d-block text-danger">Marked not suitable: {{ $flag->reason }}</small>@endif
                            </div>
                            @if($flag)
                                <form method="POST" action="{{ route('coordinator.internships.unflag', $internship) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Show to my students again</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('coordinator.internships.flag', $internship) }}" class="d-flex gap-2">
                                    @csrf
                                    <label class="visually-hidden" for="flagReason{{ $internship->id }}">Reason</label>
                                    <input id="flagReason{{ $internship->id }}" type="text" name="reason" maxlength="500" class="form-control form-control-sm" placeholder="Why it isn't suitable" required>
                                    <button type="submit" class="btn btn-sm btn-outline-danger text-nowrap">Mark not suitable</button>
                                </form>
                            @endif
                        </div>
                    </li>
                @empty
                    <li class="list-group-item text-muted">No open postings.</li>
                @endforelse
            </ul>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h2>Your students here</h2></div>
            <ul class="list-group list-group-flush">
                @forelse($interns as $placement)
                    <li class="list-group-item d-flex justify-content-between flex-wrap">
                        <span><a href="{{ route('coordinator.placements.show', $placement) }}">{{ $placement->student?->user?->name }}</a> · {{ $placement->internship?->title }}</span>
                        <small class="text-muted">{{ $placement->statusLabel() }} · {{ number_format($placement->approvedHours()) }} / {{ $placement->required_hours }} hours</small>
                    </li>
                @empty
                    <li class="list-group-item text-muted">None of your students are placed here.</li>
                @endforelse
            </ul>
        </div>

        <div class="card">
            <div class="card-header"><h2>Communication log</h2></div>
            <ul class="list-group list-group-flush">
                @forelse($messages as $message)
                    <li class="list-group-item small">
                        <strong>{{ $message->sender_id === auth()->id() ? 'You' : $employer->company_name }}</strong>
                        <span class="text-muted">· {{ $message->created_at->format('M d, Y h:i A') }}</span>
                        <div>{{ \Illuminate\Support\Str::limit($message->body, 200) }}</div>
                    </li>
                @empty
                    <li class="list-group-item text-muted">No messages with this company yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
@endsection
