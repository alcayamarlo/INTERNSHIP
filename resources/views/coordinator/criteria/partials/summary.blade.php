{{-- One line describing a criteria version. --}}
@php
    $parts = array_filter([
        $criterion->require_fourth_year ? '4th Year standing' : null,
        $criterion->require_major_subjects_passed ? 'major subjects passed' : null,
        $criterion->max_gwa !== null ? 'GWA '.number_format((float) $criterion->max_gwa, 2).' or better' : null,
        $criterion->required_hours ? number_format($criterion->required_hours).' OJT hours' : null,
        $criterion->requiredDocumentIds() ? count($criterion->requiredDocumentIds()).' required '.\Illuminate\Support\Str::plural('document', count($criterion->requiredDocumentIds())) : null,
    ]);
@endphp
<span>{{ $parts ? implode(' · ', $parts) : 'No requirements' }}</span>
