<section class="profile-panel" id="panel-contact" role="tabpanel" aria-labelledby="tab-contact" tabindex="0" @if($activeTab !== 'contact') hidden @endif>
    <form method="POST" action="{{ route('student.profile.update') }}" data-profile-section="contact" novalidate>
        @csrf
        @method('PUT')
        <input type="hidden" name="profile_section" value="contact">
        <h2 class="panel-heading">Contact information</h2>
        <p class="portfolio-description"><i class="bi bi-eye-slash" aria-hidden="true"></i> Your mobile number, email, and addresses are hidden from companies until you apply to one of their internships.</p>
        <div class="profile-field-grid">
            @include('student.profile.partials.field', ['name' => 'phone', 'label' => 'Mobile number', 'type' => 'tel', 'required' => true, 'value' => $user->phone, 'extra' => 'placeholder="+63 900 000 0000" autocomplete="tel"'])
            @include('student.profile.partials.field', ['name' => 'email', 'label' => 'Email address', 'type' => 'email', 'required' => true, 'value' => $user->email, 'extra' => 'autocomplete="email"'])
        </div>

        <h3 class="panel-subheading">City address</h3>
        <div class="profile-field-grid">
            @include('student.profile.partials.field', ['name' => 'address', 'label' => 'Street / barangay', 'required' => true, 'value' => $student->address, 'wrapperClass' => 'field-wide'])
            @include('student.profile.partials.field', ['name' => 'city', 'label' => 'City', 'required' => true, 'value' => $student->city])
            @include('student.profile.partials.field', ['name' => 'province', 'label' => 'Province', 'required' => true, 'value' => $student->province])
            @include('student.profile.partials.field', ['name' => 'zip_code', 'label' => 'ZIP code', 'required' => true, 'value' => $student->zip_code])
        </div>

        <h3 class="panel-subheading">Provincial address <span class="panel-tag">Optional</span></h3>
        <div class="profile-field-grid">
            @include('student.profile.partials.field', ['name' => 'provincial_address', 'label' => 'Full provincial address', 'value' => $student->provincial_address, 'wrapperClass' => 'field-wide'])
        </div>

        <hr class="section-divider">
        <h2 class="panel-heading">Emergency contact</h2>
        <div class="profile-field-grid">
            @include('student.profile.partials.field', ['name' => 'emergency_contact_name', 'label' => 'Name', 'required' => true, 'value' => $student->emergency_contact_name])
            @include('student.profile.partials.field', ['name' => 'emergency_contact_relationship', 'label' => 'Relationship', 'required' => true, 'value' => $student->emergency_contact_relationship, 'extra' => 'placeholder="Mother"'])
            @include('student.profile.partials.field', ['name' => 'emergency_contact_phone', 'label' => 'Phone number', 'type' => 'tel', 'required' => true, 'value' => $student->emergency_contact_phone])
            @include('student.profile.partials.field', ['name' => 'emergency_contact_address', 'label' => 'Address', 'required' => true, 'value' => $student->emergency_contact_address, 'wrapperClass' => 'field-wide'])
        </div>

        <hr class="section-divider">
        <h2 class="panel-heading">Social links <span class="panel-tag">Optional</span></h2>
        <div class="profile-field-grid">
            @include('student.profile.partials.field', ['name' => 'linkedin_url', 'label' => 'LinkedIn', 'type' => 'url', 'value' => $student->linkedin_url, 'extra' => 'placeholder="https://www.linkedin.com/in/your-name"'])
            @include('student.profile.partials.field', ['name' => 'facebook_url', 'label' => 'Facebook', 'type' => 'url', 'value' => $student->facebook_url, 'extra' => 'placeholder="https://www.facebook.com/your-name"'])
            @include('student.profile.partials.field', ['name' => 'github_url', 'label' => 'GitHub', 'type' => 'url', 'value' => $student->github_url, 'extra' => 'placeholder="https://github.com/your-name"'])
        </div>
        @include('student.profile.partials.save-bar')
    </form>
</section>
