@if($rows)
    @if($heading)<h3 class="h6">{{ $heading }}</h3>@endif
    <dl class="row small mb-3">
        @foreach($rows as $label => $value)
            <dt class="col-sm-4">{{ $label }}</dt><dd class="col-sm-8">{{ $value }}</dd>
        @endforeach
    </dl>
@endif
