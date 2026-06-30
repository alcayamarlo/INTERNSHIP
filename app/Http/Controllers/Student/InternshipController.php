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

    public function applications()
    {
        $applications = InternshipApplication::with('internship.employer')
            ->where('student_id', Auth::user()->student->id)
            ->latest('applied_at')
            ->paginate(10);

        return view('student.internships.applications', compact('applications'));
    }
}
