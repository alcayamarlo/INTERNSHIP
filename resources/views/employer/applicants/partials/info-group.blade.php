{{-- Compact label/value group for the applicant review page. Expects $heading and $rows (label => value). --}}
@if(! empty($rows))
    <section class="ar-group">
        <h2>{{ $heading }}</h2>
        <dl>
            @foreach($rows as $label => $value)
                <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
            @endforeach
        </dl>
    </section>
@endif
