<?php

namespace App\Http\Controllers\Employer;

use App\Enums\ProficiencyLevel;
use App\Enums\WorkSetup;
use App\Http\Controllers\Controller;
use App\Http\Requests\Internship\StoreInternshipRequest;
use App\Http\Requests\Internship\UpdateInternshipRequest;
use App\Models\Internship;
use App\Services\InternshipService;
use Illuminate\Support\Facades\Auth;

class InternshipController extends Controller
{
    public function __construct(private InternshipService $internshipService) {}

    public function index()
    {
        $internships = Auth::user()->employer->internships()
            ->withCount('applications')
            ->latest()
            ->paginate(10);

        return view('employer.internships.index', compact('internships'));
    }

    public function create()
    {
        return view('employer.internships.form', [
            'internship' => new Internship,
            'workSetups' => WorkSetup::cases(),
            'levels' => ProficiencyLevel::cases(),
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
            'internship' => $internship->load('requirementsList'),
            'workSetups' => WorkSetup::cases(),
            'levels' => ProficiencyLevel::cases(),
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
}
