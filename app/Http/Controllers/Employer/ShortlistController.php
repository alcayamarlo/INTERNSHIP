<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\InternshipEligibility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * A company's private shortlist of candidates. Only students it can see in Browse Students
 * (consent given, findable, eligible) can be added; students are never told.
 */
class ShortlistController extends Controller
{
    public function __construct(private InternshipEligibility $eligibility) {}

    public function store(Request $request, Student $student): JsonResponse|RedirectResponse
    {
        abort_unless($this->eligibility->constrain(Student::query()->discoverable())->whereKey($student->id)->exists(), 404);
        Auth::user()->employer->shortlistedStudents()->syncWithoutDetaching([$student->id]);

        return $this->respond($request, true, 'Added to your shortlist.');
    }

    public function destroy(Request $request, Student $student): JsonResponse|RedirectResponse
    {
        Auth::user()->employer->shortlistedStudents()->detach($student->id);

        return $this->respond($request, false, 'Removed from your shortlist.');
    }

    private function respond(Request $request, bool $shortlisted, string $message): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['shortlisted' => $shortlisted, 'message' => $message]);
        }

        return back()->with('success', $message);
    }
}
