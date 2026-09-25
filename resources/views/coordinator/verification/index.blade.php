@extends('layouts.app')

@section('title', 'Verification Queue')

@section('content')
<style>
    .verification-page {
        color: #edf6ff;
        padding-top: 0.25rem;
    }

    .verification-header {
        margin-bottom: 1.3rem;
    }

    .verification-header h1 {
        margin: 0;
        font-size: clamp(2.3rem, 2vw + 1.2rem, 3.3rem);
        line-height: 1.06;
        letter-spacing: -0.05em;
        color: #f3f9ff;
        font-weight: 800;
    }

    .verification-header p {
        margin-top: 0.7rem;
        color: rgba(214, 227, 240, 0.82);
        font-size: 1.02rem;
        line-height: 1.5;
    }

    .verification-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1.15rem;
        align-items: start;
    }

    .verification-panel {
        background: rgba(11, 25, 37, 0.95);
        border: 1px solid rgba(138, 176, 212, 0.18);
        border-radius: 16px;
        box-shadow: 0 10px 22px rgba(2, 6, 23, 0.18);
        min-height: 560px;
        overflow: hidden;
    }

    .verification-panel-header {
        padding: 1rem 1rem 0.9rem;
        background: rgba(17, 32, 45, 0.95);
        border-bottom: 1px solid rgba(138, 176, 212, 0.1);
    }

    .verification-panel-header h5 {
        margin: 0;
        font-size: 1.2rem;
        font-weight: 700;
        color: #eef8ff;
    }

    .verification-panel-body {
        padding: 0.9rem;
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
    }

    .verification-item {
        background: rgba(16, 31, 45, 0.82);
        border: 1px solid rgba(138, 176, 212, 0.12);
        border-radius: 12px;
        padding: 0.85rem 0.85rem 0.75rem;
    }

    .verification-item-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.7rem;
    }

    .verification-item h6 {
        margin: 0 0 0.35rem;
        font-size: 1.05rem;
        line-height: 1.3;
        color: #f2f8ff;
        font-weight: 700;
    }

    .verification-meta {
        margin: 0;
        color: rgba(214, 227, 240, 0.76);
        font-size: 0.78rem;
        line-height: 1.45;
    }

    .verification-badge {
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 72px;
        padding: 0.26rem 0.5rem;
        border-radius: 999px;
        font-size: 0.67rem;
        font-weight: 700;
        color: #102b20;
        background: linear-gradient(135deg, #f7dc7a, #f2bb2e);
        box-shadow: inset 0 0 0 1px rgba(17, 23, 27, 0.08);
    }

    .verification-item .evidence-text {
        margin-top: 0.75rem;
        color: rgba(214, 227, 240, 0.8);
        font-size: 0.82rem;
        line-height: 1.5;
    }

    .verification-form {
        margin-top: 0.8rem;
    }

    .verification-form-block {
        margin-bottom: 0.85rem;
    }

    .verification-form label {
        display: block;
        margin-bottom: 0.38rem;
        font-size: 0.7rem;
        letter-spacing: 0.06em;
        color: rgba(224, 236, 246, 0.86);
        text-transform: uppercase;
        font-weight: 700;
    }

    .verification-form select,
    .verification-form textarea {
        width: 100%;
        background: rgba(10, 20, 29, 0.9);
        border: 1px solid rgba(138, 176, 212, 0.18);
        border-radius: 9px;
        color: #edf7ff;
        padding: 0.7rem 0.75rem;
        font-size: 0.96rem;
        resize: vertical;
    }

    .verification-form select:focus,
    .verification-form textarea:focus {
        outline: none;
        border-color: rgba(111, 198, 255, 0.7);
        box-shadow: 0 0 0 0.2rem rgba(111, 198, 255, 0.12);
    }

    .verification-form textarea {
        min-height: 64px;
    }

    .verification-submit {
        width: 100%;
        margin-top: 0.8rem;
        border: none;
        border-radius: 12px;
        background: linear-gradient(180deg, #edf2f7, #ccd7e3);
        color: #0d1f2d;
        font-weight: 800;
        font-size: 1.15rem;
        padding: 0.8rem 1rem;
        transition: transform 0.15s ease, opacity 0.15s ease;
    }

    .verification-submit:hover {
        opacity: 0.98;
        transform: translateY(-1px);
    }

    .verification-empty {
        background: rgba(8, 18, 28, 0.58);
        border: 1px dashed rgba(138, 176, 212, 0.25);
        border-radius: 12px;
        padding: 1.5rem 1rem;
        text-align: center;
        color: rgba(214, 227, 240, 0.78);
        font-size: 0.95rem;
    }

    .verification-compact-meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
    }

    @media (max-width: 1200px) {
        .verification-grid {
            grid-template-columns: 1fr;
        }

        .verification-panel {
            min-height: auto;
        }
    }
</style>

<div class="verification-page">
    <div class="verification-header">
        <h1>Pending Verification Queue</h1>
        <p>Review submitted competencies, certificates, and portfolio evidence for your institution.</p>
    </div>

    <div class="verification-grid">
        <div class="verification-panel">
            <div class="verification-panel-header">
                <h5>Competencies</h5>
            </div>
            <div class="verification-panel-body">
                @forelse($pendingCompetencies as $competency)
                    <div class="verification-item">
                        <div class="verification-item-header">
                            <div>
                                <h6>{{ $competency->name }}</h6>
                                <div class="verification-compact-meta">
                                    <p class="verification-meta">{{ $competency->student->user->name }}</p>
                                    @if($competency->evidence_name)
                                        <span class="verification-badge">Evidence</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="verification-meta">{{ $competency->category->label() }}</div>

                        @if($competency->evidence_name)
                            <div class="evidence-text">{{ $competency->evidence_name }}</div>
                        @endif

                        <form method="POST" action="{{ route('coordinator.verification.reviewCompetency', $competency) }}" class="verification-form">
                            @csrf
                            <div class="verification-form-block">
                                <label>Decision</label>
                                <select name="verification_status" required>
                                    <option value="verified">Verified</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="verification-form-block" style="margin-bottom:0;">
                                <label>Notes</label>
                                <textarea name="review_notes" placeholder="Add review notes..."></textarea>
                            </div>
                            <button type="submit" class="verification-submit">Save Review</button>
                        </form>
                    </div>
                @empty
                    <div class="verification-empty">No pending competency evidence.</div>
                @endforelse
            </div>
        </div>

        <div class="verification-panel">
            <div class="verification-panel-header">
                <h5>Certificates</h5>
            </div>
            <div class="verification-panel-body">
                @forelse($pendingCertificates as $certificate)
                    <div class="verification-item">
                        <div class="verification-item-header">
                            <div>
                                <h6>{{ $certificate->title }}</h6>
                                <div class="verification-compact-meta">
                                    <p class="verification-meta">{{ $certificate->student->user->name }}</p>
                                    @if($certificate->verification_status)
                                        <span class="verification-badge">{{ ucfirst(str_replace('_', ' ', $certificate->verification_status)) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($certificate->issuer)
                            <div class="verification-meta">{{ $certificate->issuer }}</div>
                        @endif

                        @if($certificate->expiration_date)
                            <div class="evidence-text">Expires: {{ $certificate->expiration_date->format('M d, Y') }}</div>
                        @endif

                        <form method="POST" action="{{ route('coordinator.verification.reviewCertificate', $certificate) }}" class="verification-form">
                            @csrf
                            <div class="verification-form-block">
                                <label>Decision</label>
                                <select name="verification_status" required>
                                    <option value="verified">Verified</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="verification-form-block" style="margin-bottom:0;">
                                <label>Notes</label>
                                <textarea name="review_notes" placeholder="Add review notes..."></textarea>
                            </div>
                            <button type="submit" class="verification-submit">Save Review</button>
                        </form>
                    </div>
                @empty
                    <div class="verification-empty">No pending certificate evidence.</div>
                @endforelse
            </div>
        </div>

        <div class="verification-panel">
            <div class="verification-panel-header">
                <h5>Portfolio Items</h5>
            </div>
            <div class="verification-panel-body">
                @forelse($pendingPortfolios as $portfolio)
                    <div class="verification-item">
                        <div class="verification-item-header">
                            <div>
                                <h6>{{ $portfolio->title }}</h6>
                                <div class="verification-compact-meta">
                                    <p class="verification-meta">{{ $portfolio->student->user->name }}</p>
                                    @if($portfolio->verification_status)
                                        <span class="verification-badge">{{ ucfirst(str_replace('_', ' ', $portfolio->verification_status)) }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if($portfolio->type)
                            <div class="verification-meta">{{ $portfolio->type->label() }}</div>
                        @endif

                        @if($portfolio->description)
                            <div class="evidence-text">{{ Str::limit($portfolio->description, 100) }}</div>
                        @endif

                        <form method="POST" action="{{ route('coordinator.verification.reviewPortfolio', $portfolio) }}" class="verification-form">
                            @csrf
                            <div class="verification-form-block">
                                <label>Decision</label>
                                <select name="verification_status" required>
                                    <option value="verified">Verified</option>
                                    <option value="rejected">Rejected</option>
                                </select>
                            </div>
                            <div class="verification-form-block" style="margin-bottom:0;">
                                <label>Notes</label>
                                <textarea name="review_notes" placeholder="Add review notes..."></textarea>
                            </div>
                            <button type="submit" class="verification-submit">Save Review</button>
                        </form>
                    </div>
                @empty
                    <div class="verification-empty">No pending portfolio evidence.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
