<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SkillTag;
use App\Models\StudentSkill;
use App\Services\ActivityLogService;
use App\Support\CatalogName;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * The student skill tag list (the autocomplete behind the profile Skills tab): rename, merge
 * duplicates into one tag, or delete a tag. Merge and delete ask for confirmation first.
 */
class SkillTagController extends Controller
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $sort = $request->query('sort') === 'name' ? 'name' : 'usage';

        return view('admin.skill-tags.index', [
            'tags' => SkillTag::query()
                ->withCount('studentSkills')
                ->when($search !== '', fn ($query) => $query->where('name_normalized', 'like', '%'.CatalogName::normalized($search).'%'))
                ->when($sort === 'usage', fn ($query) => $query->orderByDesc('student_skills_count'))
                ->orderBy('name')
                ->paginate(30)
                ->withQueryString(),
            'allTags' => SkillTag::query()->orderBy('name')->get(['id', 'name']),
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    public function update(Request $request, SkillTag $skillTag)
    {
        $validated = $request->validate(['name' => ['required', 'string', 'max:60']]);
        $normalized = CatalogName::normalized($validated['name']);
        $existing = SkillTag::query()->where('name_normalized', $normalized)->whereKeyNot($skillTag->id)->first();
        if ($existing) {
            return back()->with('error', "\"{$existing->name}\" already exists. Merge \"{$skillTag->name}\" into it instead.");
        }

        $old = $skillTag->name;
        $skillTag->update(['name' => $validated['name']]);
        $this->activityLog->log(Auth::user(), 'skill_tag_renamed', ['skill_tag_id' => $skillTag->id, 'from' => $old, 'to' => $skillTag->name]);

        return back()->with('success', "Renamed \"{$old}\" to \"{$skillTag->name}\".");
    }

    /** Moves every student from this tag to the target tag, then removes this tag. */
    public function merge(Request $request, SkillTag $skillTag)
    {
        $validated = $request->validate([
            'target_id' => ['required', 'integer', Rule::exists('skill_tags', 'id'), Rule::notIn([$skillTag->id])],
        ], [
            'target_id.not_in' => 'Choose a different tag to merge into.',
            'target_id.required' => 'Choose the tag to keep.',
        ]);
        $target = SkillTag::findOrFail($validated['target_id']);

        $moved = DB::transaction(function () use ($skillTag, $target) {
            $moved = 0;
            foreach (StudentSkill::query()->where('skill_tag_id', $skillTag->id)->lockForUpdate()->get() as $studentSkill) {
                $duplicate = StudentSkill::query()->where('student_id', $studentSkill->student_id)->where('skill_tag_id', $target->id)->first();
                if ($duplicate) {
                    // The student already has the kept tag: keep its level unless only the merged one had a level.
                    if ($duplicate->level === null && $studentSkill->level !== null) {
                        $duplicate->update(['level' => $studentSkill->level]);
                    }
                    $studentSkill->delete();
                } else {
                    $studentSkill->update(['skill_tag_id' => $target->id]);
                    $moved++;
                }
            }
            $skillTag->delete();

            return $moved;
        });

        $this->activityLog->log(Auth::user(), 'skill_tags_merged', ['from_id' => $skillTag->id, 'from' => $skillTag->name, 'into_id' => $target->id, 'into' => $target->name, 'students_moved' => $moved]);

        return back()->with('success', "Merged \"{$skillTag->name}\" into \"{$target->name}\".");
    }

    public function destroy(SkillTag $skillTag)
    {
        $removed = DB::transaction(function () use ($skillTag) {
            $removed = StudentSkill::query()->where('skill_tag_id', $skillTag->id)->delete();
            $skillTag->delete();

            return $removed;
        });
        $this->activityLog->log(Auth::user(), 'skill_tag_deleted', ['skill_tag_id' => $skillTag->id, 'name' => $skillTag->name, 'removed_from_students' => $removed]);

        return back()->with('success', "Deleted \"{$skillTag->name}\"".($removed ? " and removed it from {$removed} student profile".($removed === 1 ? '' : 's') : '').'.');
    }
}
