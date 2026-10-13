<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSkillRequest;
use App\Http\Requests\Admin\UpdateSkillRequest;
use App\Models\Skill;
use App\Models\SkillCategory;
use Illuminate\Support\Facades\DB;

class SkillController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Skill::class);

        return view('employer.skills.index', [
            'skills' => Skill::with('skillCategory')
                ->withCount('internshipRequirements')
                ->orderBy('name')
                ->paginate(20),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Skill::class);

        return view('employer.skills.form', [
            'skill' => new Skill,
            'categories' => SkillCategory::orderBy('name')->get(),
        ]);
    }

    public function store(StoreSkillRequest $request)
    {
        Skill::create($request->validated());

        return redirect()->route('employer.skills.index')->with('success', 'Skill created.');
    }

    public function edit(Skill $skill)
    {
        $this->authorize('update', $skill);

        return view('employer.skills.form', [
            'skill' => $skill,
            'categories' => SkillCategory::orderBy('name')->get(),
        ]);
    }

    public function update(UpdateSkillRequest $request, Skill $skill)
    {
        $skill->update($request->validated());

        return redirect()->route('employer.skills.index')->with('success', 'Skill updated.');
    }

    public function destroy(Skill $skill)
    {
        $this->authorize('delete', $skill);

        $deleted = DB::transaction(function () use ($skill): bool {
            $lockedSkill = Skill::query()->lockForUpdate()->findOrFail($skill->id);

            if ($lockedSkill->internshipRequirements()->exists()) {
                return false;
            }

            $lockedSkill->delete();

            return true;
        });

        if (! $deleted) {
            return back()->withErrors([
                'skill' => 'This skill is used by one or more internship requirements. Remove those references before deleting it.',
            ]);
        }

        return redirect()->route('employer.skills.index')->with('success', 'Skill deleted.');
    }
}
