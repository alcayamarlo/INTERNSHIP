{{-- A main-column section of separate entries (certificate, project, ...). The bar is kept with the
     first entry; each entry stays whole on one page. Hidden when there are no entries. --}}
@foreach($items as $index => $item)
    @if($index === 0)<div class="keep"><div class="bar">{{ $heading }}</div>@endif
    <div class="entry">
        <p class="entry-title">{{ $item['title'] }}</p>
        @if(! empty($item['rows']))
            @include('student.resume.partials.pdf-rows', ['rows' => $item['rows']])
        @endif
        @if(! empty($item['skills']))
            <ul class="entry-skills">
                @foreach($item['skills'] as $skill)
                    <li>{{ $skill }}</li>
                @endforeach
            </ul>
        @endif
        @if(filled($item['description'] ?? null))
            <p class="entry-note">{{ $item['description'] }}</p>
        @endif
    </div>
    @if($index === 0)</div>@endif
@endforeach
