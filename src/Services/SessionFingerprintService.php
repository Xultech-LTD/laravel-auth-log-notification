<?php
namespace Xultech\AuthLogNotification\Services;


class SessionFingerprintService
{
    /**
     * Generate a fingerprint hash from request context.
     *
     * @param mixed $request Laravel Request or null
     * @return string
     */
    public static function generate( $request = null): string
    {
        $request = $request ?? (function_exists('request') ? request() : null);

        if (!$request) {
            return hash('sha256', 'no-request');
        }

        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $acceptLang = $request->header('Accept-Language');
        $platform = php_uname();

        return hash('sha256', $ip . '|' . $userAgent . '|' . $acceptLang . '|' . $platform);
    }

    /**
     * Compare current fingerprint with stored one.
     *
     * @param string $stored
     * @param mixed $request Laravel Request or null
     * @return bool
     */
    public static function matches(string $stored, $request = null): bool
    {
        $request = $request ?? (function_exists('request') ? request() : null);
        $current = self::generate($request);

        return hash_equals($stored, $current);
    }
}