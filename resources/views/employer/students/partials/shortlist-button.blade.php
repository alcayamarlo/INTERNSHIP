{{-- Private shortlist toggle; the student is never told. --}}
@php($isShortlisted = in_array($student['student_id'], $shortlistedIds, true))
<form method="POST" action="{{ $isShortlisted ? route('employer.students.shortlist.destroy', $student['student_id']) : route('employer.students.shortlist.store', $student['student_id']) }}" class="d-inline" data-shortlist-form>
    @csrf
    @if($isShortlisted) @method('DELETE') @endif
    <button type="submit" class="btn btn-sm {{ $isShortlisted ? 'btn-warning' : 'btn-outline-warning' }}" aria-pressed="{{ $isShortlisted ? 'true' : 'false' }}"
        aria-label="{{ $isShortlisted ? 'Remove '.$student['name'].' from shortlist' : 'Shortlist '.$student['name'] }}">
        <i class="bi {{ $isShortlisted ? 'bi-star-fill' : 'bi-star' }}" aria-hidden="true"></i> {{ $isShortlisted ? 'Shortlisted' : 'Shortlist' }}
    </button>
</form>
