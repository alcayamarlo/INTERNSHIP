{{-- One skill. --}}
<div class="sb-list-item sb-list-value" data-item>
    <input type="text" class="form-control form-control-sm" data-key="value" value="{{ $value ?? '' }}" maxlength="80" placeholder="e.g. Customer service" aria-label="Skill">
    @include('student.resume.partials.editor-controls', ['noun' => 'skill'])
</div>
