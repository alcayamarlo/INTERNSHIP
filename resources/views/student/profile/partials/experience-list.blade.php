@if($experiences->isEmpty())
    <p class="portfolio-description collection-empty">No experience added yet.</p>
@else
    <ul class="collection-list" aria-label="Your experience">
        @foreach($experiences as $experience)
            <li class="collection-item">
                <div>
                    <span class="collection-item-title">{{ $experience->position }}</span>
                    <span class="collection-item-meta">{{ $experience->organization }} · {{ $experience->periodLabel() }}</span>
                    @if($experience->description)
                        <p class="collection-item-body">{{ $experience->description }}</p>
                    @endif
                </div>
                <div class="portfolio-actions">
                    <button type="button" class="icon-action" title="Edit" aria-label="Edit {{ $experience->position }}"
                        data-collection-edit="experience"
                        data-action="{{ route('student.experiences.update', $experience) }}"
                        data-values="{{ json_encode($experience->formValues()) }}"><i class="bi bi-pencil" aria-hidden="true"></i></button>
                    <form method="POST" action="{{ route('student.experiences.destroy', $experience) }}" data-collection-delete="experience" data-confirm="Remove this experience?" data-confirm-title="Remove experience" data-confirm-label="Remove">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="icon-action" title="Remove" aria-label="Remove {{ $experience->position }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
                    </form>
                </div>
            </li>
        @endforeach
    </ul>
@endif
