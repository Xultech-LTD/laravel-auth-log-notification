<?php

namespace Xultech\AuthLogNotification\Enums;

/**
 * Enum AuthEventLevel
 *
 * Represents supported authentication-related event types
 * such as login, logout, failed, etc.
 */
enum AuthEventLevelEnum: string
{
    case LOGIN             = 'login';
    case LOGOUT            = 'logout';
    case FAILED            = 'failed';
    case PASSWORD_RESET    = 'password_reset';
    case REAUTHENTICATED   = 're-authenticated';

    /**
     * Get a human-readable label for each event type.
     *
     * @return string
     */
    public function label(): string
    {
        return match($this) {
            self::LOGIN            => 'Login',
            self::LOGOUT           => 'Logout',
            self::FAILED           => 'Failed Login',
            self::PASSWORD_RESET   => 'Password Reset',
            self::REAUTHENTICATED  => 'Re-authenticated',
        };
    }
}
