<?php

namespace App\Services;

use App\Enums\CompanyInvitationStatus;
use App\Enums\UserRole;
use App\Mail\CompanyInvitationMail;
use App\Models\CompanyInvitation;
use App\Models\Employer;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Coordinator invitations for companies without an account. The raw link token exists only in the
 * email; the database keeps its SHA-256 hash. Resending replaces the hash, so older links stop working.
 */
class CompanyInvitationService
{
    public const EMAIL_TAKEN = 'An account with this email already exists. Sign in instead, or ask your coordinator.';

    public const EMAIL_INVITED = 'This company already has an open invitation. Resend it from the Invitations list instead.';

    public const LINK_UNAVAILABLE = 'This invitation link is no longer valid. Ask your coordinator to send a new one.';

    public function __construct(
        private MailSender $mail,
        private NotificationService $notifications,
        private ActivityLogService $activityLog,
    ) {}

    /**
     * @param  array{company_name: string, address?: ?string, email: string, phone?: ?string}  $data
     * @return array{invitation: CompanyInvitation, emailed: bool}
     */
    public function invite(User $coordinator, array $data): array
    {
        $email = self::normalizeEmail($data['email']);
        $this->ensureEmailUnused($email);
        if (CompanyInvitation::query()->open()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => self::EMAIL_INVITED]);
        }

        $token = self::newToken();
        $invitation = DB::transaction(function () use ($coordinator, $data, $email, $token) {
            $invitation = CompanyInvitation::create([
                'invited_by_user_id' => $coordinator->id,
                'company_name' => trim($data['company_name']),
                'address' => filled($data['address'] ?? null) ? trim($data['address']) : null,
                'email' => $email,
                'phone' => filled($data['phone'] ?? null) ? trim($data['phone']) : null,
                'token_hash' => CompanyInvitation::hashToken($token),
                'status' => CompanyInvitationStatus::Sent,
                'expires_at' => $this->newExpiry(),
            ]);
            $invitation->update(['reference' => self::referenceFor($invitation)]);

            return $invitation;
        });

        $this->activityLog->log($coordinator, 'company_invitation_sent', ['company_invitation_id' => $invitation->id]);

        return ['invitation' => $invitation, 'emailed' => $this->sendEmail($invitation, $token)];
    }

    /**
     * New link and expiry for a sent or expired invitation; the previous link stops working at once.
     *
     * @return array{invitation: CompanyInvitation, emailed: bool}
     */
    public function resend(CompanyInvitation $invitation, User $actor): array
    {
        if ($invitation->status === CompanyInvitationStatus::Accepted) {
            throw ValidationException::withMessages(['invitation' => 'This company already registered.']);
        }
        $this->ensureEmailUnused($invitation->email);

        $token = self::newToken();
        $invitation->update([
            'token_hash' => CompanyInvitation::hashToken($token),
            'status' => CompanyInvitationStatus::Sent,
            'expires_at' => $this->newExpiry(),
            'opened_at' => null,
        ]);

        $this->activityLog->log($actor, 'company_invitation_resent', ['company_invitation_id' => $invitation->id]);

        return ['invitation' => $invitation, 'emailed' => $this->sendEmail($invitation, $token)];
    }

    /**
     * Creates the verified employer account from an open invitation, in one transaction.
     *
     * @param  array{company_name: string, address: string, phone?: ?string, industry: string, contact_person: string, description?: ?string, website?: ?string, password: string}  $data
     */
    public function accept(string $token, array $data): User
    {
        $found = CompanyInvitation::findByToken($token);
        if (! $found) {
            throw ValidationException::withMessages(['invitation' => self::LINK_UNAVAILABLE]);
        }

        [$user, $invitation] = DB::transaction(function () use ($found, $data) {
            // Lock the row so two submissions of the same link cannot both create an account.
            $invitation = CompanyInvitation::query()->lockForUpdate()->findOrFail($found->id);
            if (! $invitation->isOpen()) {
                throw ValidationException::withMessages(['invitation' => self::LINK_UNAVAILABLE]);
            }
            $this->ensureEmailUnused($invitation->email);

            $user = User::create([
                'name' => trim($data['contact_person']),
                'email' => $invitation->email,
                'password' => $data['password'],
                'role' => UserRole::Employer->value,
                'phone' => filled($data['phone'] ?? null) ? trim($data['phone']) : null,
                'must_change_password' => false,
            ]);
            // The email is proven by the invitation link, so no verification code is needed.
            $user->forceFill(['email_verified_at' => now()])->save();

            $employer = Employer::create([
                'user_id' => $user->id,
                'company_name' => trim($data['company_name']),
                'industry' => trim($data['industry']),
                'address' => trim($data['address']),
                'contact_person' => trim($data['contact_person']),
                'description' => filled($data['description'] ?? null) ? trim($data['description']) : null,
                'website' => filled($data['website'] ?? null) ? trim($data['website']) : null,
                'verified_at' => now(),
                'verified_by_user_id' => $this->verifierFor($invitation)?->id,
            ]);

            $invitation->update([
                'status' => CompanyInvitationStatus::Accepted,
                'accepted_at' => now(),
                'employer_id' => $employer->id,
            ]);

            $this->activityLog->log($user, 'company_invitation_accepted', [
                'company_invitation_id' => $invitation->id,
                'employer_id' => $employer->id,
            ]);

            return [$user, $invitation];
        });

        foreach ($this->recipientsFor($invitation) as $recipient) {
            $this->notifications->send(
                $recipient,
                'company_invitation',
                'Company registered',
                "{$invitation->company_name} accepted your invitation ({$invitation->reference}) and is now a registered partner company.",
                ['employer_id' => $invitation->employer_id, 'company_invitation_id' => $invitation->id]
            );
        }

        return $user;
    }

    /** Marks sent invitations past their expiry as expired. Returns how many changed. */
    public function expireStale(): int
    {
        return CompanyInvitation::query()
            ->where('status', CompanyInvitationStatus::Sent->value)
            ->where('expires_at', '<=', now())
            ->update(['status' => CompanyInvitationStatus::Expired->value, 'updated_at' => now()]);
    }

    public function markExpiredIfStale(CompanyInvitation $invitation): void
    {
        if ($invitation->status === CompanyInvitationStatus::Sent && ! $invitation->expires_at->isFuture()) {
            $invitation->update(['status' => CompanyInvitationStatus::Expired]);
        }
    }

    /** Preview of the next reference for the invite form; the saved one is built from the real id. */
    public static function nextReferencePreview(): string
    {
        return 'CI-'.now()->format('Y').'-'.str_pad((string) (((int) CompanyInvitation::max('id')) + 1), 5, '0', STR_PAD_LEFT);
    }

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    private static function referenceFor(CompanyInvitation $invitation): string
    {
        // Same style as PlacementService::certificateNumber: built from the id, so it cannot collide.
        return 'CI-'.$invitation->created_at->format('Y').'-'.str_pad((string) $invitation->id, 5, '0', STR_PAD_LEFT);
    }

    private static function newToken(): string
    {
        return Str::random(64);
    }

    private function newExpiry(): Carbon
    {
        return now()->addDays(max(1, (int) config('company_invitations.expires_after_days', 14)));
    }

    private function ensureEmailUnused(string $email): void
    {
        // withTrashed: a soft-deleted account still holds the email in the unique index.
        if (User::withTrashed()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => self::EMAIL_TAKEN]);
        }
    }

    private function sendEmail(CompanyInvitation $invitation, string $token): bool
    {
        $invitation->loadMissing('invitedBy');

        return $this->mail->send(
            $invitation->email,
            new CompanyInvitationMail($invitation, route('company-invitations.show', $token)),
            'company_invitation'
        );
    }

    private function inviterIsActive(CompanyInvitation $invitation): bool
    {
        $inviter = $invitation->invitedBy;

        return $inviter !== null
            && $inviter->is_active
            && $inviter->archived_at === null
            && ! $inviter->isSuspended();
    }

    /** The inviting coordinator, or an active admin if that coordinator is no longer active. */
    private function verifierFor(CompanyInvitation $invitation): ?User
    {
        $invitation->loadMissing('invitedBy');

        return $this->inviterIsActive($invitation) ? $invitation->invitedBy : $this->activeAdmins()->first();
    }

    /** @return Collection<int, User> */
    private function recipientsFor(CompanyInvitation $invitation): Collection
    {
        $invitation->loadMissing('invitedBy');

        return $this->inviterIsActive($invitation) ? collect([$invitation->invitedBy]) : $this->activeAdmins();
    }

    /** @return Collection<int, User> */
    private function activeAdmins(): Collection
    {
        return User::query()
            ->where('role', UserRole::Admin->value)
            ->where('is_active', true)
            ->whereNull('archived_at')
            ->orderBy('id')
            ->get()
            ->reject(fn (User $admin) => $admin->isSuspended())
            ->values();
    }
}
