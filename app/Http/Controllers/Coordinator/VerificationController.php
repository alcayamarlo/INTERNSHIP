<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Portfolio;
use App\Models\StudentCompetency;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VerificationController extends Controller
{
    public function __construct(private NotificationService $notificationService) {}

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

        $status = $validated['verification_status'];

        $competency->update([
            'verification_status' => $status,
            'review_notes' => $validated['review_notes'] ?? null,
            'reviewed_at' => now(),
        ]);

        $this->sendVerificationNotification(
            $competency->student->user,
            'competency',
            $status,
            $competency->name,
            $validated['review_notes'] ?? null
        );

        return back()->with('success', 'Competency evidence updated.');
    }

    public function reviewCertificate(Request $request, Certificate $certificate)
    {
        $this->authorizeCoordinatorAccess($certificate);

        $validated = $request->validate([
            'verification_status' => ['required', 'in:verified,rejected'],
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $status = $validated['verification_status'];

        $certificate->update([
            'verification_status' => $status,
            'review_notes' => $validated['review_notes'] ?? null,
            'reviewed_at' => now(),
        ]);

        $this->sendVerificationNotification(
            $certificate->student->user,
            'certificate',
            $status,
            $certificate->title,
            $validated['review_notes'] ?? null
        );

        return back()->with('success', 'Certificate evidence updated.');
    }

    public function reviewPortfolio(Request $request, Portfolio $portfolio)
    {
        $this->authorizeCoordinatorAccess($portfolio);

        $validated = $request->validate([
            'verification_status' => ['required', 'in:verified,rejected'],
            'review_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $status = $validated['verification_status'];

        $portfolio->update([
            'verification_status' => $status,
            'review_notes' => $validated['review_notes'] ?? null,
            'reviewed_at' => now(),
        ]);

        $this->sendVerificationNotification(
            $portfolio->student->user,
            'portfolio',
            $status,
            $portfolio->title,
            $validated['review_notes'] ?? null
        );

        return back()->with('success', 'Portfolio evidence updated.');
    }

    private function sendVerificationNotification($user, string $type, string $status, string $itemName, ?string $reviewNotes = null): void
    {
        $isVerified = $status === 'verified';

        $this->notificationService->send(
            $user,
            'verification',
            ucfirst($type).' Evidence '.($isVerified ? 'Verified' : 'Rejected'),
            $isVerified
                ? 'Your '.$type.' evidence "'.$itemName.'" has been verified by the coordinator.'
                : 'Your '.$type.' evidence "'.$itemName.'" was rejected by the coordinator. '.($reviewNotes ? 'Note: '.$reviewNotes : ''),
            [
                'verification_type' => $type,
                'item_name' => $itemName,
                'status' => $status,
                'review_notes' => $reviewNotes,
            ]
        );
    }

    private function authorizeCoordinatorAccess($model): void
    {
        $coordinator = Auth::user()->coordinator;

        abort_unless($coordinator && $coordinator->institution_id, 403, 'You are not assigned to an institution.');

        $student = $model->student()->firstOrFail();

        abort_unless($coordinator->canAccessStudent($student), 403, 'You cannot review evidence for this student.');
    }
}
