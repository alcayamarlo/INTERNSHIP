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
    public function home(Request $request)
    {
        // Anyone who is already signed in (admin, coordinator, employer, student) goes straight to their own dashboard.
        if ($user = $request->user()) {
            return redirect()->route($user->dashboardRoute());
        }

        $openInternships = $this->publicQuery()->with('requirementsList')->latest()->take(12)->get();

        return view('welcome', compact('openInternships'));
    }

    public function index(Request $request)
    {
        // The standalone page is retired: the internships list opens as a popup on the landing page.
        // The popup itself still loads this list in the background (an AJAX request), so only direct visits are redirected.
        if (! $request->ajax()) {
            return redirect()->to(route('home').'#internships');
        }

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

        $internships = $query->latest()->paginate(100)->withQueryString();

        return view('public.internships.index', [
            'internships' => $internships,
            'setups' => WorkSetup::cases(),
        ]);
    }

    /**
     * The details page is no longer a standalone page: visitors are sent to the internships popup on the landing page.
     * It is still rendered for the in-page details popup, which loads it with an XHR request and reads
     * the description, requirements and facts out of it.
     */
    public function show(Request $request, Internship $internship)
    {
        if (! $request->ajax()) {
            return redirect()->to(route('home').'#internships');
        }

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
            return redirect()->to(route('home').'#internships')
                ->with('info', 'Only students can apply.');
        }

        return redirect()->route('student.internships.show', $internship);
    }

    private function publicQuery()
    {
        return Internship::query()->open()->with('employer:id,company_name');
    }
}
