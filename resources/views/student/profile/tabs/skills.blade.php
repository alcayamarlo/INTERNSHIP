<section class="profile-panel" id="panel-skills" role="tabpanel" aria-labelledby="tab-skills" tabindex="0" @if($activeTab !== 'skills') hidden @endif>
    <h2 class="panel-heading">Skills</h2>
    <p class="portfolio-description">List anything you can do: technical skills (Python, HTML), soft skills (communication), or talents (singing, dancing). Type a skill and press Enter or click Add, then click a skill to set your level. Add at least {{ \App\Models\Student::MIN_COMPLETION_SKILLS }} to complete this tab.</p>
    <p class="portfolio-description"><i class="bi bi-info-circle" aria-hidden="true"></i> Skills are self-declared and don't need a certificate. They don't affect your internship match score; that comes from your <a href="{{ route('student.competencies.index') }}">Competencies</a>.</p>
    <form class="skill-form" method="POST" action="{{ route('student.skills.store') }}" data-skill-form
        data-suggest-url="{{ route('student.skills.suggest') }}" data-max="{{ $maxSkills }}" novalidate>
        @csrf
        <label class="form-label" for="skillInput">Add a skill</label>
        <div class="skill-input-row">
            <div class="skill-combobox">
                <input id="skillInput" name="name" type="text" class="form-control @error('name', 'skills') is-invalid @enderror" maxlength="60" autocomplete="off"
                    placeholder="e.g. Python, Singing, Teamwork" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="skillSuggestions" aria-describedby="skillCounter skillError"
                    @disabled($studentSkills->count() >= $maxSkills)>
                <ul class="skill-suggestions" id="skillSuggestions" role="listbox" aria-label="Skill suggestions" hidden></ul>
            </div>
            <button type="submit" class="btn-primary-profile" data-skill-add @disabled($studentSkills->count() >= $maxSkills)><i class="bi bi-plus-lg" aria-hidden="true"></i> Add</button>
        </div>
        <div class="invalid-feedback d-block" id="skillError" role="alert">@error('name', 'skills'){{ $message }}@enderror</div>
        <p class="skill-counter" id="skillCounter"><span data-skill-count>{{ $studentSkills->count() }}</span> / {{ $maxSkills }} skills
            <span class="skill-limit-note" data-skill-limit @if($studentSkills->count() < $maxSkills) hidden @endif>· You've reached the limit. Remove a skill to add another.</span>
        </p>
    </form>

    <ul class="skill-chips" data-skill-list aria-label="Your skills">
        @foreach($studentSkills as $studentSkill)
            @include('student.profile.partials.skill-chip', ['chip' => $studentSkill->toChip($certifiedSkillNames)])
        @endforeach
    </ul>
    <p class="portfolio-description" data-skill-empty @if($studentSkills->isNotEmpty()) hidden @endif>No skills yet. Add your first one above.</p>

    <template id="skillChipTemplate">
        @include('student.profile.partials.skill-chip', ['chip' => ['id' => '__ID__', 'name' => '', 'level' => null, 'level_label' => null, 'has_certificate' => false]])
    </template>
</section>
