<?php

namespace App\Models;

use App\Enums\UserRole;
use App\Mail\EmailVerificationCodeMail;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
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
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
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
            'role' => UserRole::class,
        ];
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
