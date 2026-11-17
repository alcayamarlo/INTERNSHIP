{{-- Industry dropdown with search, used by the company invite form and the company profile.
     Props: $id (id of the select), $stored (the industry saved now, or null).
     Sends `industry` (a listed sector, or "Other") and `industry_other` (typed text for "Other").
     Works without JavaScript as a plain select; Tom Select (loaded with SRI) adds the search box. --}}
@php
    $catalog = app(\App\Services\IndustryCatalog::class);
    $other = \App\Services\IndustryCatalog::OTHER;
    $choice = old('industry');
    if ($choice === null) {
        // A value saved before the list existed is free text: show it under "Other" so nothing is lost.
        $choice = blank($stored ?? null) ? '' : ($catalog->isListed($stored) ? $stored : $other);
    }
    $otherText = old('industry_other', $choice === $other && ! old('industry') ? ($stored ?? '') : '');
@endphp

<select id="{{ $id }}" name="industry" class="form-select form-control @error('industry') is-invalid @enderror" required
        data-industry-select data-other-input="{{ $id }}Other" data-other-value="{{ $other }}" aria-describedby="{{ $id }}Other">
    <option value="" @selected($choice === '')>Search or choose an industry</option>
    @foreach($catalog->sectors() as $sector)
        <option value="{{ $sector }}" @selected($choice === $sector)>{{ $sector }}</option>
    @endforeach
    <option value="{{ $other }}" @selected($choice === $other)>{{ \App\Services\IndustryCatalog::OTHER_LABEL }}</option>
</select>
<input id="{{ $id }}Other" type="text" name="industry_other" class="form-control mt-2 @error('industry_other') is-invalid @enderror"
       maxlength="100" value="{{ $otherText }}" placeholder="Type your industry" aria-label="Your industry" @if($choice !== $other) hidden @endif>
@error('industry')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
@error('industry_other')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror

@once
    {{-- Pinned version, with Subresource Integrity: the browser refuses the file if it was changed. --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tom-select/2.4.3/css/tom-select.default.min.css"
          integrity="sha384-9WZVlhSSWFURxa6PKH4XvmAFEXGmxb7RSnTJAppGXULTEWfQ2O/Ot/UU2ak0cAKX" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="stylesheet" href="{{ asset('css/sb-industry-select.css') }}?v={{ @filemtime(public_path('css/sb-industry-select.css')) }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tom-select/2.4.3/js/tom-select.complete.min.js"
            integrity="sha384-A4TlP4zHVPP9oVIQ8pTVaV6MhvBkDx1sRpmEUfAlO5e7xpodrdr6uNmUV7alXAWW" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script defer src="{{ asset('js/sb-industry-select.js') }}?v={{ @filemtime(public_path('js/sb-industry-select.js')) }}"></script>
@endonce
