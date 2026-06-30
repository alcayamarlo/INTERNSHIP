{{-- Dashboard Statistic Card Component --}}
<div class="card">
    <div class="card-body">
        <h6 class="card-title mb-3 text-muted small">{{ $title }}</h6>
        <div class="d-flex align-items-end gap-3">
            <div>
                <div class="h3 mb-0" @if($color) style="color: {{ $color }}" @endif>
                    {{ $value }}
                </div>
            </div>
            @if($showProgress ?? false)
                <div class="flex-grow-1">
                    <div class="progress" style="height: 4px;">
                        <div class="progress-bar" style="width: {{ $value }}%; background-color: {{ $color ?? '#2563EB' }}"></div>
                    </div>
                </div>
            @endif
        </div>
        @if($description)
            <small class="text-muted d-block mt-2">
                {!! $description !!}
            </small>
        @endif
    </div>
</div>
