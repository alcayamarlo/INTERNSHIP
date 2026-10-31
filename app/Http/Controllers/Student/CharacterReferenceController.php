<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\ResolvesStudentProfile;
use App\Models\CharacterReference;
use App\Models\Student;
use App\Support\PrivacyConsent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CharacterReferenceController extends Controller
{
    use ResolvesStudentProfile;

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $student = $this->student();
        $validated = $this->validated($request);
        PrivacyConsent::ensure($request, $student);

        if ($student->characterReferences()->count() >= CharacterReference::MAX_PER_STUDENT) {
            throw ValidationException::withMessages([
                'name' => 'You can list up to '.CharacterReference::MAX_PER_STUDENT.' character references.',
            ])->errorBag('references');
        }

        $student->characterReferences()->create($validated);

        return $this->respond($request, $student, 'Reference added.', 201);
    }

    public function update(Request $request, CharacterReference $reference): JsonResponse|RedirectResponse
    {
        $student = $this->ownedBy($reference->student_id);
        $validated = $this->validated($request);
        PrivacyConsent::ensure($request, $student);

        $reference->update($validated);

        return $this->respond($request, $student, 'Reference updated.');
    }

    public function destroy(Request $request, CharacterReference $reference): JsonResponse|RedirectResponse
    {
        $student = $this->ownedBy($reference->student_id);
        $reference->delete();

        return $this->respond($request, $student, 'Reference removed.');
    }

    private function validated(Request $request): array
    {
        if ($request->filled('contact_number')) {
            $request->merge(['contact_number' => preg_replace('/[\s().-]+/', '', (string) $request->input('contact_number'))]);
        }

        return $request->validateWithBag('references', [
            'name' => ['required', 'string', 'max:150'],
            'position' => ['required', 'string', 'max:150'],
            'organization' => ['required', 'string', 'max:150'],
            'contact_number' => ['required', 'regex:/^(\+?\d{1,3}[-.\s]?)?\d{7,14}$/'],
        ], [
            'organization.required' => 'Enter the company or school.',
            'contact_number.regex' => 'Please enter a valid phone number.',
        ]);
    }

    private function respond(Request $request, Student $student, string $message, int $status = 200): JsonResponse|RedirectResponse
    {
        $student->calculateProfileCompletion();

        if (! $request->expectsJson()) {
            return redirect()->to(route('student.profile.edit').'#references')->with('success', $message);
        }

        $references = $student->characterReferences()->orderBy('id')->get();

        return response()->json([
            'message' => $message,
            'count' => $references->count(),
            'max' => CharacterReference::MAX_PER_STUDENT,
            'html' => view('student.profile.partials.reference-list', ['references' => $references])->render(),
            ...ProfileController::completionPayload($student->profileCompletionSummary()),
        ], $status);
    }
}
