<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

final class PlatformAdmin
{
    public static function allows(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return in_array(strtolower(trim($user->email)), self::emails(), true);
    }

    public static function isAdminHost(Request $request): bool
    {
        $adminHost = self::adminHost();

        return $adminHost !== null && $request->getHost() === $adminHost;
    }

    public static function adminUrl(): ?string
    {
        $url = config('rentier.admin_url');

        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        return rtrim($url, '/');
    }

    public static function adminHost(): ?string
    {
        $url = self::adminUrl();

        if ($url === null) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : null;
    }

    /**
     * @return list<string>
     */
    private static function emails(): array
    {
        $configured = config('rentier.platform_admin_emails', []);

        if (! is_array($configured)) {
            return [];
        }

        $emails = array_map(
            static fn (mixed $email): string => is_string($email)
                ? strtolower(trim($email))
                : '',
            $configured,
        );

        return array_values(array_filter($emails));
    }
}
