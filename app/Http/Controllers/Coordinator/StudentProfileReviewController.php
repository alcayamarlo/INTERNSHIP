<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Models\Coordinator;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentChangeRequest;
use App\Services\ActivityLogService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentProfileReviewController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLog,
        private NotificationService $notifications
    ) {}

    public function verify(Student $student): RedirectResponse
    {
        $coordinator = $this->authorizeStudent($student);

        if (! $student->profile_verified_at) {
            $student->forceFill([
                'profile_verified_at' => now(),
                'profile_verified_by_user_id' => $coordinator->user_id,
            ])->save();
            $this->activityLog->log(Auth::user(), 'student_profile_verified', [
                'student_id' => $student->id,
            ]);
        }

        return back()->with('success', 'Student profile verified.');
    }

    public function updateAcademicReadiness(Request $request, Student $student): RedirectResponse
    {
        $this->authorizeStudent($student);
        $validated = $request->validate([
            'rendered_internship_hours' => ['required', 'integer', 'between:0,10000'],
            'major_subjects_passed' => ['required', 'boolean'],
        ]);

        $student->update([
            'rendered_internship_hours' => $validated['rendered_internship_hours'],
            'major_subjects_passed' => $request->boolean('major_subjects_passed'),
        ]);

        return back()->with('success', 'Academic readiness updated.');
    }

    public function reviewChangeRequest(
        Request $request,
        Student $student,
        StudentChangeRequest $studentChangeRequest
    ): RedirectResponse {
        $coordinator = $this->authorizeStudent($student);
        abort_unless((int) $studentChangeRequest->student_id === (int) $student->id, 404);
        abort_unless($studentChangeRequest->status === 'pending', 409, 'This change request has already been reviewed.');

        $validated = $request->validate([
            'decision' => ['required', Rule::in(['approved', 'rejected'])],
            'coordinator_note' => ['nullable', 'string', 'max:2000', 'required_if:decision,rejected'],
        ]);

        DB::transaction(function () use ($student, $studentChangeRequest, $coordinator, $validated): void {
            $lockedRequest = StudentChangeRequest::query()->lockForUpdate()->findOrFail($studentChangeRequest->id);
            abort_unless($lockedRequest->status === 'pending', 409, 'This change request has already been reviewed.');
            $student->refresh();
            $this->assertRequestStillCurrent($student, $lockedRequest);

            if ($validated['decision'] === 'approved') {
                $this->applyRequestedValue($student, $lockedRequest);
            }

            $lockedRequest->update([
                'status' => $validated['decision'],
                'coordinator_note' => $validated['coordinator_note'] ?? null,
                'reviewed_by_user_id' => $coordinator->user_id,
                'reviewed_at' => now(),
            ]);
            $this->activityLog->log(Auth::user(), 'student_profile_change_request_'.$validated['decision'], [
                'student_id' => $student->id,
                'change_request_id' => $lockedRequest->id,
                'field' => $lockedRequest->field,
            ]);
        });

        $approved = $validated['decision'] === 'approved';
        $this->notifications->send(
            $student->user,
            'profile_change_request',
            $approved ? 'Profile change request approved' : 'Profile change request rejected',
            $approved
                ? 'Your requested profile change has been approved.'
                : 'Your requested profile change was rejected.'.(filled($validated['coordinator_note'] ?? null) ? ' Coordinator note: '.$validated['coordinator_note'] : ''),
            ['student_id' => $student->id, 'change_request_id' => $studentChangeRequest->id, 'status' => $validated['decision']]
        );

        return back()->with('success', $approved ? 'Change request approved.' : 'Change request rejected.');
    }

    private function authorizeStudent(Student $student): Coordinator
    {
        $coordinator = Auth::user()->coordinator;
        abort_unless($coordinator && $coordinator->canAccessStudent($student), 403, 'You cannot manage this student.');

        return $coordinator;
    }

    private function assertRequestStillCurrent(Student $student, StudentChangeRequest $changeRequest): void
    {
        $currentValue = (string) ($student->getAttribute($changeRequest->field) ?? '');
        if ($currentValue !== (string) ($changeRequest->current_value ?? '')) {
            throw ValidationException::withMessages([
                'change_request' => 'The student information changed after this request was submitted. Review the current value before deciding.',
            ]);
        }
    }

    private function applyRequestedValue(Student $student, StudentChangeRequest $changeRequest): void
    {
        // Student ID changes are never applied here: only an admin can change a
        // student's ID (it's their login identifier), via the admin users page.
        $attributes = match ($changeRequest->field) {
            'program_id' => $this->programAttributes($changeRequest->requested_value),
            'year_level' => ['year_level' => $changeRequest->requested_value],
            default => abort(422, 'Unsupported profile field.'),
        };
        $student->update($attributes);
    }

    private function programAttributes(string $programId): array
    {
        $program = Program::find($programId);
        if (! $program) {
            throw ValidationException::withMessages([
                'change_request' => 'The requested program is no longer available.',
            ]);
        }

        return [
            'program_id' => $program->id,
            'program' => $program->display_name,
            'major_id' => null,
            'major_subjects_passed' => false,
        ];
    }
}
