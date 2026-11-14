<?php

namespace App\Http\Controllers;

use App\Enums\CompanyInvitationStatus;
use App\Models\CompanyInvitation;
use App\Rules\StrongPassword;
use App\Services\CompanyInvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Guest pages a company reaches from its invitation email: review the prefilled details, add the
 * rest, set a password, and the account is created and signed in.
 */
class CompanyInvitationController extends Controller
{
    public function __construct(private CompanyInvitationService $invitations) {}

    public function show(string $token)
    {
        $invitation = CompanyInvitation::findByToken($token);
        if (! $invitation) {
            return $this->unavailable(null, 'invalid', 404);
        }

        $this->invitations->markExpiredIfStale($invitation);
        if ($invitation->status !== CompanyInvitationStatus::Sent) {
            return $this->unavailable($invitation, $invitation->status->value, 410);
        }

        if (! $invitation->opened_at) {
            $invitation->update(['opened_at' => now()]);
        }

        return view('company-invitations.accept', ['invitation' => $invitation, 'token' => $token]);
    }

    public function store(Request $request, string $token)
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20'],
            'industry' => ['required', 'string', 'max:255'],
            'contact_person' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'url', 'max:255'],
            'password' => ['required', 'string', 'confirmed', new StrongPassword],
        ], [
            'contact_person.required' => 'Enter the name of the person who will manage this account.',
        ]);

        try {
            $user = $this->invitations->accept($token, $validated);
        } catch (ValidationException $exception) {
            // The link stopped working while the form was open: show the explanation page.
            if (array_key_exists('invitation', $exception->errors())) {
                return redirect()->route('company-invitations.show', $token);
            }
            throw $exception;
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('employer.dashboard')
            ->with('success', 'Welcome to SkillBridge. Your company account is ready.');
    }

    private function unavailable(?CompanyInvitation $invitation, string $reason, int $status)
    {
        $invitation?->loadMissing('invitedBy');

        return response()->view('company-invitations.unavailable', [
            'invitation' => $invitation,
            'reason' => $reason,
        ], $status);
    }
}
