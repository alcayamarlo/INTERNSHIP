@if($rows)
    @if($heading)<h3 class="sb-block-title">{{ $heading }}</h3>@endif
    <dl class="sb-fields">
        @foreach($rows as $label => $value)
            <div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>
        @endforeach
    </dl>
@endif
