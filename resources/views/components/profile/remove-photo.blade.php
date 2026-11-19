{{-- "Remove photo" button under the upload button. Props: action (DELETE route), hasPhoto, label.
     Disabled when there is no photo to remove. --}}
@props(['action', 'hasPhoto' => false, 'label' => 'Remove photo'])
<form method="POST" action="{{ $action }}" data-remove-photo-form>
    @csrf
    @method('DELETE')
    <button type="submit" class="pf-btn pf-btn--ghost" data-remove-photo data-confirm="Remove your profile picture?" data-confirm-title="Remove profile picture" data-confirm-label="Remove" @disabled(! $hasPhoto)><i class="bi bi-trash" aria-hidden="true"></i> {{ $label }}</button>
</form>
