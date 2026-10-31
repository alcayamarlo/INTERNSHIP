<section class="profile-panel" id="panel-references" role="tabpanel" aria-labelledby="tab-references" tabindex="0" @if($activeTab !== 'references') hidden @endif>
    <h2 class="panel-heading">Character reference</h2>
    <div class="alert alert-info reference-prompt" data-reference-prompt @if($references->isNotEmpty()) hidden @endif>
        <i class="bi bi-lightbulb" aria-hidden="true"></i> Add at least 1 reference (teacher or adviser) to improve your profile.
    </div>
    <p class="portfolio-description">List up to {{ $maxReferences }} people who can vouch for you, such as an instructor, adviser, or dean. Some internships require at least one reference before you can apply.</p>

    <div data-collection-list="references">
        @include('student.profile.partials.reference-list')
    </div>

    <form class="collection-form" method="POST" action="{{ route('student.references.store') }}" data-profile-collection="references" data-store-url="{{ route('student.references.store') }}" data-max="{{ $maxReferences }}" @if($references->count() >= $maxReferences) hidden @endif novalidate>
        @csrf
        <input type="hidden" name="_method" value="POST" data-method-field>
        <h3 class="panel-subheading" data-form-title data-add-title="Add a reference" data-edit-title="Edit reference">Add a reference</h3>
        <div class="profile-field-grid">
            <div>
                <label class="form-label" for="reference_name">Name<span class="required-mark" aria-hidden="true">*</span></label>
                <input id="reference_name" type="text" class="form-control @error('name', 'references') is-invalid @enderror" name="name" value="{{ old('name') }}" maxlength="150" required>
                @error('name', 'references')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="form-label" for="reference_position">Position<span class="required-mark" aria-hidden="true">*</span></label>
                <input id="reference_position" type="text" class="form-control @error('position', 'references') is-invalid @enderror" name="position" value="{{ old('position') }}" maxlength="150" list="referencePositions" placeholder="Instructor, Adviser, Dean" required>
                <datalist id="referencePositions">
                    <option value="Instructor"></option>
                    <option value="Adviser"></option>
                    <option value="Dean"></option>
                    <option value="Program Head"></option>
                    <option value="Supervisor"></option>
                </datalist>
                @error('position', 'references')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="form-label" for="reference_organization">Company / school<span class="required-mark" aria-hidden="true">*</span></label>
                <input id="reference_organization" type="text" class="form-control @error('organization', 'references') is-invalid @enderror" name="organization" value="{{ old('organization') }}" maxlength="150" required>
                @error('organization', 'references')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="form-label" for="reference_contact_number">Contact number<span class="required-mark" aria-hidden="true">*</span></label>
                <input id="reference_contact_number" type="tel" class="form-control @error('contact_number', 'references') is-invalid @enderror" name="contact_number" value="{{ old('contact_number') }}" maxlength="30" required>
                @error('contact_number', 'references')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
        <div class="collection-actions">
            <button type="button" class="btn-profile" data-collection-cancel>Cancel</button>
            <button type="submit" class="btn-primary-profile">Save changes</button>
        </div>
    </form>
    <p class="portfolio-description" data-reference-limit @if($references->count() < $maxReferences) hidden @endif>You've listed {{ $maxReferences }} references, the maximum. Remove one to add another.</p>
</section>
