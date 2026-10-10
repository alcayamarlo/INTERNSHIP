<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\StudentProfileRequest;
use App\Models\Student;
use App\Services\ActivityLogService;
use App\Services\FileUploadService;
use App\Services\ResumeBuilderService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLog,
        private FileUploadService $fileUpload,
        private ResumeBuilderService $resumeBuilder
    ) {}
    /**
     * Display the student's profile.
     */
    public function edit()
    {
        $student = Auth::user()->student()->firstOrCreate([], ['profile_completion' => 0]);
        $user = Auth::user();
        return view('student.profile.edit', [
            'student' => $student,
            'user' => $user,
            'careerInfo' => $student->only(['preferred_internship_field', 'preferred_work_setup', 'preferred_location', 'expected_graduation']),
        ]);
    }

    /**
     * Update the student's profile.
     * Handles personal, contact, academic, career, and profile picture updates.
     */
    public function update(StudentProfileRequest $request)
    {
        $student = Auth::user()->student()->firstOrCreate([], ['profile_completion' => 0]);
        $user = Auth::user();
        $validated = $request->validated();

        $previousPicture = $student->profile_picture;
        $newPicture = null;

        if ($request->hasFile('profile_picture')) {
            $newPicture = $this->fileUpload->uploadImage(
                $request->file('profile_picture'),
                'profiles/students',
                (string) $student->id
            );
            $validated['profile_picture'] = $newPicture;
        }

        try {
            DB::transaction(function () use ($validated, $user, $student, $newPicture) {
                $user->update([
                    'name' => trim(implode(' ', array_filter([$validated['first_name'], $validated['middle_name'] ?? null, $validated['last_name'], $validated['suffix'] ?? null]))),
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                ]);

                $student->update([
                    'profile_picture' => $newPicture ?? $student->profile_picture,
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'],
                    'address' => $validated['address'],
                    'date_of_birth' => $validated['date_of_birth'] ?? null,
                    'student_id_number' => $validated['student_number'] ?? null,
                    'program' => $validated['program'] ?? null,
                    'year_level' => $validated['year_level'] ?? null,
                    'career_objectives' => $validated['career_objectives'] ?? null,
                    'middle_name' => $validated['middle_name'] ?? null,
                    'suffix' => $validated['suffix'] ?? null,
                    'gender' => $validated['gender'] ?? null,
                    'city' => $validated['city'] ?? null,
                    'province' => $validated['province'] ?? null,
                    'zip_code' => $validated['zip_code'] ?? null,
                    'department' => $validated['department'] ?? null,
                    'expected_graduation' => $validated['expected_graduation'] ?? null,
                    'preferred_internship_field' => $validated['preferred_internship_field'] ?? null,
                    'preferred_work_setup' => $validated['preferred_work_setup'] ?? null,
                    'preferred_location' => $validated['preferred_location'] ?? null,
                ]);

                $student->calculateProfileCompletion();
                $this->activityLog->log($user, 'profile_update', ['role' => 'student']);
            });
        } catch (\Throwable $exception) {
            if ($newPicture) {
                $this->fileUpload->delete($newPicture);
            }

            throw $exception;
        }

        if ($previousPicture && $previousPicture !== $newPicture) {
            $this->fileUpload->delete($previousPicture);
        }

        if ($student->fresh()->profile_completion === 100 && ! $student->resumes()->exists()) {
            try {
                $this->resumeBuilder->build($student->fresh());
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        return redirect()->route('student.profile.edit')
            ->with('success', 'Profile updated successfully!');
    }

    public function updatePicture(\Illuminate\Http\Request $request)
    {
        $request->validate(['profile_picture' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:2048']]);
        $student = Auth::user()->student()->firstOrFail();
        $previousPicture = $student->profile_picture;
        $newPicture = $this->fileUpload->uploadImage($request->file('profile_picture'), 'profiles/students', (string) $student->id);

        try {
            DB::transaction(function () use ($student, $newPicture, $previousPicture) {
                $student->update(['profile_picture' => $newPicture]);
            });
        } catch (\Throwable $exception) {
            $this->fileUpload->delete($newPicture);
            throw $exception;
        }

        if ($previousPicture) {
            $this->fileUpload->delete($previousPicture);
        }

        return back()->with('success', 'Profile picture updated successfully.');
    }

    /**
     * Delete the profile picture.
     */
    public function deleteProfilePicture()
    {
        $student = Auth::user()->student()->firstOrFail();

        if ($student->profile_picture) {
            $previousPicture = $student->profile_picture;
            DB::transaction(function () use ($student, $previousPicture) {
                $student->update(['profile_picture' => null]);
            });
            $this->fileUpload->delete($previousPicture);

            return redirect()->route('student.profile.edit')
                ->with('success', 'Profile picture removed successfully!');
        }

        return redirect()->route('student.profile.edit')
            ->with('error', 'No profile picture to delete.');
    }
}
