<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\CoordinatorProfileRequest;
use App\Models\Coordinator;
use App\Services\ActivityLogService;
use App\Services\FileUploadService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $user = Auth::user();
        $coordinator = $user->coordinator()->firstOrCreate([]);
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
        $user = Auth::user();
        $coordinator = $user->coordinator()->firstOrCreate([]);
        $validated = $request->validated();

        $previousPicture = $coordinator->profile_picture;
        $newPicture = null;

        if ($request->hasFile('profile_picture')) {
            $newPicture = $this->fileUpload->uploadImage(
                $request->file('profile_picture'),
                'profiles/coordinators',
                (string) $coordinator->id
            );
            $validated['profile_picture'] = $newPicture;
        }

        try {
            DB::transaction(function () use ($user, $coordinator, $validated, $newPicture) {
                $user->update([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                ]);

                $coordinator->update([
                    'department' => $validated['department'],
                    'office_address' => $validated['office_address'] ?? null,
                    'profile_picture' => $newPicture ?? $coordinator->profile_picture,
                ]);
                $this->activityLog->log($user, 'profile_update', ['role' => 'coordinator']);
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

        // Store position info
        session()->put('coordinator_info', [
            'position' => $validated['position'] ?? null,
        ]);

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
            $previousPicture = $coordinator->profile_picture;
            DB::transaction(function () use ($coordinator, $previousPicture) {
                $coordinator->update(['profile_picture' => null]);
            });
            $this->fileUpload->delete($previousPicture);

            return redirect()->route('coordinator.profile.edit')
                ->with('success', 'Profile picture removed successfully!');
        }

        return redirect()->route('coordinator.profile.edit')
            ->with('error', 'No profile picture to delete.');
    }
}
