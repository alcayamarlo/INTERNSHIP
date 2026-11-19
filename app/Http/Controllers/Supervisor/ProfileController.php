<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Services\FileUploadService;
use App\Support\Avatar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        return view('supervisor.profile.edit', [
            'user' => $user,
            'supervisor' => $user->supervisor()->with('employer')->first(),
            'avatarUrl' => Avatar::url($user->avatar),
            'internsCount' => $user->supervisor ? $user->supervisor->placements()->count() : 0,
            'placements' => $user->supervisor
                ? $user->supervisor->placements()->with(['student.user'])->latest()->limit(6)->get()
                : collect(),
        ]);
    }

    public function updatePicture(Request $request, FileUploadService $fileUpload)
    {
        $validated = $request->validate(['avatar' => FileUploadService::imageRules(true)]);
        $user = Auth::user();
        $previousAvatar = $user->avatar;
        $newAvatar = $fileUpload->uploadImage($validated['avatar'], 'profiles/supervisors', (string) $user->id);

        try {
            $user->update(['avatar' => $newAvatar]);
        } catch (\Throwable $exception) {
            $fileUpload->delete($newAvatar);
            throw $exception;
        }

        if ($previousAvatar && $previousAvatar !== $newAvatar) {
            $fileUpload->delete($previousAvatar);
        }

        return redirect()->route('supervisor.profile.edit')->with('success', 'Profile picture updated.');
    }

    public function deletePicture(FileUploadService $fileUpload)
    {
        $user = Auth::user();
        $previousAvatar = $user->avatar;

        if (! $previousAvatar) {
            return redirect()->route('supervisor.profile.edit')->with('error', 'No profile picture to remove.');
        }

        $user->update(['avatar' => null]);
        $fileUpload->delete($previousAvatar);

        return redirect()->route('supervisor.profile.edit')->with('success', 'Profile picture removed.');
    }
}
