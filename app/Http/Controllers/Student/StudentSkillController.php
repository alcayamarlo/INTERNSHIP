<?php

namespace App\Http\Controllers\Student;

use App\Enums\SkillLevel;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Student\Concerns\ResolvesStudentProfile;
use App\Models\SkillTag;
use App\Models\Student;
use App\Models\StudentSkill;
use App\Support\CatalogName;
use App\Support\PrivacyConsent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StudentSkillController extends Controller
{
    use ResolvesStudentProfile;

    private const SUGGESTION_LIMIT = 8;

    public function suggest(Request $request): JsonResponse
    {
        $term = str_replace(['%', '_'], '', CatalogName::normalized((string) $request->query('q', '')));
        if ($term === '') {
            return response()->json(['suggestions' => []]);
        }

        $ownedSkillIds = $this->student()->studentSkills()->pluck('skill_tag_id');
        $base = fn () => SkillTag::query()->whereNotIn('id', $ownedSkillIds)->orderBy('name');
        $suggestions = $base()->where('name_normalized', 'like', $term.'%')->limit(self::SUGGESTION_LIMIT)->pluck('name');
        if ($suggestions->count() < self::SUGGESTION_LIMIT) {
            $suggestions = $suggestions->concat(
                $base()->where('name_normalized', 'like', '%'.$term.'%')
                    ->whereNotIn('name', $suggestions)
                    ->limit(self::SUGGESTION_LIMIT - $suggestions->count())
                    ->pluck('name')
            );
        }

        return response()->json(['suggestions' => $suggestions->values()]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $request->merge(['name' => CatalogName::display((string) $request->input('name'))]);
        $validated = $request->validateWithBag('skills', [
            'name' => ['required', 'string', 'max:60'],
            'level' => ['nullable', Rule::enum(SkillLevel::class)],
        ], [
            'name.required' => 'Type a skill to add.',
            'name.max' => 'Keep each skill under 60 characters.',
        ]);
        $student = $this->student();
        PrivacyConsent::ensure($request, $student);

        $studentSkill = DB::transaction(function () use ($student, $validated): StudentSkill {
            Student::query()->whereKey($student->id)->lockForUpdate()->first();

            if ($student->studentSkills()->count() >= StudentSkill::MAX_PER_STUDENT) {
                throw ValidationException::withMessages([
                    'name' => 'You can add up to '.StudentSkill::MAX_PER_STUDENT.' skills. Remove one to add another.',
                ])->errorBag('skills');
            }

            $skill = SkillTag::query()->firstOrCreate(
                ['name_normalized' => CatalogName::normalized($validated['name'])],
                ['name' => $validated['name']]
            );

            if ($student->studentSkills()->where('skill_tag_id', $skill->id)->exists()) {
                throw ValidationException::withMessages([
                    'name' => '"'.$skill->name.'" is already in your skills.',
                ])->errorBag('skills');
            }

            return $student->studentSkills()->create([
                'skill_tag_id' => $skill->id,
                'level' => $validated['level'] ?? null,
            ])->setRelation('skill', $skill);
        });

        return $this->respond($request, $student, 'Skill added.', $studentSkill, 201);
    }

    public function update(Request $request, StudentSkill $studentSkill): JsonResponse|RedirectResponse
    {
        $student = $this->ownedBy($studentSkill->student_id);
        $validated = $request->validateWithBag('skills', [
            'level' => ['nullable', Rule::enum(SkillLevel::class)],
        ]);
        PrivacyConsent::ensure($request, $student);

        $studentSkill->update(['level' => $validated['level'] ?? null]);

        return $this->respond($request, $student, 'Skill level updated.', $studentSkill->load('skill'));
    }

    public function destroy(Request $request, StudentSkill $studentSkill): JsonResponse|RedirectResponse
    {
        $student = $this->ownedBy($studentSkill->student_id);
        $studentSkill->delete();

        return $this->respond($request, $student, 'Skill removed.');
    }

    private function respond(Request $request, Student $student, string $message, ?StudentSkill $studentSkill = null, int $status = 200): JsonResponse|RedirectResponse
    {
        $student->calculateProfileCompletion();

        if (! $request->expectsJson()) {
            return redirect()->to(route('student.profile.edit').'#skills')->with('success', $message);
        }

        return response()->json([
            'message' => $message,
            'skill' => $studentSkill?->toChip($student->certifiedCompetencyNames()),
            'count' => $student->studentSkills()->count(),
            'max' => StudentSkill::MAX_PER_STUDENT,
            ...ProfileController::completionPayload($student->profileCompletionSummary()),
        ], $status);
    }
}
