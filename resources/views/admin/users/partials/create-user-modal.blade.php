{{-- Create User pop-up (shared <x-modal> shell). Same fields, names, old() values and errors as the Create User page; posts to the same store route. --}}
@php
    $createRoles = \App\Enums\UserRole::assignable();
    $createInstitutions = \App\Models\Institution::query()->whereNotNull('verified_at')->orderBy('name')->get();
    // Coordinators only: departments that still need a coordinator.
    $createDepartments = \App\Models\Program::query()->whereDoesntHave('coordinator')->orderBy('code')->get();
    // The Edit User forms on this page also send "role", but they always send "is_active" too, so a create attempt is told apart by its absence.
    $reopenCreateUser = $errors->any() && old('role') !== null && old('is_active') === null;
@endphp
<x-modal id="create-user-modal" title="Create User" subtitle="Add a new account and choose its role." icon="bi-person-plus"
         :action="route('admin.users.store')" :reopen="$reopenCreateUser" submit="Create Account" submit-icon="bi-person-plus">
    <div class="sb-modal__grid">

        <p class="sb-modal__group">Account type</p>
        <div>
            <label for="cu-role" class="form-label">Account Type <span class="text-danger" aria-hidden="true">*</span></label>
            <select id="cu-role" name="role" class="form-select @if($reopenCreateUser) @error('role') is-invalid @enderror @endif" required>
                <option value="">Select account type</option>
                @foreach($createRoles as $role)
                    <option value="{{ $role->value }}" {{ old('role') === $role->value ? 'selected' : '' }}>{{ $role->label() }}</option>
                @endforeach
            </select>
            @if($reopenCreateUser) @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
        </div>

        <p class="sb-modal__group">Personal information</p>
        <div class="sm-3">
            <label for="cu-name" class="form-label">Full Name <span class="text-danger" aria-hidden="true">*</span></label>
            <input id="cu-name" name="name" type="text" value="{{ old('name') }}" class="form-control @if($reopenCreateUser) @error('name') is-invalid @enderror @endif" required>
            @if($reopenCreateUser) @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
        </div>
        <div class="sm-3">
            <label for="cu-phone" class="form-label">Phone Number <span class="text-muted fw-normal">(optional)</span></label>
            <input id="cu-phone" name="phone" type="text" value="{{ old('phone') }}" class="form-control @error('phone') is-invalid @enderror">
            @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div id="cu-company-field">
            <label for="cu-company" class="form-label">Company Name</label>
            <input id="cu-company" name="company_name" type="text" value="{{ old('company_name') }}" class="form-control @error('company_name') is-invalid @enderror">
            @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div id="cu-institution-field">
            <label for="cu-institution" class="form-label">Institution</label>
            <select id="cu-institution" name="institution_id" class="form-select @error('institution_id') is-invalid @enderror">
                <option value="">Select institution</option>
                @foreach($createInstitutions as $institution)
                    <option value="{{ $institution->id }}" {{ old('institution_id') == $institution->id ? 'selected' : '' }}>{{ $institution->name }}</option>
                @endforeach
            </select>
            @error('institution_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div id="cu-department-field" hidden>
            <label for="cu-department" class="form-label">Department <span class="text-danger" aria-hidden="true">*</span></label>
            <select id="cu-department" name="program_id" class="form-select @if($reopenCreateUser) @error('program_id') is-invalid @enderror @endif">
                <option value="">Select department</option>
                @foreach($createDepartments as $department)
                    <option value="{{ $department->id }}" @selected((string) old('program_id') === (string) $department->id)>{{ $department->display_name }}</option>
                @endforeach
            </select>
            <small class="sb-modal__hint d-block">{{ $createDepartments->isEmpty() ? 'Every department already has a coordinator.' : 'Only departments without a coordinator are listed. The coordinator cannot change it later.' }}</small>
            @if($reopenCreateUser) @error('program_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror @endif
        </div>
        <div id="cu-student-id-field">
            <label for="cu-student-id" class="form-label">Student ID Number</label>
            <input id="cu-student-id" name="student_id_number" type="text" value="{{ old('student_id_number') }}" placeholder="SCC-21-00013240" class="form-control @if($reopenCreateUser) @error('student_id_number') is-invalid @enderror @endif">
            <small class="sb-modal__hint d-block">This becomes the student's login username and can't be changed by the student afterward.</small>
            @if($reopenCreateUser) @error('student_id_number')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
        </div>

        <p class="sb-modal__group">Account details</p>
        <div>
            <label for="cu-email" class="form-label">Email Address <span class="text-danger" aria-hidden="true">*</span></label>
            <input id="cu-email" name="email" type="email" value="{{ old('email') }}" class="form-control @if($reopenCreateUser) @error('email') is-invalid @enderror @endif" required>
            @if($reopenCreateUser) @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
        </div>
        <div class="sm-3">
            <label for="cu-password" class="form-label">Temporary Password <span class="text-danger" aria-hidden="true">*</span></label>
            <div class="sb-modal__pw">
                <input id="cu-password" name="password" type="password" class="form-control @error('password') is-invalid @enderror" required>
                <button type="button" class="sb-modal__eye" data-pw-toggle="cu-password" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button>
            </div>
            @error('password')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        </div>
        <div class="sm-3">
            <label for="cu-password-confirmation" class="form-label">Confirm Password <span class="text-danger" aria-hidden="true">*</span></label>
            <div class="sb-modal__pw">
                <input id="cu-password-confirmation" name="password_confirmation" type="password" class="form-control" required>
                <button type="button" class="sb-modal__eye" data-pw-toggle="cu-password-confirmation" aria-label="Show password" aria-pressed="false"><i class="bi bi-eye" aria-hidden="true"></i></button>
            </div>
        </div>
    </div>
</x-modal>
<script>
(function () {
    var role = document.getElementById('cu-role');
    if (!role) return;
    var companyField = document.getElementById('cu-company-field');
    var institutionField = document.getElementById('cu-institution-field');
    var studentIdField = document.getElementById('cu-student-id-field');
    var company = document.getElementById('cu-company');
    var institution = document.getElementById('cu-institution');
    var studentId = document.getElementById('cu-student-id');
    var departmentField = document.getElementById('cu-department-field');
    var department = document.getElementById('cu-department');

    function updateFields() {
        var isEmployer = role.value === 'employer';
        var isStudent = role.value === 'student';
        var needsInstitution = role.value === 'coordinator' || isStudent;
        companyField.hidden = !isEmployer;
        institutionField.hidden = isEmployer;
        studentIdField.hidden = !isStudent;
        company.required = isEmployer;
        institution.required = needsInstitution;
        studentId.required = isStudent;
        departmentField.hidden = role.value !== 'coordinator';
        department.required = role.value === 'coordinator';
    }
    role.addEventListener('change', updateFields);
    updateFields();
})();
</script>
