@extends('layouts.app')

@section('title', $portfolio->title)

@section('content')
<x-page-header :title="$portfolio->title" :subtitle="$portfolio->type->label().' · '.strtoupper(pathinfo($portfolio->file_path, PATHINFO_EXTENSION)).' file'">
    <a href="{{ route('student.profile.edit') }}#portfolio" class="btn btn-outline-secondary"><i class="bi bi-chevron-left" aria-hidden="true"></i> Back to Portfolio</a>
</x-page-header>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                @if($portfolio->description)
                    <div class="mb-4">
                        <h2 class="h6">Description</h2>
                        <p class="text-muted">{{ $portfolio->description }}</p>
                    </div>
                @endif

                <!-- File Info -->
                <div class="sb-tile mb-4">
                    <div>
                        <h3 class="mb-3"><i class="bi bi-info-circle" aria-hidden="true"></i> File Information</h3>
                        <dl class="row mb-0">
                            <dt class="col-sm-4">Filename:</dt>
                            <dd class="col-sm-8"><code>{{ pathinfo($portfolio->file_path, PATHINFO_BASENAME) }}</code></dd>

                            <dt class="col-sm-4">File Type:</dt>
                            <dd class="col-sm-8">{{ strtoupper(pathinfo($portfolio->file_path, PATHINFO_EXTENSION)) }}</dd>

                            <dt class="col-sm-4">Uploaded On:</dt>
                            <dd class="col-sm-8">{{ $portfolio->created_at->format('M d, Y \a\t g:i A') }}</dd>

                            <dt class="col-sm-4">Last Modified:</dt>
                            <dd class="col-sm-8">{{ $portfolio->updated_at->format('M d, Y \a\t g:i A') }}</dd>
                        </dl>
                    </div>
                </div>

                <!-- Preview (for PDFs and images) -->
                @php
                    $extension = strtolower(pathinfo($portfolio->file_path, PATHINFO_EXTENSION));
                    $isPreviewable = in_array($extension, ['pdf', 'jpg', 'jpeg', 'png']);
                @endphp

                @if($isPreviewable)
                    <div class="mb-4">
                        <h2 class="h6 mb-3">Preview</h2>
                        <div class="border rounded p-3 bg-light" style="min-height: 300px; display: flex; align-items: center; justify-content: center;">
                            @if(in_array($extension, ['jpg', 'jpeg', 'png']))
                                <img src="{{ route('student.portfolio.preview', $portfolio, false) }}" alt="{{ $portfolio->title }}" class="img-fluid" style="max-height: 400px;" onerror="this.hidden=true;this.insertAdjacentHTML('afterend', '&lt;p&gt;Preview not available. The file could not be loaded.&lt;/p&gt;');">
                            @elseif($extension === 'pdf')
                                <div class="text-center">
                                    <i class="bi bi-file-pdf fs-1 text-danger"></i>
                                    <p class="mt-3 text-muted">Click "Open Preview" or "Download" to view the PDF</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Action Buttons -->
                <div class="btn-group-vertical w-100" role="group">
                    <a href="{{ route('student.portfolio.preview', $portfolio, false) }}" class="btn btn-outline-primary text-start" target="_blank">
                        <i class="bi bi-eye"></i> Open Preview in New Tab
                    </a>
                    <a href="{{ route('student.portfolio.download', $portfolio, false) }}" class="btn btn-outline-success text-start">
                        <i class="bi bi-download"></i> Download File
                    </a>
                    <a href="{{ route('student.portfolio.edit', $portfolio) }}" class="btn btn-outline-secondary text-start">
                        <i class="bi bi-pencil"></i> Edit Details
                    </a>
                </div>

                <div class="mt-3">
                    <a href="{{ route('student.profile.edit') }}#portfolio" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-x-circle"></i> Back to Portfolio
                    </a>
                </div>
            </div>
        </div>

        <!-- Info Box -->
        <div class="alert alert-info mt-4">
            <strong><i class="bi bi-lightbulb"></i> Tip:</strong> Keep your portfolio updated with recent projects and certifications. Employers review this when evaluating your internship application.
        </div>
    </div>
</div>
@endsection
