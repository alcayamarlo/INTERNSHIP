<section class="profile-panel" id="panel-personal" role="tabpanel" aria-labelledby="tab-personal" tabindex="0" @if($activeTab !== 'personal') hidden @endif>
    {{-- Saved on its own (not part of the Personal form), so it never touches the unsaved-changes bar. --}}
    <section class="photo-field" aria-labelledby="profilePhotoHeading">
        <h2 class="panel-heading" id="profilePhotoHeading">Profile photo <span class="panel-tag">Optional</span></h2>
        <div class="photo-field-row">
            <div class="avatar photo-field-preview" data-avatar>
                <x-avatar :avatar="$profileAvatar" :alt="$user->name" />
            </div>
            <div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn-profile" data-photo-pick>{{ $profileAvatar['url'] ? 'Change photo' : 'Upload photo' }}</button>
                    <button type="button" class="btn-profile" data-photo-remove @unless($profileAvatar['url']) hidden @endunless>Remove photo</button>
                </div>
                <p class="portfolio-description mt-2 mb-0">JPG, PNG, or WebP, up to 5 MB. It is cropped to a square from the center.</p>
                <div class="invalid-feedback d-block" data-photo-error role="alert">@error('profile_picture'){{ $message }}@enderror</div>
            </div>
        </div>
    </section>
    <hr class="section-divider">

    <form method="POST" action="{{ route('student.profile.update') }}" data-profile-section="personal" novalidate>
        @csrf
        @method('PUT')
        <input type="hidden" name="profile_section" value="personal">
        <h2 class="panel-heading">Personal information</h2>
        <div class="profile-field-grid">
            @include('student.profile.partials.field', ['name' => 'first_name', 'label' => 'First name', 'required' => true, 'value' => $student->first_name ?: (explode(' ', $user->name)[0] ?? '')])
            @include('student.profile.partials.field', ['name' => 'middle_name', 'label' => 'Middle name', 'value' => $student->middle_name])
            @include('student.profile.partials.field', ['name' => 'last_name', 'label' => 'Last name', 'required' => true, 'value' => $student->last_name ?: str($user->name)->afterLast(' ')->toString()])
            <div>
                <label class="form-label" for="suffix">Suffix</label>
                @php($suffixValue = old('suffix', $student->suffix ?? ''))
                <select id="suffix" class="form-select @error('suffix') is-invalid @enderror" name="suffix">
                    <option value="" {{ $suffixValue === '' ? 'selected' : '' }}>N/A</option>
                    @foreach(['Jr.', 'Sr.', 'II', 'III', 'IV', 'V'] as $suffix)
                        <option value="{{ $suffix }}" {{ $suffixValue === $suffix ? 'selected' : '' }}>{{ $suffix }}</option>
                    @endforeach
                </select>
                @error('suffix')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div>
                <label class="form-label" for="gender">Gender<span class="required-mark" aria-hidden="true">*</span></label>
                <select id="gender" class="form-select @error('gender') is-invalid @enderror" name="gender" required>
                    <option value="">Select...</option>
                    @foreach(['Male', 'Female', 'Other', 'Prefer Not to Say'] as $gender)
                        <option value="{{ $gender }}" {{ old('gender', $student->gender) === $gender ? 'selected' : '' }}>{{ $gender }}</option>
                    @endforeach
                </select>
                @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            @include('student.profile.partials.field', ['name' => 'date_of_birth', 'label' => 'Date of birth', 'type' => 'date', 'required' => true, 'value' => $student->date_of_birth?->format('Y-m-d'), 'extra' => 'max="'.now()->subDay()->format('Y-m-d').'"'])
            @include('student.profile.partials.field', ['name' => 'place_of_birth', 'label' => 'Place of birth', 'value' => $student->place_of_birth])
            @include('student.profile.partials.field', ['name' => 'citizenship', 'label' => 'Citizenship', 'value' => $student->citizenship, 'extra' => 'placeholder="Filipino"'])
            @include('student.profile.partials.field', ['name' => 'languages', 'label' => 'Languages / dialects spoken', 'value' => $student->languages, 'wrapperClass' => 'field-wide', 'extra' => 'placeholder="English, Filipino, Cebuano"', 'hint' => 'Separate with commas.'])
        </div>

        <hr class="section-divider">
        <h2 class="panel-heading">Sensitive details <span class="panel-tag">Optional</span></h2>
        <p class="portfolio-description">These are hidden from companies by default. Turn on "Show to companies" only for the details you want to share.</p>
        <div class="profile-field-grid">
            <div>
                <label class="form-label" for="civil_status">Civil status</label>
                <select id="civil_status" class="form-select @error('civil_status') is-invalid @enderror" name="civil_status">
                    <option value="">Select...</option>
                    @foreach($civilStatuses as $civilStatus)
                        <option value="{{ $civilStatus }}" @selected(old('civil_status', $student->civil_status) === $civilStatus)>{{ $civilStatus }}</option>
                    @endforeach
                </select>
                @error('civil_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                @include('student.profile.partials.visibility-toggle', ['name' => 'show_civil_status'])
            </div>
            <div>
                @include('student.profile.partials.field', ['name' => 'religion', 'label' => 'Religion', 'value' => $student->religion])
                @include('student.profile.partials.visibility-toggle', ['name' => 'show_religion'])
            </div>
            <div>
                @include('student.profile.partials.field', ['name' => 'height_cm', 'label' => 'Height (cm)', 'type' => 'number', 'value' => $student->height_cm, 'extra' => 'step="0.1" min="50" max="250" inputmode="decimal"'])
                @include('student.profile.partials.visibility-toggle', ['name' => 'show_height'])
            </div>
            <div>
                @include('student.profile.partials.field', ['name' => 'weight_kg', 'label' => 'Weight (kg)', 'type' => 'number', 'value' => $student->weight_kg, 'extra' => 'step="0.1" min="20" max="300" inputmode="decimal"'])
                @include('student.profile.partials.visibility-toggle', ['name' => 'show_weight'])
            </div>
        </div>
        @include('student.profile.partials.save-bar')
    </form>

    <hr class="section-divider">
    <section aria-labelledby="discoverableHeading">
        <h2 class="panel-heading" id="discoverableHeading">Company search</h2>
        <form method="POST" action="{{ route('student.profile.discoverable') }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="discoverable" value="0">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch" id="discoverable" name="discoverable" value="1"
                    @checked($student->isDiscoverable()) @disabled(! $student->hasPrivacyConsent()) onchange="this.form.requestSubmit()">
                <label class="form-check-label" for="discoverable">Allow companies to find my profile</label>
            </div>
            <p class="portfolio-description mt-1 mb-0">
                @if($student->hasPrivacyConsent())
                    Companies can see your course, year level, skills, certificates, awards, and experience, and invite you to apply. Your email, phone, and address stay hidden until you accept an invitation or apply.
                @else
                    Agree to the privacy statement above before companies can find your profile.
                @endif
            </p>
            <noscript><button type="submit" class="btn-profile mt-2">Save</button></noscript>
        </form>
    </section>
</section>
