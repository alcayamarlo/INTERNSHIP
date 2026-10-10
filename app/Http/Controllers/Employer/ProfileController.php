<?php

namespace App\Http\Controllers\Employer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\EmployerProfileRequest;
use App\Models\Employer;
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
     * Display the employer's profile.
     */
    public function edit()
    {
        $user = Auth::user();
        $employer = $user->employer()->firstOrCreate([], [
            'company_name' => $user->name,
            'contact_person' => $user->name,
        ]);
        $employerInfo = session('employer_info', []);
        $address = $this->parseAddress($employer->address);

        return view('employer.profile.edit', [
            'employer' => $employer,
            'user' => $user,
            'employerInfo' => $employerInfo,
            'address' => $address,
        ]);
    }

    /**
     * Update the employer's profile.
     * Handles company and contact information updates.
     */
    public function update(EmployerProfileRequest $request)
    {
        $user = Auth::user();
        $employer = $user->employer()->firstOrCreate([], [
            'company_name' => $user->name,
            'contact_person' => $user->name,
        ]);
        $validated = $request->validated();

        $previousLogo = $employer->logo;
        $newLogo = null;

        if ($request->hasFile('company_logo')) {
            $newLogo = $this->fileUpload->uploadImage(
                $request->file('company_logo'),
                'logos/employers',
                (string) $employer->id
            );
            $validated['logo'] = $newLogo;
        }

        try {
            DB::transaction(function () use ($user, $employer, $validated, $newLogo) {
                $user->update([
                    'name' => $validated['contact_person'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                ]);

                $employer->update([
                    'company_name' => $validated['company_name'],
                    'industry' => $validated['industry'],
                    'description' => $validated['description'] ?? null,
                    'website' => $validated['website'] ?? null,
                    'logo' => $newLogo ?? $employer->logo,
                    'contact_person' => $validated['contact_person'],
                    'address' => "{$validated['street']}, {$validated['city']}, {$validated['province']} {$validated['zip_code']}",
                ]);
                $this->activityLog->log($user, 'profile_update', ['role' => 'employer']);
            });
        } catch (\Throwable $exception) {
            if ($newLogo) {
                $this->fileUpload->delete($newLogo);
            }

            throw $exception;
        }

        if ($previousLogo && $previousLogo !== $newLogo) {
            $this->fileUpload->delete($previousLogo);
        }

        // Store additional fields
        session()->put('employer_info', [
            'company_size' => $validated['company_size'] ?? null,
            'position' => $validated['position'] ?? null,
        ]);

        return redirect()->route('employer.profile.edit')
            ->with('success', 'Company profile updated successfully!');
    }

    /**
     * @return array{street: string, city: string, province: string, zip_code: string}
     */
    private function parseAddress(?string $address): array
    {
        if (! $address) {
            return ['street' => '', 'city' => '', 'province' => '', 'zip_code' => ''];
        }

        if (preg_match('/^(.+),\s*(.+),\s*(.+?)\s+(\S+)$/', $address, $matches)) {
            return [
                'street' => $matches[1],
                'city' => $matches[2],
                'province' => $matches[3],
                'zip_code' => $matches[4],
            ];
        }

        return ['street' => $address, 'city' => '', 'province' => '', 'zip_code' => ''];
    }

    /**
     * Delete the company logo.
     */
    public function deleteCompanyLogo()
    {
        $employer = Auth::user()->employer()->firstOrFail();

        if ($employer->logo) {
            $previousLogo = $employer->logo;
            DB::transaction(function () use ($employer, $previousLogo) {
                $employer->update(['logo' => null]);
            });
            $this->fileUpload->delete($previousLogo);

            return redirect()->route('employer.profile.edit')
                ->with('success', 'Company logo removed successfully!');
        }

        return redirect()->route('employer.profile.edit')
            ->with('error', 'No logo to delete.');
    }
}
