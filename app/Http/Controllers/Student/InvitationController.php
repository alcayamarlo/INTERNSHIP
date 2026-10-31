<?php

namespace App\Http\Controllers\Student;

use App\Enums\InvitationStatus;
use App\Http\Controllers\Controller;
use App\Models\InternshipInvitation;
use App\Services\InvitationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class InvitationController extends Controller
{
    public function __construct(private InvitationService $invitations) {}

    public function index()
    {
        $invitations = Auth::user()->student->invitations()
            ->with(['internship.employer', 'application:id'])
            ->orderByRaw('case when status = ? then 0 else 1 end', [InvitationStatus::Pending->value])
            ->latest()
            ->paginate(15);

        return view('student.invitations.index', [
            'invitations' => $invitations,
            'hasResume' => Auth::user()->student->resumes()->whereNotNull('file_path')->exists(),
        ]);
    }

    public function accept(InternshipInvitation $invitation)
    {
        $this->authorize('respond', $invitation);

        try {
            $application = $this->invitations->accept($invitation, Auth::user());
        } catch (ValidationException $exception) {
            return redirect()->route('student.invitations.index')
                ->with('error', collect($exception->errors())->flatten()->first())
                ->with('needs_resume', array_key_exists('resume', $exception->errors()));
        }

        return redirect()->route('student.applications.show', $application)
            ->with('success', InvitationService::ACCEPTED_MESSAGE);
    }

    public function decline(InternshipInvitation $invitation)
    {
        $this->authorize('respond', $invitation);

        try {
            $this->invitations->decline($invitation);
        } catch (ValidationException $exception) {
            return redirect()->route('student.invitations.index')->with('error', collect($exception->errors())->flatten()->first());
        }

        return redirect()->route('student.invitations.index')->with('success', 'Invitation declined.');
    }
}
