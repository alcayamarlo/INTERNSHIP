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

<div class="sb-layout-2">
    <div class="sb-stack">
        <section class="card">
            <div class="card-header"><h2>Open postings</h2></div>
            @if($internships->isEmpty())
                <div class="card-body"><p class="sb-note">No open postings.</p></div>
            @else
                <ul class="sb-rows sb-rows--card">
                    @foreach($internships as $internship)
                        @php($flag = $internship->flags->first())
                        <li data-internship="{{ $internship->id }}">
                            <div class="sb-grow">
                                <span class="sb-row-title">{{ $internship->title }}</span>
                                <span class="sb-row-sub">Programs: {{ $internship->programs->pluck('code')->implode(', ') ?: 'All' }}</span>
                                @if($flag)<span class="sb-detail-alert">Marked not suitable: {{ $flag->reason }}</span>@endif
                            </div>
                            @if($flag)
                                <form method="POST" action="{{ route('coordinator.internships.unflag', $internship) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">Show to my students again</button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('coordinator.internships.flag', $internship) }}" class="d-flex flex-wrap gap-2">
                                    @csrf
                                    <label class="visually-hidden" for="flagReason{{ $internship->id }}">Reason</label>
                                    <input id="flagReason{{ $internship->id }}" type="text" name="reason" maxlength="500" class="form-control form-control-sm w-auto flex-grow-1" placeholder="Why it isn't suitable" required>
                                    <button type="submit" class="btn btn-sm btn-outline-primary text-nowrap">Mark not suitable</button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="card">
            <div class="card-header"><h2>Your students here</h2></div>
            @if($interns->isEmpty())
                <div class="card-body"><p class="sb-note">None of your students are placed here.</p></div>
            @else
                <ul class="sb-rows sb-rows--card">
                    @foreach($interns as $placement)
                        <li>
                            <div class="sb-grow">
                                <span class="sb-row-title"><a href="{{ route('coordinator.placements.show', $placement) }}">{{ $placement->student?->user?->name }}</a> · {{ $placement->internship?->title }}</span>
                                <span class="sb-row-sub">{{ $placement->statusLabel() }} · {{ number_format($placement->approvedHours()) }} / {{ $placement->required_hours }} hours</span>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="card">
            <div class="card-header"><h2>Communication log</h2></div>
            @if($messages->isEmpty())
                <div class="card-body"><p class="sb-note">No messages with this company yet.</p></div>
            @else
                <ul class="sb-rows sb-rows--card">
                    @foreach($messages as $message)
                        <li>
                            <div class="sb-grow">
                                <span class="sb-row-title">{{ $message->sender_id === auth()->id() ? 'You' : $employer->company_name }} <span class="text-muted fw-normal small">· {{ $message->created_at->format('M d, Y h:i A') }}</span></span>
                                <p class="small mt-1 mb-0">{{ \Illuminate\Support\Str::limit($message->body, 200) }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <aside class="sb-stack">
        <section class="card">
            <div class="card-header"><h2>Contact</h2></div>
            <div class="card-body">
                <dl class="sb-fields">
                    <div><dt>Contact person</dt><dd>{{ $employer->contact_person ?: '—' }}</dd></div>
                    <div><dt>Email</dt><dd>{{ $employer->user?->email ?: '—' }}</dd></div>
                    <div><dt>Phone</dt><dd>{{ $employer->user?->phone ?: '—' }}</dd></div>
                    <div><dt>Address</dt><dd>{{ $employer->address ?: '—' }}</dd></div>
                    <div><dt>Website</dt><dd>{{ $employer->website ?: '—' }}</dd></div>
                </dl>
            </div>
        </section>

        <form method="POST" action="{{ route('coordinator.companies.moa.update', $employer) }}" class="card" data-moa-form>
            @csrf
            @method('PUT')
            <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
                <h2>MOA with the school</h2>
                <x-status-badge :status="$employer->hasValidMoa() ? 'active' : 'neutral'" :label="$employer->moaStatus()" />
            </div>
            <div class="card-body sb-stack sb-stack--tight">
                <div>
                    <label class="form-label" for="moaSignedOn">Signed on</label>
                    <input id="moaSignedOn" type="date" name="moa_signed_on" class="form-control @error('moa_signed_on') is-invalid @enderror" value="{{ old('moa_signed_on', $employer->moa_signed_on?->toDateString()) }}">
                    @error('moa_signed_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div>
                    <label class="form-label" for="moaExpiresOn">Expires on</label>
                    <input id="moaExpiresOn" type="date" name="moa_expires_on" class="form-control @error('moa_expires_on') is-invalid @enderror" value="{{ old('moa_expires_on', $employer->moa_expires_on?->toDateString()) }}">
                    @error('moa_expires_on')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="form-text text-muted">Coordinators get a reminder 30 days before it expires.</small>
                </div>
                <div><button type="submit" class="btn btn-primary btn-sm">Save MOA dates</button></div>
            </div>
        </form>
    </aside>
</div>
@endsection
