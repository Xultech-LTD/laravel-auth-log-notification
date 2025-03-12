<?php

namespace Xultech\AuthLogNotification\Constants;

/**
 * Class AuthEventLevel
 *
 * PHP 8.0-compatible version of the event level enumeration.
 * Defines standardized event types and human-readable labels.
 */

class AuthEventLevel
{
    public const LOGIN             = 'login';
    public const LOGOUT            = 'logout';
    public const FAILED            = 'failed';
    public const PASSWORD_RESET    = 'password_reset';
    public const REAUTHENTICATED   = 're-authenticated';

    /**
     * Get a human-readable label for a given event level.
     *
     * @param string|null $value
     * @return string
     */
    public static function label(?string $value): string
    {
        return match ($value) {
            self::LOGIN            => 'Login',
            self::LOGOUT           => 'Logout',
            self::FAILED           => 'Failed Login',
            self::PASSWORD_RESET   => 'Password Reset',
            self::REAUTHENTICATED  => 'Re-authenticated',
            default                => ucfirst($value ?? 'Unknown'),
        };
    }

    /**
     * Get all supported event level values.
     *
     * @return array
     */
    public static function values(): array
    {
        return [
            self::LOGIN,
            self::LOGOUT,
            self::FAILED,
            self::PASSWORD_RESET,
            self::REAUTHENTICATED,
        ];
    }
}