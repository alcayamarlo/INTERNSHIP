<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\ResolvesStudentProfile;
use App\Models\Student;
use App\Models\StudentExperience;
use App\Support\PrivacyConsent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExperienceController extends Controller
{
    use ResolvesStudentProfile;

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $student = $this->student();
        $validated = $this->validated($request);
        PrivacyConsent::ensure($request, $student);

        $student->experiences()->create($validated);

        return $this->respond($request, $student, 'Experience added.', 201);
    }

    public function update(Request $request, StudentExperience $experience): JsonResponse|RedirectResponse
    {
        $student = $this->ownedBy($experience->student_id);
        $validated = $this->validated($request);
        PrivacyConsent::ensure($request, $student);

        $experience->update($validated);

        return $this->respond($request, $student, 'Experience updated.');
    }

    public function destroy(Request $request, StudentExperience $experience): JsonResponse|RedirectResponse
    {
        $student = $this->ownedBy($experience->student_id);
        $experience->delete();

        return $this->respond($request, $student, 'Experience removed.');
    }

    private function validated(Request $request): array
    {
        $validated = $request->validateWithBag('experience', [
            'organization' => ['required', 'string', 'max:150'],
            'position' => ['required', 'string', 'max:150'],
            'start_date' => ['required', 'date', 'before_or_equal:today'],
            'is_current' => ['nullable', 'boolean'],
            'end_date' => ['exclude_if:is_current,1', 'required', 'date', 'after_or_equal:start_date', 'before_or_equal:today'],
            'description' => ['nullable', 'string', 'max:1000'],
        ], [
            'organization.required' => 'Enter the company or organization.',
            'end_date.required' => 'Enter an end date, or tick "I currently work here".',
            'end_date.after_or_equal' => 'The end date must be after the start date.',
        ]);

        $validated['is_current'] = $request->boolean('is_current');
        $validated['end_date'] = $validated['is_current'] ? null : $validated['end_date'];

        return $validated;
    }

    private function respond(Request $request, Student $student, string $message, int $status = 200): JsonResponse|RedirectResponse
    {
        if (! $request->expectsJson()) {
            return redirect()->to(route('student.profile.edit').'#experience')->with('success', $message);
        }

        return response()->json([
            'message' => $message,
            'html' => view('student.profile.partials.experience-list', [
                'experiences' => $student->experiences()->orderByDesc('is_current')->orderByDesc('start_date')->get(),
            ])->render(),
        ], $status);
    }
}
