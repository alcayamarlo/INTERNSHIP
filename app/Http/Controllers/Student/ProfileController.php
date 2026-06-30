<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\StudentProfileRequest;
use App\Models\Student;
use App\Services\ActivityLogService;
use App\Services\FileUploadService;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function __construct(
        private ActivityLogService $activityLog,
        private FileUploadService $fileUpload
    ) {}
    /**
     * Display the student's profile.
     */
    public function edit()
    {
        $student = Auth::user()->student()->firstOrFail();
        $user = Auth::user();
        $careerInfo = session('student_career_info', []);

        return view('student.profile.edit', [
            'student' => $student,
            'user' => $user,
            'careerInfo' => $careerInfo,
        ]);
    }

    /**
     * Update the student's profile.
     * Handles personal, contact, academic, career, and profile picture updates.
     */
    public function update(StudentProfileRequest $request)
    {
        $student = Auth::user()->student()->firstOrFail();
        $user = Auth::user();
        $validated = $request->validated();

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            $this->fileUpload->delete($student->profile_picture);

            $validated['profile_picture'] = $this->fileUpload->uploadImage(
                $request->file('profile_picture'),
                'profiles/students',
                (string) $student->id
            );
        }

        // Update user information
        $user->update([
            'name' => "{$validated['first_name']} {$validated['last_name']}",
            'email' => $validated['email'],
            'phone' => $validated['phone'],
        ]);

        // Update student information
        $student->update([
            'profile_picture' => $validated['profile_picture'] ?? $student->profile_picture,
            'address' => $validated['address'],
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'student_id_number' => $validated['student_number'] ?? null,
            'program' => $validated['program'] ?? null,
            'year_level' => $validated['year_level'] ?? null,
            'career_objectives' => $validated['career_objectives'] ?? null,
        ]);

        // Store additional fields in session or attributes if needed
        session()->put('student_career_info', [
            'preferred_internship_field' => $validated['preferred_internship_field'] ?? null,
            'preferred_work_setup' => $validated['preferred_work_setup'] ?? null,
            'preferred_location' => $validated['preferred_location'] ?? null,
            'expected_graduation' => $validated['expected_graduation'] ?? null,
        ]);

        // Recalculate profile completion
        $student->calculateProfileCompletion();

        $this->activityLog->log($user, 'profile_update', ['role' => 'student']);

        return redirect()->route('student.profile.edit')
            ->with('success', 'Profile updated successfully!');
    }

    /**
     * Delete the profile picture.
     */
    public function deleteProfilePicture()
    {
        $student = Auth::user()->student()->firstOrFail();

        if ($student->profile_picture) {
            $this->fileUpload->delete($student->profile_picture);
            $student->update(['profile_picture' => null]);

            return redirect()->route('student.profile.edit')
                ->with('success', 'Profile picture removed successfully!');
        }

        return redirect()->route('student.profile.edit')
            ->with('error', 'No profile picture to delete.');
    }
}
