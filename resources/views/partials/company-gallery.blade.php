{{-- Company photo gallery. Expects $employer (with photos). Optional $heading. Pictures whose file is missing are left
     out, and the whole card is left out when there is no picture to show. --}}
@php
    $pictures = $employer->photos->map(fn ($photo) => ['url' => $photo->url(), 'caption' => $photo->caption])->filter(fn ($picture) => $picture['url'])->values();
@endphp
@if($pictures->isNotEmpty())
    <section class="card company-gallery" aria-labelledby="companyGalleryHeading-{{ $employer->id }}">
        <div class="card-header"><h2 class="h6 mb-0" id="companyGalleryHeading-{{ $employer->id }}"><i class="bi bi-images" aria-hidden="true"></i> {{ $heading ?? 'Company photos' }}</h2></div>
        <div class="card-body">
            <div class="row g-3">
                @foreach($pictures as $picture)
                    <figure class="col-sm-6 col-lg-4 mb-0">
                        <a href="{{ $picture['url'] }}" target="_blank" rel="noopener" class="d-block ratio ratio-4x3 rounded overflow-hidden border">
                            <img src="{{ $picture['url'] }}" alt="{{ $picture['caption'] ?: $employer->company_name.' office' }}" loading="lazy" style="object-fit: cover; width: 100%; height: 100%;">
                        </a>
                        @if($picture['caption'])<figcaption class="small text-muted mt-1">{{ $picture['caption'] }}</figcaption>@endif
                    </figure>
                @endforeach
            </div>
        </div>
    </section>
@endif
