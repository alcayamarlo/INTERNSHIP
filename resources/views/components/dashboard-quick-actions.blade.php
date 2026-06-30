{{-- Dashboard Quick Actions Component --}}
<div class="card">
    <div class="card-header bg-white">
        <h5 class="mb-0">
            <i class="{{ $icon ?? 'bi bi-lightning-charge' }}"></i>
            {{ $title }}
        </h5>
    </div>
    <div class="list-group list-group-flush">
        {{ $slot }}
    </div>
</div>
