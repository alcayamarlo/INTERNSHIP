@if($awardGroups === [])
    <p class="portfolio-description collection-empty">No honors or awards added yet.</p>
@else
    @foreach($awardGroups as $group)
        <h3 class="panel-subheading">{{ $group['label'] }}</h3>
        <ul class="collection-list" aria-label="{{ $group['label'] }} awards">
            @foreach($group['awards'] as $award)
                <li class="collection-item">
                    <div>
                        <span class="collection-item-title">{!! nl2br(e($award->title)) !!}</span>
                        @if($award->metaLine() !== '')<span class="collection-item-meta">{{ $award->metaLine() }}</span>@endif
                        @if($group['level'] === \App\Enums\AwardLevel::Other->value)
                            <span class="collection-item-meta">Copied from your old Honors / awards text. Edit it or add each award on its own, then remove this one.</span>
                        @endif
                    </div>
                    <div class="portfolio-actions">
                        <button type="button" class="icon-action" title="Edit" aria-label="Edit {{ Str::limit($award->title, 60) }}"
                            data-collection-edit="awards"
                            data-action="{{ route('student.awards.update', $award) }}"
                            data-values="{{ json_encode($award->formValues()) }}"><i class="bi bi-pencil" aria-hidden="true"></i></button>
                        <form method="POST" action="{{ route('student.awards.destroy', $award) }}" data-collection-delete="awards" data-confirm="Remove this award?" data-confirm-title="Remove award" data-confirm-label="Remove">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="icon-action" title="Remove" aria-label="Remove {{ Str::limit($award->title, 60) }}"><i class="bi bi-trash" aria-hidden="true"></i></button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    @endforeach
@endif
