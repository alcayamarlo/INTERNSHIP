<?php

namespace App\Http\Controllers;

use App\Enums\CompanyInvitationStatus;
use App\Models\CompanyInvitation;
use App\Rules\StrongPassword;
use App\Services\CompanyInvitationService;
use App\Services\IndustryCatalog;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Guest pages a company reaches from its invitation email: review the prefilled details, add the
 * rest, set a password, and the account is created. They then sign in from the landing page.
 */
class CompanyInvitationController extends Controller
{
    public function __construct(private CompanyInvitationService $invitations, private IndustryCatalog $industries) {}

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
        // Company name, address, email (and phone, when given) come from the invitation the
        // coordinator filled in; the form cannot change them (CompanyInvitationService::accept).
        $invitation = CompanyInvitation::findByToken($token);
        $validated = $request->validate([
            'address' => [$invitation && blank($invitation->address) ? 'required' : 'exclude', 'string', 'max:500'],
            'phone' => [$invitation && blank($invitation->phone) ? 'nullable' : 'exclude', 'string', 'max:20'],
            ...$this->industries->rules(),
            'contact_person' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'url', 'max:255'],
            'password' => ['required', 'string', 'confirmed', new StrongPassword],
        ], [
            'contact_person.required' => 'Enter the name of the person who will manage this account.',
            ...$this->industries->messages(),
        ]);
        // The chosen sector, or what was typed for "Other".
        $validated['industry'] = $this->industries->resolve($validated);
        unset($validated['industry_other']);

        try {
            $user = $this->invitations->accept($token, $validated);
        } catch (ValidationException $exception) {
            // The link stopped working while the form was open: show the explanation page.
            if (array_key_exists('invitation', $exception->errors())) {
                return redirect()->route('company-invitations.show', $token);
            }
            throw $exception;
        }

        // The company signs in itself from the landing page with the password it just chose.
        return redirect()->route('home')
            ->with('company_account_ready', 'Your company account is ready. Sign in to continue.')
            ->with('signin_email', $user->email);
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
