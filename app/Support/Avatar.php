<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * The signed-in user's avatar for the profile header, top bar, and sidebar card: the photo URL
 * (with ?v= so a new upload shows at once), or null when there is none or the file is missing,
 * in which case the initials are shown.
 */
final class Avatar
{
    /** @return array{url: ?string, initials: string} */
    public static function for(?User $user): array
    {
        $path = match ($user?->role) {
            UserRole::Student => $user->student?->profile_picture,
            UserRole::Coordinator => $user->coordinator?->profile_picture,
            UserRole::Employer => $user->employer?->logo,
            UserRole::Admin => $user->avatar,
            default => null,
        };

        return ['url' => self::url($path), 'initials' => self::initials($user?->name)];
    }

    public static function url(?string $path): ?string
    {
        $url = PublicFileUrl::relative($path);
        if ($url === null) {
            return null;
        }

        try {
            $version = Storage::disk(UploadDisks::public())->lastModified($path);
        } catch (\Throwable) {
            $version = null;
        }

        return $version ? $url.(str_contains($url, '?') ? '&' : '?').'v='.$version : $url;
    }

    public static function initials(?string $name): string
    {
        $initials = collect(preg_split('/\s+/u', trim((string) $name)) ?: [])
            ->filter()
            ->map(fn (string $part) => mb_substr($part, 0, 1))
            ->take(2)
            ->implode('');

        return mb_strtoupper($initials ?: 'U');
    }
}
