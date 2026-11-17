{{-- One education entry (Appendix H): level, years, school, and the course for college. --}}
@php $entry = $entry ?? ['level' => '', 'years' => '', 'school' => '', 'detail' => '']; @endphp
<div class="sb-list-item sb-list-entry" data-item>
    <div class="sb-field-grid sb-field-grid--2">
        <label class="sb-field">
            <span>Level <b class="sb-req" aria-hidden="true">*</b></span>
            <input type="text" class="form-control form-control-sm" data-key="level" value="{{ $entry['level'] }}" maxlength="60" placeholder="e.g. Senior High School" list="reEducationLevels">
        </label>
        <label class="sb-field">
            <span>Years</span>
            <input type="text" class="form-control form-control-sm" data-key="years" value="{{ $entry['years'] }}" maxlength="40" placeholder="e.g. 2020-2022">
        </label>
        <label class="sb-field">
            <span>School</span>
            <input type="text" class="form-control form-control-sm" data-key="school" value="{{ $entry['school'] }}" maxlength="150" placeholder="e.g. St. Cecilia's College Cebu Inc.">
        </label>
        <label class="sb-field">
            <span>Course or strand</span>
            <input type="text" class="form-control form-control-sm" data-key="detail" value="{{ $entry['detail'] }}" maxlength="150" placeholder="e.g. BS Information Technology">
        </label>
    </div>
    @include('student.resume.partials.editor-controls', ['noun' => 'education entry'])
</div>
