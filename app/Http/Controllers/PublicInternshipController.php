<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Enums\WorkSetup;
use App\Models\Internship;
use Illuminate\Http\Request;

/**
 * Read-only, guest-visible internship listings. Only posting fields are exposed
 * (no student data, no employer contact details, no allowance or internal notes).
 */
class PublicInternshipController extends Controller
{
    public function home()
    {
        $openInternships = $this->publicQuery()->with('requirementsList')->latest()->take(12)->get();

        return view('welcome', compact('openInternships'));
    }

    public function index(Request $request)
    {
        $query = $this->publicQuery();

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhereHas('employer', fn ($employer) => $employer->where('company_name', 'like', "%{$search}%"));
            });
        }

        if ($location = trim((string) $request->query('location'))) {
            $query->where('location', 'like', "%{$location}%");
        }

        $setup = WorkSetup::tryFrom((string) $request->query('setup'));
        if ($setup) {
            $query->where('work_setup', $setup->value);
        }

        $internships = $query->latest()->paginate(9)->withQueryString();

        return view('public.internships.index', [
            'internships' => $internships,
            'setups' => WorkSetup::cases(),
        ]);
    }

    public function show(Internship $internship)
    {
        abort_unless($this->publicQuery()->whereKey($internship->id)->exists(), 404);

        $internship->load('employer', 'requirementsList');

        return view('public.internships.show', compact('internship'));
    }

    /**
     * Entry point behind every "Apply" button. Guests are sent to login (and returned here afterwards
     * through the intended URL); students continue to the real, authenticated apply flow.
     */
    public function apply(Request $request, Internship $internship)
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'))->with('status', 'Please log in to apply.');
        }

        if (! $user->isRole(UserRole::Student)) {
            return redirect()->route('internships.show', $internship)
                ->with('info', 'Only students can apply.');
        }

        return redirect()->route('student.internships.show', $internship);
    }

    private function publicQuery()
    {
        return Internship::query()->open()->with('employer:id,company_name');
    }
}
