{{-- Latest published announcements for the signed-in user's role (or everyone). --}}
@props(['limit' => 3])

@php
    $role = auth()->user()?->role?->value;
    $announcements = \App\Models\Announcement::query()
        ->whereNotNull('published_at')
        ->where(fn ($query) => $query->whereNull('target_role')->orWhere('target_role', $role))
        ->latest('published_at')
        ->take($limit)
        ->get();
@endphp

@if($announcements->isNotEmpty())
    <div class="card mb-4" data-dashboard-announcements>
        <div class="card-header"><h2><i class="bi bi-megaphone" aria-hidden="true"></i> Announcements</h2></div>
        <ul class="sb-list">
            @foreach($announcements as $announcement)
                <li class="sb-list-item">
                    <div>
                        <p class="sb-list-title"><strong>{{ $announcement->title }}</strong></p>
                        <div class="small text-muted">{{ \Illuminate\Support\Str::limit($announcement->content, 220) }}</div>
                    </div>
                    <time class="sb-list-meta" datetime="{{ $announcement->published_at->toIso8601String() }}">{{ $announcement->published_at->diffForHumans() }}</time>
                </li>
            @endforeach
        </ul>
    </div>
@endif
