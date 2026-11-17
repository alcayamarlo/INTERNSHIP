<?php

namespace App\Http\Controllers\Coordinator;

use App\Enums\CompanyInvitationStatus;
use App\Http\Controllers\Controller;
use App\Models\CompanyInvitation;
use App\Services\CompanyInvitationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** Coordinators invite companies that have no account yet, and track their own invitations. */
class CompanyInvitationController extends Controller
{
    public function __construct(private CompanyInvitationService $invitations) {}

    public function index(Request $request)
    {
        $this->authorize('viewAny', CompanyInvitation::class);
        $status = CompanyInvitationStatus::tryFrom((string) $request->query('status'));

        $invitations = CompanyInvitation::query()
            ->where('invited_by_user_id', Auth::id())
            ->when($status, fn ($query) => $query->withCurrentStatus($status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('coordinator.companies.invitations', [
            'invitations' => $invitations,
            'status' => $status,
            'referencePreview' => CompanyInvitationService::nextReferencePreview(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', CompanyInvitation::class);
        $request->merge(['email' => CompanyInvitationService::normalizeEmail((string) $request->input('email'))]);
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'phone' => ['required', 'string', 'max:20'],
        ], [
            'email.unique' => CompanyInvitationService::EMAIL_TAKEN,
        ]);

        // The service re-checks the email (and an open invitation for it) and throws a ValidationException.
        $result = $this->invitations->invite($request->user(), $validated);

        return $this->sentResponse($result, 'sent to');
    }

    public function resend(CompanyInvitation $companyInvitation)
    {
        $this->authorize('resend', $companyInvitation);

        try {
            $result = $this->invitations->resend($companyInvitation, Auth::user());
        } catch (ValidationException $exception) {
            return back()->with('error', collect($exception->errors())->flatten()->first());
        }

        return $this->sentResponse($result, 'resent to');
    }

    /** @param  array{invitation: CompanyInvitation, emailed: bool}  $result */
    private function sentResponse(array $result, string $verb)
    {
        $invitation = $result['invitation'];
        $redirect = redirect()->route('coordinator.companies.invitations.index');

        return $result['emailed']
            ? $redirect->with('success', "Invitation {$invitation->reference} {$verb} {$invitation->email}.")
            : $redirect->with('error', "Invitation {$invitation->reference} was saved, but the email could not be sent. Use Resend to try again.");
    }
}
