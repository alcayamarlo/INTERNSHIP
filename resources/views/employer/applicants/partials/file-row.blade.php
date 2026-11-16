<li>
    <div class="sb-grow">
        <span class="sb-row-title">{{ $file['title'] }}</span>
        @if(! empty($file['issuer']))<span class="sb-row-sub">{{ $file['issuer'] }}</span>@endif
        @if(! empty($file['description']))<span class="sb-row-sub">{{ $file['description'] }}</span>@endif
        @if(! empty($file['tools_used']))<span class="sb-row-sub">Tools: {{ $file['tools_used'] }}</span>@endif
        @if(! empty($file['achievements']))<small class="d-block mt-1">{{ $file['achievements'] }}</small>@endif
        @if(! empty($file['project_url']))<a class="small d-block mt-1" href="{{ $file['project_url'] }}" target="_blank" rel="noopener noreferrer nofollow">Open project link</a>@endif
    </div>
    @if($file['preview_url'])
        <button type="button" class="btn btn-sm btn-outline-primary employer-file-preview"
            data-preview-url="{{ $file['preview_url'] }}"
            data-file-name="{{ $file['title'] }}" data-file-category="{{ $fileCategory }}" data-file-issuer="{{ $file['issuer'] ?? '' }}"
            data-file-uploaded="{{ $file['uploaded'] ?? 'Unknown' }}"
            data-file-status="Submitted" data-file-reviewer="—" data-file-reviewed-at="—" data-file-size="—"
            data-file-extension="{{ $file['file_extension'] }}">
            <i class="bi bi-eye" aria-hidden="true"></i> View
        </button>
    @endif
</li>
