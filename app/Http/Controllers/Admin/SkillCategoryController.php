<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSkillCategoryRequest;
use App\Http\Requests\Admin\UpdateSkillCategoryRequest;
use App\Models\SkillCategory;
use Illuminate\Support\Facades\DB;

class SkillCategoryController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', SkillCategory::class);

        return view('admin.skill-categories.index', [
            'categories' => SkillCategory::withCount('skills')->orderBy('name')->paginate(20),
        ]);
    }

    public function create()
    {
        $this->authorize('create', SkillCategory::class);

        return view('admin.skill-categories.form', ['category' => new SkillCategory]);
    }

    public function store(StoreSkillCategoryRequest $request)
    {
        SkillCategory::create($request->validated());

        return redirect()->route('admin.skill-categories.index')->with('success', 'Skill category created.');
    }

    public function edit(SkillCategory $skill_category)
    {
        $this->authorize('update', $skill_category);

        return view('admin.skill-categories.form', ['category' => $skill_category]);
    }

    public function update(UpdateSkillCategoryRequest $request, SkillCategory $skill_category)
    {
        DB::transaction(function () use ($request, $skill_category): void {
            $category = SkillCategory::query()->lockForUpdate()->findOrFail($skill_category->id);
            $category->update($request->validated());

            DB::table('skills')
                ->where('category_id', $category->id)
                ->update(['category' => $category->name, 'updated_at' => now()]);
        });

        return redirect()->route('admin.skill-categories.index')->with('success', 'Skill category updated.');
    }

    public function destroy(SkillCategory $skill_category)
    {
        $this->authorize('delete', $skill_category);

        $deleted = DB::transaction(function () use ($skill_category): bool {
            $category = SkillCategory::query()->lockForUpdate()->findOrFail($skill_category->id);

            if ($category->skills()->exists()) {
                return false;
            }

            $category->delete();

            return true;
        });

        if (! $deleted) {
            return back()->withErrors([
                'category' => 'This category has skills assigned to it. Reassign or delete those skills before deleting the category.',
            ]);
        }

        return redirect()->route('admin.skill-categories.index')->with('success', 'Skill category deleted.');
    }
}
