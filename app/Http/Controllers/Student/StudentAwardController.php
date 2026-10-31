<?php

namespace App\Http\Controllers\Student;

use App\Enums\AwardLevel;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\ResolvesStudentProfile;
use App\Models\Student;
use App\Models\StudentAward;
use App\Support\PrivacyConsent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentAwardController extends Controller
{
    use ResolvesStudentProfile;

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $student = $this->student();
        $validated = $this->validated($request);
        PrivacyConsent::ensure($request, $student);

        $student->awards()->create($validated);

        return $this->respond($request, $student, 'Award added.', 201);
    }

    public function update(Request $request, StudentAward $award): JsonResponse|RedirectResponse
    {
        $student = $this->ownedBy($award->student_id);
        // The copied old paragraph keeps its long title until the student shortens it.
        $validated = $this->validated($request, $award->level === AwardLevel::Other);
        PrivacyConsent::ensure($request, $student);

        $award->update($validated);

        return $this->respond($request, $student, 'Award updated.');
    }

    public function destroy(Request $request, StudentAward $award): JsonResponse|RedirectResponse
    {
        $student = $this->ownedBy($award->student_id);
        $award->delete();

        return $this->respond($request, $student, 'Award removed.');
    }

    private function validated(Request $request, bool $allowOther = false): array
    {
        $levels = array_map(fn (AwardLevel $level) => $level->value, $allowOther ? AwardLevel::cases() : AwardLevel::selectable());

        $validated = $request->validateWithBag('awards', [
            'level' => ['required', Rule::in($levels)],
            'title' => ['required', 'string', $allowOther ? 'max:2000' : 'max:150'],
            'event' => ['nullable', 'string', 'max:150'],
            'year' => ['nullable', 'string', 'max:20', 'regex:'.StudentAward::YEAR_PATTERN],
            'detail' => ['nullable', 'string', 'max:150'],
        ], [
            'level.required' => 'Choose the level.',
            'level.in' => 'Choose the level.',
            'title.required' => 'Enter the award, e.g. Dean\'s Lister.',
            'year.regex' => 'Use a year like 2024, 2023-2024, or 2023-present.',
        ]);

        if (filled($validated['year'] ?? null)) {
            $validated['year'] = preg_replace('/\s*[-–]\s*/', '-', strtolower(trim($validated['year'])));
        }

        return $validated;
    }

    private function respond(Request $request, Student $student, string $message, int $status = 200): JsonResponse|RedirectResponse
    {
        if (! $request->expectsJson()) {
            return redirect()->to(route('student.profile.edit').'#academic')->with('success', $message);
        }

        return response()->json([
            'message' => $message,
            'count' => $student->awards()->count(),
            'html' => view('student.profile.partials.award-list', ['awardGroups' => StudentAward::grouped($student->awards()->get())])->render(),
        ], $status);
    }
}
