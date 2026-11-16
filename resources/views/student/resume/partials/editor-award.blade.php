{{-- One honor / award. Awards that follow each other with the same group share one heading. --}}
@php $award = $award ?? ['group' => '', 'title' => '', 'meta' => '']; @endphp
<div class="sb-list-item sb-list-entry" data-item>
    <div class="sb-list-entry-head">
        <span class="sb-list-entry-label">Award</span>
        @include('student.resume.partials.editor-controls', ['noun' => 'award'])
    </div>
    <div class="sb-field-grid">
        <label class="sb-field">
            <span>Award</span>
            <textarea class="form-control form-control-sm" data-key="title" rows="2" maxlength="1000" placeholder="e.g. Dean's Lister">{{ $award['title'] }}</textarea>
        </label>
        <label class="sb-field">
            <span>Group <small>(optional)</small></span>
            <input type="text" class="form-control form-control-sm" data-key="group" value="{{ $award['group'] }}" maxlength="100" placeholder="e.g. College">
        </label>
        <label class="sb-field">
            <span>Details <small>(optional)</small></span>
            <input type="text" class="form-control form-control-sm" data-key="meta" value="{{ $award['meta'] }}" maxlength="150" placeholder="e.g. 2025 · St. Cecilia's College">
        </label>
    </div>
</div>
