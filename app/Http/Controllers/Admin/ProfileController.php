<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FileUploadService;
use App\Support\PublicFileUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function edit()
    {
        return view('admin.profile.edit', [
            'user' => Auth::user(),
            'avatarUrl' => PublicFileUrl::relative(Auth::user()->avatar),
        ]);
    }

    public function updatePicture(Request $request, FileUploadService $fileUpload)
    {
        $validated = $request->validate([
            'avatar' => FileUploadService::imageRules(true),
        ]);
        $user = Auth::user();
        $previousAvatar = $user->avatar;
        $newAvatar = $fileUpload->uploadImage(
            $validated['avatar'],
            'profiles/admins',
            (string) $user->id,
        );

        try {
            $user->update(['avatar' => $newAvatar]);
        } catch (\Throwable $exception) {
            $fileUpload->delete($newAvatar);
            throw $exception;
        }

        if ($previousAvatar && $previousAvatar !== $newAvatar) {
            $fileUpload->delete($previousAvatar);
        }

        return redirect()->route('admin.profile.edit')->with('success', 'Profile picture updated.');
    }
}
