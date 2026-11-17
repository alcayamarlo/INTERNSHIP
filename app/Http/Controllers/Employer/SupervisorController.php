<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Models\Placement;
use App\Models\Supervisor;
use App\Services\SupervisorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * HR / Company: create the company's supervisors, edit or deactivate them, and assign interns to them.
 * Everything here is limited to the signed-in company's own supervisors and interns.
 */
class SupervisorController extends Controller
{
    public function __construct(private SupervisorService $supervisors) {}

    public function index()
    {
        $employer = Auth::user()->employer;
        abort_unless($employer, 403);

        $supervisors = $employer->supervisors()->with('user')->withCount('placements')->get()
            ->sortBy(fn (Supervisor $supervisor) => mb_strtolower((string) $supervisor->user?->name))->values();

        return view('employer.supervisors.index', compact('supervisors'));
    }

    public function store(Request $request): RedirectResponse
    {
        $employer = Auth::user()->employer;
        abort_unless($employer, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'position' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
        ], [
            'name.required' => 'Enter the supervisor\'s full name.',
            'position.required' => 'Enter the supervisor\'s job title; it is printed on the OJT forms.',
        ]);

        $result = $this->supervisors->create($employer, $request->user(), $validated);

        return redirect()->route('employer.supervisors.index')->with(
            $result['emailed'] ? 'success' : 'error',
            $result['emailed']
                ? "Supervisor account created. A link to set the password was emailed to {$result['supervisor']->user->email}."
                : 'The account was created, but the email could not be sent. Use "Resend setup link" in a moment.'
        );
    }

    public function update(Request $request, Supervisor $supervisor): RedirectResponse
    {
        $this->ownSupervisor($supervisor);
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $this->supervisors->update($supervisor, $request->user(), $validated);

        return back()->with('success', 'Supervisor updated.');
    }

    /** Deactivate or reactivate. Deactivating hands the interns back to the HR account until reactivated. */
    public function status(Request $request, Supervisor $supervisor): RedirectResponse
    {
        $this->ownSupervisor($supervisor);
        $active = ! $supervisor->user->is_active;
        $this->supervisors->setActive($supervisor, $request->user(), $active);

        return back()->with('success', $active
            ? 'Supervisor reactivated. Their interns are back with them.'
            : 'Supervisor deactivated. They can no longer sign in, and their interns are handled by your HR account until you reactivate them.');
    }

    public function resend(Request $request, Supervisor $supervisor): RedirectResponse
    {
        $this->ownSupervisor($supervisor);
        if (! SupervisorService::isPending($supervisor)) {
            return back()->with('error', 'This supervisor already set a password.');
        }

        $sent = $this->supervisors->sendSetupLink($supervisor);

        return back()->with(
            $sent ? 'success' : 'error',
            $sent ? 'A new setup link was emailed. The previous link no longer works.' : 'The email could not be sent. Try again in a moment.'
        );
    }

    /** Assign (or clear) the supervisor of one intern. */
    public function assign(Request $request, Placement $placement): RedirectResponse
    {
        $employer = Auth::user()->employer;
        abort_unless($employer && (int) $placement->employer_id === (int) $employer->id, 403);
        $validated = $request->validate(['supervisor_user_id' => ['nullable', 'integer']]);

        $changed = $this->supervisors->assign($employer, $placement, $validated['supervisor_user_id'] ?? null, $request->user());

        return back()->with('success', $changed ? 'Supervisor updated for this intern.' : 'Nothing changed.');
    }

    /** Assign one supervisor to several interns at once. */
    public function assignMany(Request $request): RedirectResponse
    {
        $employer = Auth::user()->employer;
        abort_unless($employer, 403);
        $validated = $request->validate([
            'placements' => ['required', 'array', 'min:1', 'max:100'],
            'placements.*' => ['integer'],
            'supervisor_user_id' => ['required', 'integer'],
        ], [
            'placements.required' => 'Tick at least one intern.',
            'supervisor_user_id.required' => 'Choose a supervisor.',
        ]);

        $changed = $this->supervisors->assignMany($employer, array_map('intval', $validated['placements']), (int) $validated['supervisor_user_id'], $request->user());

        return back()->with('success', $changed > 0 ? "Supervisor assigned to {$changed} intern(s)." : 'Nothing changed: those interns already have this supervisor.');
    }

    private function ownSupervisor(Supervisor $supervisor): void
    {
        $employer = Auth::user()->employer;
        abort_unless($employer && (int) $supervisor->employer_id === (int) $employer->id, 404);
        $supervisor->loadMissing('user');
    }
}
