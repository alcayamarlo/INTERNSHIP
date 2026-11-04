<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Schools on the platform. Only verified institutions can be picked when creating coordinators
 * and students or importing students.
 */
class InstitutionController extends Controller
{
    public function __construct(private ActivityLogService $activityLog) {}

    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));

        return view('admin.institutions.index', [
            'institutions' => Institution::query()
                ->withCount(['students', 'coordinators'])
                ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    public function store(Request $request)
    {
        $institution = Institution::create($this->validated($request));
        $this->activityLog->log(Auth::user(), 'institution_created', ['institution_id' => $institution->id]);

        return back()->with('success', "{$institution->name} was added. Verify it to let coordinators and students be linked to it.");
    }

    public function update(Request $request, Institution $institution)
    {
        $institution->update($this->validated($request, $institution));
        $this->activityLog->log(Auth::user(), 'institution_updated', ['institution_id' => $institution->id]);

        return back()->with('success', 'Institution saved.');
    }

    public function verify(Institution $institution)
    {
        $institution->update(['verified_at' => now(), 'verified_by_user_id' => Auth::id()]);
        $this->activityLog->log(Auth::user(), 'institution_verified', ['institution_id' => $institution->id]);

        return back()->with('success', "{$institution->name} is verified.");
    }

    public function unverify(Institution $institution)
    {
        $institution->update(['verified_at' => null, 'verified_by_user_id' => null]);
        $this->activityLog->log(Auth::user(), 'institution_unverified', ['institution_id' => $institution->id]);

        return back()->with('success', "{$institution->name} is no longer verified. Existing accounts keep working; it can't be picked for new ones.");
    }

    private function validated(Request $request, ?Institution $institution = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('institutions', 'name')->ignore($institution?->id)],
            'address' => ['nullable', 'string', 'max:1000'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:2000'],
        ], [
            'name.unique' => 'An institution with this name already exists.',
        ]);
    }
}
