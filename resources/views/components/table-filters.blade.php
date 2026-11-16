{{--
    Shared filter bar for student lists (Applicants, Sent Invitations).
    Props:
      action   - URL the GET form submits to (the list page itself)
      filters  - App\Support\StudentListFilters
      options  - $filters->options(...): internships, statuses, programs
      showing  - number of rows matching the filters (paginator total)
      total    - number of rows without filters
      target   - CSS selector of the results region that public/js/table-filters.js swaps in place
    Works without JavaScript as a normal GET form; with it, results update as you type or pick.
--}}
@props(['action', 'filters', 'options', 'showing', 'total', 'target', 'label' => 'Filter list'])

@php($v = $filters->values)
<form method="GET" action="{{ $action }}" class="card sb-filterbar sb-live-filters" role="search" aria-label="{{ $label }}"
      data-table-filters data-target="{{ $target }}">
  <div class="sb-filter-row">
    <div class="sb-filter-field">
        <label class="form-label" for="tfName">Name</label>
        <input id="tfName" type="search" name="q" value="{{ $v['q'] }}" class="form-control" maxlength="100"
               placeholder="Search by name" autocomplete="off">
    </div>
    <div class="sb-filter-field">
        <label class="form-label" for="tfInternship">Internship position</label>
        <select id="tfInternship" name="internship" class="form-select">
            <option value="">All positions</option>
            @foreach($options['internships'] as $id => $title)
                <option value="{{ $id }}" @selected($v['internship'] === (int) $id)>{{ $title }}</option>
            @endforeach
        </select>
    </div>
    <div class="sb-filter-field">
        <label class="form-label" for="tfStatus">Status</label>
        <select id="tfStatus" name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach($options['statuses'] as $key => $statusLabel)
                <option value="{{ $key }}" @selected($v['status'] === $key)>{{ $statusLabel }}</option>
            @endforeach
        </select>
    </div>
    <div class="sb-filter-field">
        <label class="form-label" for="tfProgram">Program</label>
        <select id="tfProgram" name="program" class="form-select">
            <option value="">All Programs</option>
            @foreach($options['programs'] as $program)
                <option value="{{ $program }}" @selected($v['program'] === $program)>{{ $program }}</option>
            @endforeach
        </select>
    </div>
    <div class="sb-filter-actions">
        {{-- Hidden once the script loads, since results then update on their own. --}}
        <button type="submit" class="btn btn-primary sb-live-apply">Filter</button>
        <a href="{{ $action }}" class="btn btn-outline-secondary" data-tf-clear @if(! $filters->active()) aria-disabled="true" @endif>Clear Filters</a>
    </div>
  </div>
    <p class="sb-note mt-2" data-tf-count aria-live="polite">Showing {{ number_format($showing) }} of {{ number_format($total) }}</p>
</form>

@push('scripts')
    <script src="{{ asset('js/table-filters.js') }}?v={{ @filemtime(public_path('js/table-filters.js')) }}" defer></script>
@endpush
