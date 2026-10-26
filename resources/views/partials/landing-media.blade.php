{{-- resources/views/partials/landing-media.blade.php
     Params: name, icon, ratio (hero|square|wide), alt, eager (bool)
     Looks for public/images/landing/{name}.(webp|jpg|jpeg|png), then public/images/{name}.*
     Falls back to a styled gradient panel when no photo exists. --}}
@php
    $name  = $name ?? 'hero';
    $icon  = $icon ?? 'cap';
    $ratio = $ratio ?? 'wide';
    $alt   = $alt ?? '';
    $eager = $eager ?? false;

    $src = null;
    $file = null;
    foreach (['images/landing', 'images'] as $dir) {
        foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
            if (file_exists(public_path("{$dir}/{$name}.{$ext}"))) {
                $src  = asset("{$dir}/{$name}.{$ext}");
                $file = public_path("{$dir}/{$name}.{$ext}");
                break 2;
            }
        }
    }

    // Real pixel size lets the browser reserve space and avoid layout jumps.
    $dims = $file ? @getimagesize($file) : false;
    $w = $dims[0] ?? null;
    $h = $dims[1] ?? null;

    $icons = [
        'cap'      => 'm2 10 10-5 10 5-10 5-10-5ZM6 12v5c3.5 3 8.5 3 12 0v-5M22 10v6',
        'profile'  => 'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z',
        'growth'   => 'M3 17l6-6 4 4 8-8M15 7h6v6',
        'search'   => 'M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM21 21l-4.3-4.3',
        'shield'   => 'm9 12 2 2 4-4M12 22s8-4 8-11V5l-8-3-8 3v6c0 7 8 11 8 11Z',
        'building' => 'M3 21h18M5 21V5l7-3v19M19 21V9l-7-4M8 8v.01M8 12v.01M8 16v.01M15 12v.01M15 16v.01',
    ];
    $path = $icons[$icon] ?? $icons['cap'];
@endphp

<figure class="lm lm-{{ $ratio }} {{ $src ? 'lm-photo' : 'lm-fallback' }}" @unless($src) role="img" aria-label="{{ $alt }}" @endunless>
    @if ($src)
        <img
            src="{{ $src }}"
            alt="{{ $alt }}"
            @if ($w && $h) width="{{ $w }}" height="{{ $h }}" @endif
            @if ($eager) loading="eager" fetchpriority="high" @else loading="lazy" @endif
            decoding="async">
        <span class="lm-shade" aria-hidden="true"></span>
    @else
        <span class="lm-blob lm-blob-a" aria-hidden="true"></span>
        <span class="lm-blob lm-blob-b" aria-hidden="true"></span>
        <span class="lm-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><path d="{{ $path }}"></path></svg>
        </span>
    @endif
</figure>