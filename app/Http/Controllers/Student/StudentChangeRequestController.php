<?php

namespace App\Http\Controllers\Student;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\StudentChangeRequest;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentChangeRequestController extends Controller
{
    public function store(Request $request, NotificationService $notifications): RedirectResponse
    {
        abort_unless($request->user()?->isRole(UserRole::Student), 403);
        $student = $request->user()->student;
        abort_unless($student, 404);

        if (! $student->profile_verified_at) {
            throw ValidationException::withMessages([
                'field' => 'Change requests are available after your coordinator verifies your profile.',
            ]);
        }

        $validated = $request->validate([
            'field' => ['required', Rule::in(['program_id', 'year_level'])],
            'requested_value' => ['required', 'string', 'max:255'],
        ]);
        $field = $validated['field'];
        $value = trim($validated['requested_value']);

        $rules = match ($field) {
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'year_level' => ['required', Rule::in(['1st Year', '2nd Year', '3rd Year', '4th Year'])],
        };
        $validatedValue = validator(['requested_value' => $value], ['requested_value' => $rules], [
            'requested_value.unique' => 'This student ID is already in use.',
        ])->validate()['requested_value'];

        $currentValue = (string) ($student->getAttribute($field) ?? '');
        if ((string) $validatedValue === $currentValue) {
            throw ValidationException::withMessages([
                'requested_value' => 'Choose a value different from your current one.',
            ]);
        }

        if ($student->changeRequests()->where('field', $field)->where('status', 'pending')->exists()) {
            return back()->with('error', 'You already have a pending request for this field.')->withFragment('academic');
        }

        $coordinatorUser = $student->academicProgram()->with('coordinator.user')->first()?->coordinator?->user;
        if (! $coordinatorUser) {
            return back()->with('error', 'No coordinator is assigned to your program yet.')->withFragment('academic');
        }

        $changeRequest = $student->changeRequests()->create([
            'field' => $field,
            'current_value' => $currentValue === '' ? null : $currentValue,
            'requested_value' => (string) $validatedValue,
            'status' => 'pending',
        ]);

        $label = match ($field) {
            'student_id_number' => 'Student ID',
            'program_id' => 'Program',
            'year_level' => 'Year level',
        };
        $notifications->send(
            $coordinatorUser,
            'profile_change_request',
            'Student profile change request',
            $student->user->name.' requested a change to '.$label.'.',
            ['student_id' => $student->id, 'change_request_id' => $changeRequest->id]
        );

        return back()->with('success', 'Change request sent to your coordinator.')->withFragment('academic');
    }
}
