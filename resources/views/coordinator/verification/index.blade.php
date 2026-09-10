@extends('layouts.app')

@section('title', 'Verification Queue')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">Pending Verification Queue</h1>
        <p class="text-muted mb-0">Review submitted competencies, certificates, and portfolio evidence for your institution.</p>
    </div>
</div>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Competencies</h5>
            </div>
            <div class="list-group list-group-flush">
                @forelse($pendingCompetencies as $competency)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <h6 class="mb-1">{{ $competency->name }}</h6>
                                <div class="text-muted small mb-2">{{ $competency->student->user->name }} · {{ $competency->category->label() }}</div>
                                @if($competency->evidence_name)
                                    <div class="small text-muted">Evidence: {{ $competency->evidence_name }}</div>
                                @endif
                            </div>
                            <span class="badge bg-warning text-dark">{{ ucfirst(str_replace('_', ' ', $competency->verification_status)) }}</span>
                        </div>

                        <form method="POST" action="{{ route('coordinator.verification.reviewCompetency', $competency) }}" class="mt-3">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small">Decision</label>
                                <select name="verification_status" class="form-select form-select-sm" required>
                                    <option value="verified">Verified</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Notes</label>
                                <textarea name="review_notes" class="form-control form-control-sm" rows="2" placeholder="Add review notes..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary w-100">Save Review</button>
                        </form>
                    </div>
                @empty
                    <div class="list-group-item text-center text-muted py-4">No pending competency evidence.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Certificates</h5>
            </div>
            <div class="list-group list-group-flush">
                @forelse($pendingCertificates as $certificate)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <h6 class="mb-1">{{ $certificate->title }}</h6>
                                <div class="text-muted small mb-2">{{ $certificate->student->user->name }} · {{ $certificate->issuer ?? 'Issuer not provided' }}</div>
                                @if($certificate->expiration_date)
                                    <div class="small text-muted">Expires: {{ $certificate->expiration_date->format('M d, Y') }}</div>
                                @endif
                            </div>
                            <span class="badge bg-warning text-dark">{{ ucfirst(str_replace('_', ' ', $certificate->verification_status)) }}</span>
                        </div>

                        <form method="POST" action="{{ route('coordinator.verification.reviewCertificate', $certificate) }}" class="mt-3">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small">Decision</label>
                                <select name="verification_status" class="form-select form-select-sm" required>
                                    <option value="verified">Verified</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Notes</label>
                                <textarea name="review_notes" class="form-control form-control-sm" rows="2" placeholder="Add review notes..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary w-100">Save Review</button>
                        </form>
                    </div>
                @empty
                    <div class="list-group-item text-center text-muted py-4">No pending certificate evidence.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0">Portfolio Items</h5>
            </div>
            <div class="list-group list-group-flush">
                @forelse($pendingPortfolios as $portfolio)
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start gap-3">
                            <div>
                                <h6 class="mb-1">{{ $portfolio->title }}</h6>
                                <div class="text-muted small mb-2">{{ $portfolio->student->user->name }} · {{ $portfolio->type->label() }}</div>
                                @if($portfolio->description)
                                    <div class="small text-muted">{{ Str::limit($portfolio->description, 100) }}</div>
                                @endif
                            </div>
                            <span class="badge bg-warning text-dark">{{ ucfirst(str_replace('_', ' ', $portfolio->verification_status)) }}</span>
                        </div>

                        <form method="POST" action="{{ route('coordinator.verification.reviewPortfolio', $portfolio) }}" class="mt-3">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label small">Decision</label>
                                <select name="verification_status" class="form-select form-select-sm" required>
                                    <option value="verified">Verified</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Notes</label>
                                <textarea name="review_notes" class="form-control form-control-sm" rows="2" placeholder="Add review notes..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary w-100">Save Review</button>
                        </form>
                    </div>
                @empty
                    <div class="list-group-item text-center text-muted py-4">No pending portfolio evidence.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
