<li class="skill-chip" data-skill-id="{{ $chip['id'] }}" data-level="{{ $chip['level'] }}">
    <button type="button" class="skill-chip-label" data-skill-level-toggle aria-expanded="false" aria-haspopup="true" title="Set your level">
        <span data-skill-name>{{ $chip['name'] }}</span>
        <span class="skill-chip-level" data-skill-level-label @if(! $chip['level']) hidden @endif>{{ $chip['level_label'] }}</span>
        <span class="skill-chip-certificate" data-skill-certificate title="A competency with this name has an uploaded certificate" @unless($chip['has_certificate']) hidden @endunless><i class="bi bi-file-earmark-check" aria-hidden="true"></i> Has certificate</span>
    </button>
    <form method="POST" action="{{ route('student.skills.destroy', $chip['id']) }}" data-skill-remove>
        @csrf
        @method('DELETE')
        <button type="submit" class="skill-chip-remove" aria-label="Remove {{ $chip['name'] }}" data-skill-remove-button>&times;</button>
    </form>
    <form class="skill-level-menu" method="POST" action="{{ route('student.skills.update', $chip['id']) }}" data-skill-level-form hidden>
        @csrf
        @method('PATCH')
        <fieldset>
            <legend class="skill-level-legend">Level</legend>
            @foreach([null => 'No level'] + collect($skillLevels)->mapWithKeys(fn ($level) => [$level->value => $level->label()])->all() as $value => $label)
                <label class="skill-level-option">
                    <input type="radio" name="level" value="{{ $value }}" @checked((string) $chip['level'] === (string) $value)>
                    {{ $label }}
                </label>
            @endforeach
        </fieldset>
        <button type="submit" class="btn-primary-profile skill-level-save" data-skill-level-save>Save level</button>
    </form>
</li>
