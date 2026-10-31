{{-- Text-like input with label and inline error. Expects $name, $label; optional $type, $value, $required, $extra (raw attribute string), $hint. --}}
@php($fieldId = $id ?? $name)
<div class="{{ $wrapperClass ?? '' }}">
    <label class="form-label" for="{{ $fieldId }}">{{ $label }}@if($required ?? false)<span class="required-mark" aria-hidden="true">*</span>@endif</label>
    <input id="{{ $fieldId }}" type="{{ $type ?? 'text' }}" class="form-control @error($name) is-invalid @enderror" name="{{ $name }}" value="{{ old($name, $value ?? '') }}" @if($required ?? false) required @endif {!! $extra ?? '' !!}>
    @isset($hint)<small class="form-text text-muted">{{ $hint }}</small>@endisset
    @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
