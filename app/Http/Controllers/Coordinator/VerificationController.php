<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Portfolio;
use App\Models\StudentCompetency;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerificationController extends Controller
{
    public function index()
    {
        $coordinator = Auth::user()->coordinator;

        abort_unless($coordinator && $coordinator->institution_id, 403, 'No institution has been assigned to this coordinator.');

        $studentIds = $coordinator->scopedStudentsQuery()->pluck('id');

        $pendingCompetencies = StudentCompetency::whereIn('student_id', $studentIds)
            ->whereIn('verification_status', ['evidence_submitted', 'rejected'])
            ->with(['student.user'])
            ->orderByDesc('created_at')
            ->get();

        $pendingCertificates = Certificate::whereIn('student_id', $studentIds)
            ->whereIn('verification_status', ['evidence_submitted', 'rejected'])
            ->with(['student.user'])
            ->orderByDesc('created_at')
            ->get();

        $pendingPortfolios = Portfolio::whereIn('student_id', $studentIds)
            ->whereIn('verification_status', ['evidence_submitted', 'rejected'])
            ->with(['student.user'])
            ->orderByDesc('created_at')
            ->get();

        return view('coordinator.verification.index', [
            'pendingCompetencies' => $pendingCompetencies,
            'pendingCertificates' => $pendingCertificates,
            'pendingPortfolios' => $pendingPortfolios,
        ]);
    }

    public function reviewCompetency(Request $request, StudentCompetency $competency)
    {
        $this->authorizeCoordinatorAccess($competency);

        $validated = $request->validate([
            'verification_status' => ['required', 'in:verified,rejected'],
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $competency->update([
            'verification_status' => $validated['verification_status'],
            'review_notes' => $validated['review_notes'] ?? null,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Competency evidence updated.');
    }

    public function reviewCertificate(Request $request, Certificate $certificate)
    {
        $this->authorizeCoordinatorAccess($certificate);

        $validated = $request->validate([
            'verification_status' => ['required', 'in:verified,rejected'],
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $certificate->update([
            'verification_status' => $validated['verification_status'],
            'review_notes' => $validated['review_notes'] ?? null,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Certificate evidence updated.');
    }

    public function reviewPortfolio(Request $request, Portfolio $portfolio)
    {
        $this->authorizeCoordinatorAccess($portfolio);

        $validated = $request->validate([
            'verification_status' => ['required', 'in:verified,rejected'],
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $portfolio->update([
            'verification_status' => $validated['verification_status'],
            'review_notes' => $validated['review_notes'] ?? null,
            'reviewed_at' => now(),
        ]);

        return back()->with('success', 'Portfolio evidence updated.');
    }

    private function authorizeCoordinatorAccess($model): void
    {
        $coordinator = Auth::user()->coordinator;

        abort_unless($coordinator && $coordinator->institution_id, 403, 'You are not assigned to an institution.');

        $student = $model->student()->firstOrFail();

        abort_unless($coordinator->canAccessStudent($student), 403, 'You cannot review evidence for this student.');
    }
}
