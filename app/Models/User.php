<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Mail\EmailVerificationCodeMail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use App\Services\MailSender;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'avatar',
        'is_active',
        'archived_at',
        'email_notifications',
        'last_login_at',
        'must_change_password',
        'suspended_at',
        'suspended_until',
        'suspension_reason',
        'suspended_by_user_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
        'two_factor_recovery_codes',
        'two_factor_code_hash',
    ];

    // Mirrors the column default so a just-created user is active in memory too.
    protected $attributes = [
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verification_code_expires_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'archived_at' => 'datetime',
            'email_notifications' => 'boolean',
            'must_change_password' => 'boolean',
            'suspended_at' => 'datetime',
            'suspended_until' => 'datetime',
            'last_failed_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'two_factor_code_expires_at' => 'datetime',
            'two_factor_skip_once' => 'boolean',
            'demo_account' => 'boolean',
            'role' => UserRole::class,
            'phone' => 'encrypted', // stored encrypted; not searchable in SQL (App\Support\SensitiveColumns)
        ];
    }

    /** Suspended now: set by an admin and not yet past the end date (no end date means until lifted). */
    public function isSuspended(): bool
    {
        return $this->suspended_at !== null && ($this->suspended_until === null || $this->suspended_until->isFuture());
    }

    /** The message shown on the sign-in screen while suspended. */
    public function suspensionMessage(): string
    {
        $until = $this->suspended_until
            ? 'until '.$this->suspended_until->timezone('Asia/Manila')->format('M d, Y h:i A')
            : 'until an administrator lifts it';

        return 'Your account is suspended '.$until.'. Reason: '.($this->suspension_reason ?: 'Not given').'.';
    }

    /**
     * Verified means an email is on file and its code was confirmed. Imported students start without
     * an email, so they must add and verify one (after setting their password) before using the app.
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->email !== null && $this->email_verified_at !== null;
    }

    /**
     * $user->email_verified (true/false). Computed from email + email_verified_at rather than a
     * second column, so the boolean and the timestamp can never disagree.
     */
    protected function emailVerified(): Attribute
    {
        return Attribute::get(fn (): bool => $this->hasVerifiedEmail());
    }

    protected static function booted(): void
    {
        // A new password, however it was set (reset, first-login change, admin, supervisor setup), ends every other
        // signed-in session of this account. The browser that made the change stays signed in.
        static::updated(function (User $user): void {
            if (! $user->wasChanged('password')) {
                return;
            }
            // A new password also ends every "trust this browser" for the second sign-in step.
            \Illuminate\Support\Facades\DB::table('two_factor_trusted_browsers')->where('user_id', $user->id)->delete();
            if (config('session.driver') !== 'database') {
                return;
            }
            $current = auth()->id() === $user->id && request()->hasSession() ? request()->session()->getId() : null;
            \Illuminate\Support\Facades\DB::table(config('session.table', 'sessions'))
                ->where('user_id', $user->id)
                ->when($current, fn ($query) => $query->where('id', '<>', $current))
                ->delete();
        });

        // A student who switches to a DIFFERENT email must verify the new one: whoever saves it
        // (student profile, admin edit, anything else) drops the old verification and any
        // pending code. Saving the same address (any casing/spacing) changes nothing.
        static::updating(function (User $user): void {
            if (! $user->isDirty('email') || ! $user->isRole(UserRole::Student)) {
                return;
            }

            $old = mb_strtolower(trim((string) $user->getOriginal('email')));
            $new = mb_strtolower(trim((string) $user->email));
            if ($old === $new) {
                return;
            }

            // Skipped when the same save sets a fresh verification on purpose (admin "verify").
            if (! $user->isDirty('email_verified_at')) {
                $user->email_verified_at = null;
            }
            $user->verification_code = null;
            $user->verification_code_expires_at = null;
            $user->verification_attempts = 0;
        });
    }

    /** Students add or change their own email on the verify page until it is verified. */
    public function canSetOwnEmail(): bool
    {
        return $this->isRole(UserRole::Student) && ! $this->hasVerifiedEmail();
    }

    public function setEmailAttribute(?string $value): void
    {
        $this->attributes['email'] = $value === null ? null : mb_strtolower(trim($value));
    }

    /**
     * Returns false when the mail transport fails so callers can show a
     * recoverable message instead of a 500 after the account already exists.
     */
    public function sendEmailVerificationNotification(): bool
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->forceFill([
            'verification_code' => Hash::make($code),
            'verification_code_expires_at' => now()->addMinutes(10),
            'verification_attempts' => 0,
        ])->save();

        return app(MailSender::class)->send(
            $this->email,
            new EmailVerificationCodeMail($code),
            'email_verification'
        );
    }

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function employer(): HasOne
    {
        return $this->hasOne(Employer::class);
    }

    public function coordinator(): HasOne
    {
        return $this->hasOne(Coordinator::class);
    }

    /**
     * What ties this account to OJT records. An account with any of these is archived, never deleted outright,
     * so a placement, evaluation or application is never lost with it.
     *
     * @return list<string>
     */
    public function deletionBlockers(): array
    {
        $found = [];
        $add = function (string $label, int $count) use (&$found): void {
            if ($count > 0) {
                $found[] = "{$count} {$label}";
            }
        };

        if ($this->student) {
            $add('OJT placement(s)', $this->student->placements()->count());
            $add('internship application(s)', $this->student->applications()->count());
            $add('internship invitation(s)', $this->student->invitations()->count());
        }
        if ($this->employer) {
            $add('OJT placement(s)', $this->employer->placements()->count());
            $add('internship posting(s)', $this->employer->internships()->count());
            $add('supervisor account(s)', $this->employer->supervisors()->count());
            $add('internship invitation(s)', $this->employer->invitations()->count());
        }
        $add('supervised intern(s)', \App\Models\Placement::query()->where('supervisor_user_id', $this->id)->count());
        $add('placement(s) validated', \App\Models\Placement::query()->where('validated_by_user_id', $this->id)->count());
        $add('company invitation(s) sent', \App\Models\CompanyInvitation::query()->where('invited_by_user_id', $this->id)->count());

        return $found;
    }

    /** The supervisor profile (company link and job title) of a supervisor account. */
    public function supervisor(): HasOne
    {
        return $this->hasOne(Supervisor::class);
    }

    /** The company this account works for: the HR account's own, or the supervisor's employer. */
    public function companyId(): ?int
    {
        return $this->employer?->id ?? $this->supervisor?->employer_id;
    }

    /** Active, not archived, not suspended: able to sign in and act. */
    public function canActNow(): bool
    {
        return $this->is_active && $this->archived_at === null && ! $this->isSuspended();
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    public function appNotifications(): HasMany
    {
        return $this->hasMany(AppNotification::class);
    }

    public function isRole(UserRole ...$roles): bool
    {
        return in_array($this->role, $roles, true);
    }

    public function dashboardRoute(): string
    {
        return $this->role->dashboardRoute();
    }
}
