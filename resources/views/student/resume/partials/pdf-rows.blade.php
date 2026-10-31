<table class="rows">
    @foreach($rows as $label => $value)
        <tr>
            <td class="label">{{ $label }}</td>
            <td class="colon">:</td>
            {{-- Links get break points after "/" (added after escaping) so long URLs wrap. --}}
            <td class="value">@if($label === 'Link'){!! str_replace('/', '/&#8203;', e($value)) !!}@else{{ $value }}@endif</td>
        </tr>
    @endforeach
</table>
