<section class="profile-panel" id="panel-personal" role="tabpanel" aria-labelledby="tab-personal" tabindex="0" @if($activeTab !== 'personal') hidden @endif>
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
        <div class="profile-field-grid">
            <div class="field-wide">
                <label class="form-label" for="about_me">About me</label>
                <textarea id="about_me" class="form-control @error('about_me') is-invalid @enderror" name="about_me" rows="4" maxlength="1000" placeholder="A short introduction: who you are, what you are good at and what you want to learn">{{ old('about_me', $student->about_me) }}</textarea>
                <div class="form-text">Shown to companies and used as the summary on your resume.</div>
                @error('about_me')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
