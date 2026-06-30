<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\CoordinatorProfileRequest;
use App\Models\Coordinator;
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
     * Display the coordinator's profile.
     */
    public function edit()
    {
        $coordinator = Auth::user()->coordinator()->firstOrFail();
        $user = Auth::user();
        $coordinatorInfo = session('coordinator_info', []);

        return view('coordinator.profile.edit', [
            'coordinator' => $coordinator,
            'user' => $user,
            'coordinatorInfo' => $coordinatorInfo,
        ]);
    }

    /**
     * Update the coordinator's profile.
     * Handles institution and personal information updates.
     */
    public function update(CoordinatorProfileRequest $request)
    {
        $coordinator = Auth::user()->coordinator()->firstOrFail();
        $user = Auth::user();
        $validated = $request->validated();

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            $this->fileUpload->delete($coordinator->profile_picture ?? null);

            $validated['profile_picture'] = $this->fileUpload->uploadImage(
                $request->file('profile_picture'),
                'profiles/coordinators',
                (string) $coordinator->id
            );
        }

        // Update user information
        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
        ]);

        // Update coordinator information
        $coordinator->update([
            'department' => $validated['department'],
            'office_address' => $validated['office_address'] ?? null,
            'profile_picture' => $validated['profile_picture'] ?? $coordinator->profile_picture,
        ]);

        // Store position info
        session()->put('coordinator_info', [
            'position' => $validated['position'] ?? null,
        ]);

        $this->activityLog->log($user, 'profile_update', ['role' => 'coordinator']);

        return redirect()->route('coordinator.profile.edit')
            ->with('success', 'Profile updated successfully!');
    }

    /**
     * Delete the profile picture.
     */
    public function deleteProfilePicture()
    {
        $coordinator = Auth::user()->coordinator()->firstOrFail();

        if ($coordinator->profile_picture) {
            $this->fileUpload->delete($coordinator->profile_picture);
            $coordinator->update(['profile_picture' => null]);

            return redirect()->route('coordinator.profile.edit')
                ->with('success', 'Profile picture removed successfully!');
        }

        return redirect()->route('coordinator.profile.edit')
            ->with('error', 'No profile picture to delete.');
    }
}
