{{-- One honor / award. Awards that follow each other with the same group share one heading. --}}
@php $award = $award ?? ['group' => '', 'title' => '', 'meta' => '']; @endphp
<div class="re-item re-entry" data-item>
    <div class="re-entry-head">
        <span class="re-entry-label">Award</span>
        @include('student.resume.partials.editor-controls', ['noun' => 'award'])
    </div>
    <div class="re-grid">
        <label class="re-field">
            <span>Award</span>
            <textarea class="form-control form-control-sm" data-key="title" rows="2" maxlength="1000" placeholder="e.g. Dean's Lister">{{ $award['title'] }}</textarea>
        </label>
        <label class="re-field">
            <span>Group <small>(optional)</small></span>
            <input type="text" class="form-control form-control-sm" data-key="group" value="{{ $award['group'] }}" maxlength="100" placeholder="e.g. College">
        </label>
        <label class="re-field">
            <span>Details <small>(optional)</small></span>
            <input type="text" class="form-control form-control-sm" data-key="meta" value="{{ $award['meta'] }}" maxlength="150" placeholder="e.g. 2025 · St. Cecilia's College">
        </label>
    </div>
</div>
