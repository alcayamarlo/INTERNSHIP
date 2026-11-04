{{-- Opens the shared "Report" dialog (partials/report-modal, included in the layout). --}}
@auth
    @unless(auth()->user()->isRole(\App\Enums\UserRole::Admin))
        <button type="button" class="btn btn-sm btn-outline-secondary {{ $class ?? '' }}" data-report-type="{{ $type }}" data-report-id="{{ $id }}" data-report-label="{{ $label }}">
            <i class="bi bi-flag" aria-hidden="true"></i> Report
        </button>
    @endunless
@endauth
