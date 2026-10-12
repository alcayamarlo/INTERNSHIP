<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeploymentRequirement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DeploymentRequirementController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'upload_source' => ['required', Rule::in([
                DeploymentRequirement::SOURCE_STUDENT,
                DeploymentRequirement::SOURCE_COORDINATOR,
            ])],
        ]);
        $code = Str::slug($validated['name']);
        if ($code === '') {
            throw ValidationException::withMessages(['name' => 'Enter a name that can be used as a document identifier.']);
        }
        if (DeploymentRequirement::query()->where('code', $code)->exists()) {
            throw ValidationException::withMessages(['name' => 'A document with this name already exists.']);
        }

        DeploymentRequirement::query()->create([
            ...$validated,
            'code' => $code,
            'sort_order' => ((int) DeploymentRequirement::query()->max('sort_order')) + 1,
            'is_active' => true,
        ]);

        return back()->with('success', 'Deployment requirement added.');
    }

    public function update(Request $request, DeploymentRequirement $deploymentRequirement): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'upload_source' => ['required', Rule::in([
                DeploymentRequirement::SOURCE_STUDENT,
                DeploymentRequirement::SOURCE_COORDINATOR,
                DeploymentRequirement::SOURCE_SYSTEM,
            ])],
            'sort_order' => ['required', 'integer', 'between:1,65535'],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($deploymentRequirement->code === 'resume'
            && $validated['upload_source'] !== DeploymentRequirement::SOURCE_SYSTEM) {
            throw ValidationException::withMessages([
                'upload_source' => 'The Resume requirement must remain system-generated.',
            ]);
        }
        if ($deploymentRequirement->code !== 'resume'
            && $validated['upload_source'] === DeploymentRequirement::SOURCE_SYSTEM) {
            throw ValidationException::withMessages([
                'upload_source' => 'System-generated documents must use the Resume requirement.',
            ]);
        }

        if ($validated['upload_source'] !== $deploymentRequirement->upload_source
            && $deploymentRequirement->portfolios()->exists()) {
            throw ValidationException::withMessages([
                'upload_source' => 'The upload source cannot change after files have been submitted for this requirement.',
            ]);
        }

        $deploymentRequirement->update($validated);

        return back()->with('success', 'Deployment requirement updated.');
    }

    public function destroy(DeploymentRequirement $deploymentRequirement): RedirectResponse
    {
        if ($deploymentRequirement->portfolios()->exists()) {
            throw ValidationException::withMessages([
                'deployment_requirement' => 'This requirement has submitted files. Deactivate it instead of deleting it.',
            ]);
        }

        $deploymentRequirement->delete();

        return back()->with('success', 'Deployment requirement deleted.');
    }
}
