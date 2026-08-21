<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Application\ApplyInternshipRequest;
use App\Models\Internship;
use App\Models\InternshipApplication;
use App\Services\ApplicationService;
use App\Services\CompetencyMatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class InternshipController extends Controller
{
    public function __construct(
        private CompetencyMatchingService $matchingService,
        private ApplicationService $applicationService
    ) {}

    public function index(Request $request)
    {
        $student = Auth::user()->student;
        $query = Internship::query()->open()->with('employer', 'requirementsList');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($location = $request->get('location')) {
            $query->where('location', 'like', "%{$location}%");
        }

        if ($company = $request->get('company')) {
            $query->whereHas('employer', fn ($q) => $q->where('company_name', 'like', "%{$company}%"));
        }

        if ($skill = $request->get('skill')) {
            $query->whereHas('requirementsList', fn ($q) => $q->where('requirement_name', 'like', "%{$skill}%"));
        }

        $internships = $query->latest()->paginate(12)->withQueryString();
        $recommendations = $this->matchingService->getRecommendations($student, 3);

        return view('student.internships.index', compact('internships', 'recommendations'));
    }

    public function show(Internship $internship)
    {
        $internship->load('employer', 'requirementsList');
        $student = Auth::user()->student;
        $matchPercentage = $this->matchingService->calculateMatch($student, $internship);
        $hasApplied = InternshipApplication::where('internship_id', $internship->id)
            ->where('student_id', $student->id)
            ->exists();

        return view('student.internships.show', compact('internship', 'matchPercentage', 'hasApplied'));
    }

    public function apply(ApplyInternshipRequest $request, Internship $internship)
    {
        try {
            $this->applicationService->apply(
                Auth::user()->student,
                $internship,
                Auth::user(),
                $request->validated('cover_letter')
            );
        } catch (ValidationException $e) {
            return back()->with('error', collect($e->errors())->flatten()->first());
        }

        return redirect()->route('student.applications.index')
            ->with('success', 'Application submitted successfully.');
    }

    public function applications(Request $request)
    {
        $query = InternshipApplication::with('internship.employer')
            ->where('student_id', Auth::user()->student->id);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }
        if ($request->filled('job_title')) {
            $query->whereHas('internship', fn ($q) => $q->where('title', 'like', '%'.$request->job_title.'%'));
        }
        if ($request->filled('company')) {
            $query->whereHas('internship.employer', fn ($q) => $q->where('company_name', 'like', '%'.$request->company.'%'));
        }
        if ($request->filled('location')) {
            $query->whereHas('internship', fn ($q) => $q->where('location', 'like', '%'.$request->location.'%'));
        }
        if ($request->filled('applied_from')) {
            $query->whereDate('applied_at', '>=', $request->applied_from);
        }
        if ($request->filled('applied_to')) {
            $query->whereDate('applied_at', '<=', $request->applied_to);
        }

        $applications = $query
            ->latest('applied_at')
            ->paginate(10)->withQueryString();

        return view('student.internships.applications', compact('applications'));
    }

    public function showApplication(InternshipApplication $application)
    {
        $this->authorize('view', $application);
        $application->load('internship.employer');
        return view('student.internships.application-show', compact('application'));
    }

    public function editApplication(InternshipApplication $application)
    {
        $this->authorize('view', $application);
        return view('student.internships.application-edit', compact('application'));
    }

    public function updateApplication(ApplyInternshipRequest $request, InternshipApplication $application)
    {
        $this->authorize('view', $application);
        abort_unless($application->status === \App\Enums\ApplicationStatus::Submitted, 422, 'Only submitted applications can be edited.');
        $application->update(['cover_letter' => $request->validated('cover_letter')]);
        return redirect()->route('student.applications.show', $application)->with('success', 'Application updated successfully.');
    }

    public function destroyApplication(InternshipApplication $application)
    {
        $this->authorize('view', $application);
        abort_unless($application->status === \App\Enums\ApplicationStatus::Submitted, 422, 'Only submitted applications can be deleted.');
        $application->delete();
        return redirect()->route('student.applications.index')->with('success', 'Application deleted successfully.');
    }
}
