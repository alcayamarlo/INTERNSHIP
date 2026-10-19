{{-- Dashboard quick actions panel. Put list-group-item-action links in the slot. --}}
@props(['title', 'icon' => 'bi-lightning-charge'])

<div class="card">
    <div class="card-header">
        <h2>
            <i class="bi {{ \Illuminate\Support\Str::replaceFirst('bi ', '', $icon) }}" aria-hidden="true"></i>
            {{ $title }}
        </h2>
    </div>
    <div class="list-group list-group-flush">
        {{ $slot }}
    </div>
</div>
