<?php

namespace Xultech\AuthLogNotification\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles rate limiting for login attempts with support for IP/email-based lockout.
 */
class LoginRateLimiter
{
    /**
     * Check if the user has been locked out.
     *
     * @param string $identifier
     * @return bool
     */
    public static function isLockedOut(string $identifier): bool
    {
        return Cache::has(self::getLockKey($identifier));
    }

    /**
     * Register a failed login attempt and trigger lockout if needed.
     *
     * @param string $identifier
     */
    public static function registerFailure(string $identifier): void
    {
        $config = Config::get('authlog.lockout');
        $attemptKey = self::getAttemptKey($identifier);
        $lockKey = self::getLockKey($identifier);

        // Increment attempts and store timestamp
        $attempts = Cache::increment($attemptKey);
        Cache::put($attemptKey . ':timestamp', now(), now()->addMinutes($config['lockout_minutes']));

        // If max attempts exceeded, lock the user out
        if ($attempts >= $config['max_attempts']) {
            Cache::put($lockKey, true, now()->addMinutes($config['lockout_minutes']));
        }
    }

    /**
     * Clear attempts and lockout flags.
     *
     * @param string $identifier
     */
    public static function clear(string $identifier): void
    {
        Cache::forget(self::getAttemptKey($identifier));
        Cache::forget(self::getAttemptKey($identifier) . ':timestamp');
        Cache::forget(self::getLockKey($identifier));
    }

    /**
     * Get the number of failed attempts.
     *
     * @param string $identifier
     * @return int
     */
    public static function attempts(string $identifier): int
    {
        return Cache::get(self::getAttemptKey($identifier), 0);
    }

    /**
     * Get how long until the lockout expires (in seconds).
     *
     * @param string $identifier
     * @return int|null
     */
    public static function secondsRemaining(string $identifier): ?int
    {
        $expiresAt = Cache::get(self::getAttemptKey($identifier) . ':timestamp');

        if (! $expiresAt instanceof Carbon) {
            return null;
        }

        return max(0, Carbon::now()->diffInSeconds($expiresAt));
    }

    /**
     * Resolve the identifier to use (IP, email, or both).
     *
     * @param Request $request
     * @param string|null $email
     * @return string
     */
    public static function resolveIdentifier(Request $request, ?string $email = null): string
    {
        $type = Config::get('authlog.lockout.track_by', 'ip');

        return match ($type) {
            'email' => $email ?? 'unknown',
            'both'  => ($email ?? 'unknown') . '|' . $request->getClientIp(),
            default => $request->getClientIp(),
        };
    }

    /**
     * Return the cache key for tracking attempts.
     *
     * @param string $identifier
     * @return string
     */
    protected static function getAttemptKey(string $identifier): string
    {
        $prefix = Config::get('authlog.lockout.key_prefix', 'authlog:lockout:');
        return $prefix . 'attempts:' . sha1($identifier);
    }

    /**
     * Return the cache key for the lockout flag.
     *
     * @param string $identifier
     * @return string
     */
    protected static function getLockKey(string $identifier): string
    {
        $prefix = Config::get('authlog.lockout.key_prefix', 'authlog:lockout:');
        return $prefix . 'locked:' . sha1($identifier);
    }
}
