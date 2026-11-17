<?php

namespace App\Http\Controllers\Coordinator;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\CoordinatorProfileRequest;
use App\Models\Coordinator;
use App\Services\ActivityLogService;
use App\Services\FileUploadService;
use App\Support\Avatar;
use Illuminate\Http\Request;
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

        // Only a picture that was actually replaced is deleted; saving the profile without a new picture keeps it.
        if ($newPicture && $previousPicture && $previousPicture !== $newPicture) {
            $this->fileUpload->delete($previousPicture);
        }

        // Store position info
        session()->put('coordinator_info', [
            'position' => $validated['position'] ?? null,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully!',
                'profile_picture_url' => Avatar::url($coordinator->fresh()->profile_picture),
            ]);
        }

        return redirect()->route('coordinator.profile.edit')
            ->with('success', 'Profile updated successfully!');
    }

    /**
     * Upload or replace only the profile picture (the Upload Photo button). It does not send the
     * rest of the profile, so an unfinished profile cannot block the picture.
     */
    public function updatePicture(Request $request)
    {
        $request->validate(['profile_picture' => FileUploadService::imageRules(true)]);
        $user = Auth::user();
        $coordinator = $user->coordinator()->firstOrCreate([]);

        $previousPicture = $coordinator->profile_picture;
        $newPicture = $this->fileUpload->uploadImage($request->file('profile_picture'), 'profiles/coordinators', (string) $coordinator->id);

        try {
            $coordinator->update(['profile_picture' => $newPicture]);
        } catch (\Throwable $exception) {
            $this->fileUpload->delete($newPicture);

            throw $exception;
        }

        if ($previousPicture && $previousPicture !== $newPicture) {
            $this->fileUpload->delete($previousPicture);
        }
        $this->activityLog->log($user, 'profile_update', ['role' => 'coordinator', 'field' => 'profile_picture']);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Profile picture updated.',
                'profile_picture_url' => Avatar::url($newPicture),
            ]);
        }

        return redirect()->route('coordinator.profile.edit')->with('success', 'Profile picture updated.');
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
