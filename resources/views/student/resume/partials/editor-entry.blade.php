{{-- One entry (experience, project, certificate, or reference). Expects $section and $config; $entry is
     empty for the "add" template. Fields the template does not print for this section are not shown. --}}
@php $entry = $entry ?? ['title' => '', 'rows' => [], 'skills' => [], 'description' => '']; @endphp
<div class="re-item re-entry" data-item>
    <div class="re-entry-head">
        <span class="re-entry-label">{{ ucfirst($config['noun']) }}</span>
        @include('student.resume.partials.editor-controls', ['noun' => $config['noun']])
    </div>

    <label class="re-field">
        <span>{{ $config['title'] }} <b class="re-req" aria-hidden="true">*</b></span>
        <input type="text" class="form-control form-control-sm" data-key="title" value="{{ $entry['title'] }}" maxlength="150" placeholder="{{ $config['titlePlaceholder'] }}">
    </label>

    <div class="re-field">
        <span>Details</span>
        <div class="re-list re-list--rows" data-list="rows">
            @foreach($entry['rows'] as $row)
                @include('student.resume.partials.editor-row', ['row' => $row, 'labelPlaceholder' => $config['rowLabel'], 'valuePlaceholder' => $config['rowValue']])
            @endforeach
        </div>
        {{-- data-add names the <template> to copy; the new item goes into the list just before the button. --}}
        <button type="button" class="re-add re-add--small" data-add="row-{{ $section }}"><i class="bi bi-plus-lg" aria-hidden="true"></i> Add detail</button>
    </div>

    @if($config['skills'])
        <label class="re-field">
            <span>Skills learned <small>(comma separated)</small></span>
            <input type="text" class="form-control form-control-sm" data-key="skills" data-split="," value="{{ implode(', ', $entry['skills']) }}" placeholder="e.g. Troubleshooting, Network cabling">
        </label>
    @endif

    @if($config['description'])
        <label class="re-field">
            <span>Description <small>(optional)</small></span>
            <textarea class="form-control form-control-sm" data-key="description" rows="3" maxlength="2000" placeholder="{{ $config['descriptionPlaceholder'] }}">{{ $entry['description'] }}</textarea>
        </label>
    @endif
</div>
