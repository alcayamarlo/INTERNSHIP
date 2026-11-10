<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ProficiencyLevel;
use App\Enums\WorkSetup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Internship\StoreInternshipRequest;
use App\Http\Requests\Internship\UpdateInternshipRequest;
use App\Models\DeploymentRequirement;
use App\Models\Internship;
use App\Models\Program;
use App\Services\InternshipService;
use Illuminate\Support\Facades\Auth;

class InternshipController extends Controller
{
    public function __construct(private InternshipService $internshipService) {}

    public function index()
    {
        $employer = Auth::user()->employer;
        $internships = $employer->internships()
            ->withCount('applications')
            // The detail panel: competencies, programs, and the three latest applicants.
            ->with([
                'requirementsList',
                'programs',
                'applications' => fn ($query) => $query->with('student.user')->latest('applied_at')->latest('id')->limit(3),
            ])
            ->latest()
            ->paginate(10);

        // The list page also holds the "Post New Internship" pop-up, so it gets the form's options.
        return view('employer.internships.index', [
            'internships' => $internships,
            // Tab counts cover every posting, not only this page of the list.
            'statusCounts' => $employer->internships()->toBase()
                ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'internship' => new Internship,
            ...$this->formOptions(),
        ]);
    }

    public function create()
    {
        return view('employer.internships.form', [
            'internship' => new Internship,
            ...$this->formOptions(),
        ]);
    }

    public function store(StoreInternshipRequest $request)
    {
        $this->internshipService->create(
            Auth::user()->employer,
            $request->validated(),
            $request
        );

        return redirect()->route('employer.internships.index')
            ->with('success', 'Internship posted successfully.');
    }

    public function edit(Internship $internship)
    {
        $this->authorize('update', $internship);

        return view('employer.internships.form', [
            'internship' => $internship->load('requirementsList', 'programs'),
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateInternshipRequest $request, Internship $internship)
    {
        $this->authorize('update', $internship);

        $this->internshipService->update($internship, $request->validated(), $request);

        return redirect()->route('employer.internships.index')
            ->with('success', 'Internship updated successfully.');
    }

    public function destroy(Internship $internship)
    {
        $this->authorize('delete', $internship);

        $this->internshipService->delete($internship);

        return back()->with('success', 'Internship deleted successfully.');
    }

    public function close(Internship $internship)
    {
        $this->authorize('close', $internship);

        $this->internshipService->close($internship);

        return back()->with('success', 'Internship posting closed.');
    }

    /** Choices for the posting form (create and edit pages, and the pop-up on the list page). */
    private function formOptions(): array
    {
        return [
            'workSetups' => WorkSetup::cases(),
            'levels' => ProficiencyLevel::cases(),
            'programs' => Program::orderBy('name')->get(),
            'onRequestDocuments' => DeploymentRequirement::onRequest()->orderBy('sort_order')->get(['code', 'name']),
        ];
    }
}
