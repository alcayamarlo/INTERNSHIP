{{-- Move up / move down / remove for one repeatable editor item. Expects $noun (e.g. "row"). --}}
<div class="re-item-controls">
    <button type="button" class="re-icon-btn" data-move="-1" aria-label="Move {{ $noun }} up" title="Move up"><i class="bi bi-arrow-up" aria-hidden="true"></i></button>
    <button type="button" class="re-icon-btn" data-move="1" aria-label="Move {{ $noun }} down" title="Move down"><i class="bi bi-arrow-down" aria-hidden="true"></i></button>
    <button type="button" class="re-icon-btn re-icon-btn--danger" data-remove aria-label="Remove {{ $noun }}" title="Remove"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
</div>
