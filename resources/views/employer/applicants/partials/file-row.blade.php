<li class="d-flex justify-content-between align-items-center gap-2 border-bottom py-2">
    <span>{{ $file['title'] }}
        @if(! empty($file['issuer']))<small class="text-muted d-block">{{ $file['issuer'] }}</small>@endif
        @if(! empty($file['description']))<small class="text-muted d-block">{{ $file['description'] }}</small>@endif
        @if(! empty($file['tools_used']))<small class="text-muted d-block">Tools: {{ $file['tools_used'] }}</small>@endif
        @if(! empty($file['achievements']))<small class="d-block">{{ $file['achievements'] }}</small>@endif
        @if(! empty($file['project_url']))<a class="small d-block" href="{{ $file['project_url'] }}" target="_blank" rel="noopener noreferrer nofollow">Open project link</a>@endif
    </span>
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
