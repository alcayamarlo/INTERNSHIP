{{-- One label : value row (personal details, education, or an entry's detail rows). --}}
@php $row = $row ?? ['label' => '', 'value' => '']; @endphp
<div class="re-item re-row" data-item>
    <input type="text" class="form-control form-control-sm" data-key="label" value="{{ $row['label'] }}" maxlength="60" placeholder="{{ $labelPlaceholder ?? 'Label' }}" aria-label="Label">
    <input type="text" class="form-control form-control-sm" data-key="value" value="{{ $row['value'] }}" maxlength="300" placeholder="{{ $valuePlaceholder ?? 'Value' }}" aria-label="Value">
    @include('student.resume.partials.editor-controls', ['noun' => 'row'])
</div>
