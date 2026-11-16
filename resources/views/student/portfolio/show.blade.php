@extends('layouts.app')

@section('title', $portfolio->title)

@php
    $extension = strtolower(pathinfo((string) $portfolio->file_path, PATHINFO_EXTENSION));
    $isImage = in_array($extension, ['jpg', 'jpeg', 'png'], true);
@endphp

@section('content')
<x-page-header :title="$portfolio->title" :subtitle="$portfolio->type->label().' · '.strtoupper($extension).' file'">
    <a href="{{ route('student.profile.edit') }}#portfolio" class="btn btn-outline-secondary"><i class="bi bi-chevron-left" aria-hidden="true"></i> Back to Portfolio</a>
</x-page-header>

<div class="sb-layout-2">
    <div class="sb-stack">
        @if($portfolio->description)
            <section class="card">
                <div class="card-header"><h2>Description</h2></div>
                <div class="card-body"><p class="mb-0">{{ $portfolio->description }}</p></div>
            </section>
        @endif

        @if($isImage || $extension === 'pdf')
            <section class="card">
                <div class="card-header"><h2>Preview</h2></div>
                <div class="card-body">
                    @if($isImage)
                        <div class="sb-file-preview">
                            <img src="{{ route('student.portfolio.preview', $portfolio, false) }}" alt="{{ $portfolio->title }}" onerror="this.hidden=true;this.insertAdjacentHTML('afterend', '&lt;p class=&quot;sb-note&quot;&gt;Preview not available. The file could not be loaded.&lt;/p&gt;');">
                        </div>
                    @else
                        <div class="sb-file-placeholder">
                            <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                            <p>Click "Open Preview" or "Download" to view the PDF</p>
                        </div>
                    @endif
                </div>
            </section>
        @endif
    </div>

    <aside class="sb-stack">
        <section class="card">
            <div class="card-header"><h2><i class="bi bi-info-circle" aria-hidden="true"></i> File Information</h2></div>
            <div class="card-body">
                <dl class="sb-fields">
                    <div><dt>Filename</dt><dd><code>{{ pathinfo((string) $portfolio->file_path, PATHINFO_BASENAME) }}</code></dd></div>
                    <div><dt>File Type</dt><dd>{{ strtoupper($extension) }}</dd></div>
                    <div><dt>Uploaded On</dt><dd>{{ $portfolio->created_at->format('M d, Y \a\t g:i A') }}</dd></div>
                    <div><dt>Last Modified</dt><dd>{{ $portfolio->updated_at->format('M d, Y \a\t g:i A') }}</dd></div>
                </dl>
            </div>
        </section>

        <section class="card">
            <div class="card-body d-grid gap-2">
                <a href="{{ route('student.portfolio.preview', $portfolio, false) }}" class="btn btn-outline-primary" target="_blank"><i class="bi bi-eye" aria-hidden="true"></i> Open Preview in New Tab</a>
                <a href="{{ route('student.portfolio.download', $portfolio, false) }}" class="btn btn-outline-secondary"><i class="bi bi-download" aria-hidden="true"></i> Download File</a>
                <a href="{{ route('student.portfolio.edit', $portfolio) }}" class="btn btn-outline-secondary"><i class="bi bi-pencil" aria-hidden="true"></i> Edit Details</a>
                <a href="{{ route('student.profile.edit') }}#portfolio" class="btn btn-outline-secondary"><i class="bi bi-x-circle" aria-hidden="true"></i> Back to Portfolio</a>
            </div>
        </section>

        <div class="sb-callout">
            <p><strong><i class="bi bi-lightbulb" aria-hidden="true"></i> Tip:</strong> Keep your portfolio updated with recent projects and certifications. Employers review this when evaluating your internship application.</p>
        </div>
    </aside>
</div>
@endsection
