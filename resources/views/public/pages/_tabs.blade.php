{{-- Page switcher shared by the public information pages. Expects $active. --}}
@php
    $tabs = [
        'how' => ['How it works', route('pages.how')],
        'students' => ['Students', route('pages.students')],
        'coordinators' => ['Coordinators', route('pages.coordinators')],
        'faqs' => ['FAQs', route('pages.faqs')],
    ];
@endphp
<nav class="sb-pg-tabs" aria-label="More about SkillBridge">
    @foreach ($tabs as $key => [$label, $url])
        <a href="{{ $url }}" @class(['is-on' => $active === $key]) @if ($active === $key) aria-current="page" @endif>{{ $label }}</a>
    @endforeach
</nav>
