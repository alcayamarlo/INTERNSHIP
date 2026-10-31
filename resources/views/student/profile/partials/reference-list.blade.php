@if($references->isEmpty())
    <p class="portfolio-description collection-empty">No references added yet.</p>
@else
    <ul class="collection-list" aria-label="Your character references">
        @foreach($references as $reference)
            <li class="collection-item">
                <div>
                    <span class="collection-item-title">{{ $reference->name }}</span>
                    <span class="collection-item-meta">{{ $reference->position }} · {{ $reference->organization }}</span>
                    <span class="collection-item-meta"><i class="bi bi-telephone" aria-hidden="true"></i> {{ $reference->contact_number }}</span>
                </div>
                <div class="portfolio-actions">
                    <button type="button" class="icon-action" title="Edit" aria-label="Edit {{ $reference->name }}"
                        data-collection-edit="references"
                        data-action="{{ route('student.references.update', $reference) }}"
                        data-values="{{ json_encode($reference->only(['name', 'position', 'organization', 'contact_number'])) }}"><i class="bi bi-pencil" aria-hidden="true"></i></button>
                    <form method="POST" action="{{ route('student.references.destroy', $reference) }}" data-collection-delete="references" data-confirm="Remove this reference?" data-confirm-title="Remove reference" data-confirm-label="Remove">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="icon-action" title="Remove" aria-label="Remove {{ $reference->name }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
                    </form>
                </div>
            </li>
        @endforeach
    </ul>
@endif
