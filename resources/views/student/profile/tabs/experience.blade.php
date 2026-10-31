<section class="profile-panel" id="panel-experience" role="tabpanel" aria-labelledby="tab-experience" tabindex="0" @if($activeTab !== 'experience') hidden @endif>
    <h2 class="panel-heading">Experience <span class="panel-tag">Optional</span></h2>
    <p class="portfolio-description">Add part-time jobs, volunteer work, or organization roles. You can skip this tab; it doesn't affect your profile completion.</p>

    <div data-collection-list="experience">
        @include('student.profile.partials.experience-list')
    </div>

    <form class="collection-form" method="POST" action="{{ route('student.experiences.store') }}" data-profile-collection="experience" data-store-url="{{ route('student.experiences.store') }}" novalidate>
        @csrf
        <input type="hidden" name="_method" value="POST" data-method-field>
        <h3 class="panel-subheading" data-form-title data-add-title="Add experience" data-edit-title="Edit experience">Add experience</h3>
        <div class="profile-field-grid">
            <div>
                <label class="form-label" for="experience_organization">Company / organization<span class="required-mark" aria-hidden="true">*</span></label>
                <input id="experience_organization" type="text" class="form-control @error('organization', 'experience') is-invalid @enderror" name="organization" value="{{ old('organization') }}" maxlength="150" required>
                @error('organization', 'experience')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="form-label" for="experience_position">Position<span class="required-mark" aria-hidden="true">*</span></label>
                <input id="experience_position" type="text" class="form-control @error('position', 'experience') is-invalid @enderror" name="position" value="{{ old('position') }}" maxlength="150" required>
                @error('position', 'experience')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="form-label" for="experience_start_date">From<span class="required-mark" aria-hidden="true">*</span></label>
                <input id="experience_start_date" type="date" class="form-control @error('start_date', 'experience') is-invalid @enderror" name="start_date" value="{{ old('start_date') }}" max="{{ now()->format('Y-m-d') }}" required>
                @error('start_date', 'experience')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="form-label" for="experience_end_date">To</label>
                <input id="experience_end_date" type="date" class="form-control @error('end_date', 'experience') is-invalid @enderror" name="end_date" value="{{ old('end_date') }}" max="{{ now()->format('Y-m-d') }}" data-end-date>
                @error('end_date', 'experience')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-check mt-2">
                    <input type="hidden" name="is_current" value="0">
                    <input class="form-check-input" type="checkbox" name="is_current" value="1" id="experience_is_current" data-is-current @checked(old('is_current'))>
                    <label class="form-check-label" for="experience_is_current">I currently work here (present)</label>
                </div>
            </div>
            <div class="field-wide">
                <label class="form-label" for="experience_description">Short description</label>
                <textarea id="experience_description" class="form-control @error('description', 'experience') is-invalid @enderror" name="description" rows="3" maxlength="1000" placeholder="What did you do there?">{{ old('description') }}</textarea>
                @error('description', 'experience')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="collection-actions">
            <button type="button" class="btn-profile" data-collection-cancel>Cancel</button>
            <button type="submit" class="btn-primary-profile">Save changes</button>
        </div>
    </form>
</section>
