{{-- ResumePreview: the resume template (student/resume/pdf.blade.php, preview mode) shown at A4 size,
     scaled to the column. $html is the first render; public/js/resume-editor.js swaps in fresh HTML
     from the preview endpoint as the student types. The frame is sandboxed without scripts. --}}
@props(['html', 'title' => 'Resume preview'])

<section {{ $attributes->class(['rp card']) }} data-resume-preview aria-labelledby="resumePreviewTitle">
    <div class="rp-bar">
        <h2 id="resumePreviewTitle"><i class="bi bi-eye" aria-hidden="true"></i> Live preview</h2>
        <span class="rp-status" data-preview-status role="status" aria-live="polite">Up to date</span>
    </div>
    <div class="rp-error" data-preview-error hidden>
        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
        <span data-preview-error-text>The preview could not update.</span>
        <button type="button" class="btn btn-sm btn-outline-primary" data-preview-retry>Try again</button>
    </div>
    <div class="rp-stage" data-preview-stage>
        <iframe class="rp-frame" title="{{ $title }}" sandbox="allow-same-origin" srcdoc="{{ $html }}" data-preview-frame tabindex="-1"></iframe>
    </div>
    <p class="rp-note">The layout is fixed so your resume always looks professional. Only the content changes.</p>
</section>
