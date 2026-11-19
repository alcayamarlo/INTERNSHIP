{{-- The left "Details" card. Props: user (the account), roleLine (text shown with the briefcase), note.
     Extra role-specific rows go in the slot, after the shared ones. --}}
@props(['user', 'roleLine' => null, 'note' => null, 'title' => 'Details'])
<x-profile.card :title="$title">
    @if($roleLine)<x-profile.detail-row icon="bi-briefcase">{{ $roleLine }}</x-profile.detail-row>@endif
    <x-profile.detail-row icon="bi-envelope">{{ $user->email ?: '—' }}</x-profile.detail-row>
    <x-profile.detail-row icon="bi-telephone">{{ $user->phone ?: '—' }}</x-profile.detail-row>
    {{ $slot }}
    <x-profile.detail-row icon="bi-check-circle">Account <b>Active</b></x-profile.detail-row>
    <x-profile.detail-row icon="bi-calendar-event">Member since {{ $user->created_at?->format('F j, Y') ?? '—' }}</x-profile.detail-row>
    <x-profile.detail-row icon="bi-clock-history">Last sign-in {{ $user->last_login_at ? $user->last_login_at->format('F j, Y \a\t g:i A') : 'not recorded' }}</x-profile.detail-row>
    @if($note)<p class="pf-foot">{{ $note }}</p>@endif
</x-profile.card>
