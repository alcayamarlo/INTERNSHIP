<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Mail\SupervisorAccountMail;
use App\Models\Employer;
use App\Models\Placement;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Supervisors are company staff the HR account creates to run the OJT day to day. The HR account
 * creates the account, assigns interns (any number per supervisor) and can deactivate it, which hands
 * the interns back to HR. The supervisor chooses their own password from an emailed link.
 */
class SupervisorService
{
    public const EMAIL_TAKEN = 'That email already has an account.';

    /** The setup link works for 7 days (config/auth.php, broker "supervisors"). */
    public const BROKER = 'supervisors';

    public function __construct(
        private MailSender $mail,
        private ActivityLogService $activityLog,
        private NotificationService $notifications,
    ) {}

    /**
     * @param  array{name: string, email: string, position: string, phone?: ?string}  $data
     * @return array{supervisor: Supervisor, emailed: bool}
     */
    public function create(Employer $employer, User $creator, array $data): array
    {
        $email = mb_strtolower(trim($data['email']));
        // withTrashed: a soft-deleted account still holds the email in the unique index.
        if (User::withTrashed()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => self::EMAIL_TAKEN]);
        }

        $supervisor = DB::transaction(function () use ($employer, $creator, $data, $email) {
            $user = User::create([
                'name' => trim($data['name']),
                'email' => $email,
                // Nobody knows this: the supervisor sets their own from the emailed link.
                'password' => Str::random(48),
                'role' => UserRole::Supervisor->value,
                'phone' => filled($data['phone'] ?? null) ? trim($data['phone']) : null,
                'must_change_password' => false,
            ]);

            return Supervisor::create([
                'user_id' => $user->id,
                'employer_id' => $employer->id,
                'position' => trim($data['position']),
                'created_by_user_id' => $creator->id,
            ]);
        });

        $this->activityLog->log($creator, 'supervisor_created', ['supervisor_id' => $supervisor->id, 'employer_id' => $employer->id]);

        return ['supervisor' => $supervisor, 'emailed' => $this->sendSetupLink($supervisor)];
    }

    /** A new setup link (the previous one stops working). */
    public function sendSetupLink(Supervisor $supervisor): bool
    {
        $supervisor->loadMissing('user', 'employer');
        $token = Password::broker(self::BROKER)->createToken($supervisor->user);
        $url = route('supervisor.setup', ['token' => $token, 'email' => $supervisor->user->email]);

        return $this->mail->send($supervisor->user->email, new SupervisorAccountMail($supervisor, $url), 'supervisor_account');
    }

    /** Whether the supervisor has not set their password yet. */
    public static function isPending(Supervisor $supervisor): bool
    {
        return $supervisor->user?->email_verified_at === null;
    }

    public function update(Supervisor $supervisor, User $actor, array $data): void
    {
        DB::transaction(function () use ($supervisor, $data) {
            $supervisor->update(['position' => trim($data['position'])]);
            $supervisor->user->update([
                'name' => trim($data['name']),
                'phone' => filled($data['phone'] ?? null) ? trim($data['phone']) : null,
            ]);
            // The name printed on forms for the interns assigned to them follows the account.
            $supervisor->placements()->update(['supervisor_name' => trim($data['name'])]);
        });
        $this->activityLog->log($actor, 'supervisor_updated', ['supervisor_id' => $supervisor->id]);
    }

    /** Deactivating stops sign-in; the interns fall back to HR and come back on reactivation. */
    public function setActive(Supervisor $supervisor, User $actor, bool $active): void
    {
        $supervisor->user->update(['is_active' => $active]);
        $this->activityLog->log($actor, $active ? 'supervisor_reactivated' : 'supervisor_deactivated', ['supervisor_id' => $supervisor->id]);
    }

    /**
     * Assigns one of the company's supervisors to an intern, or clears the assignment (null).
     *
     * @return bool whether anything changed
     */
    public function assign(Employer $employer, Placement $placement, ?int $supervisorUserId, User $actor, bool $notify = true): bool
    {
        if ((int) $placement->employer_id !== (int) $employer->id) {
            throw ValidationException::withMessages(['supervisor_user_id' => 'That intern does not belong to your company.']);
        }
        $supervisor = $supervisorUserId ? $this->companySupervisor($employer, $supervisorUserId) : null;
        if ((int) $placement->supervisor_user_id === (int) $supervisor?->user_id) {
            return false;
        }

        $previous = $placement->supervisorUser;
        $placement->supervisor_user_id = $supervisor?->user_id;
        if ($supervisor) {
            // The printed name follows the assigned supervisor (see OjtDocumentData::defaultSupervisor).
            $placement->supervisor_name = $supervisor->user->name;
        } elseif ($previous && $placement->supervisor_name === $previous->name) {
            $placement->supervisor_name = null;
        }
        $placement->save();

        $this->activityLog->log($actor, $supervisor ? 'supervisor_assigned' : 'supervisor_unassigned', [
            'placement_id' => $placement->id,
            'supervisor_user_id' => $supervisor?->user_id ?? $previous?->id,
        ]);

        if ($supervisor && $notify) {
            $placement->loadMissing('student.user');
            $this->notifications->send(
                $supervisor->user,
                'ojt_update',
                'New intern assigned',
                ($placement->student?->user?->name ?? 'An intern').' was assigned to you. Open the intern to see the time records, weekly reports and evaluations.',
                ['placement_id' => $placement->id]
            );
        }

        return true;
    }

    /**
     * Assigns one supervisor to several interns. Interns that are not the company's are skipped.
     *
     * @param  list<int>  $placementIds
     * @return int how many interns changed
     */
    public function assignMany(Employer $employer, array $placementIds, int $supervisorUserId, User $actor): int
    {
        $supervisor = $this->companySupervisor($employer, $supervisorUserId);
        $changed = 0;
        foreach ($employer->placements()->whereIn('id', $placementIds)->get() as $placement) {
            $changed += (int) $this->assign($employer, $placement, $supervisorUserId, $actor, notify: false);
        }
        if ($changed > 0) {
            $this->notifications->send(
                $supervisor->user,
                'ojt_update',
                'New interns assigned',
                $changed === 1 ? 'An intern was assigned to you.' : "{$changed} interns were assigned to you.",
            );
        }

        return $changed;
    }

    /** An active supervisor of this company, or a validation error. */
    private function companySupervisor(Employer $employer, int $userId): Supervisor
    {
        $supervisor = $employer->supervisors()->where('user_id', $userId)->with('user')->first();
        if (! $supervisor) {
            throw ValidationException::withMessages(['supervisor_user_id' => 'Choose one of your company\'s supervisors.']);
        }
        if (! $supervisor->user->canActNow()) {
            throw ValidationException::withMessages(['supervisor_user_id' => 'That supervisor is deactivated. Reactivate them first.']);
        }

        return $supervisor;
    }
}
