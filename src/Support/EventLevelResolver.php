<?php

namespace Xultech\AuthLogNotification\Support;

use Xultech\AuthLogNotification\Constants\AuthEventLevel;
use Xultech\AuthLogNotification\Enums\AuthEventLevelEnum;

class EventLevelResolver
{
    /**
     * Check if the enum is supported in this environment.
     *
     * @return bool
     */
    public static function enumSupported(): bool
    {
        return version_compare(PHP_VERSION, '8.1.0') >= 0
            && class_exists(AuthEventLevelEnum::class);
    }

    /**
     * Get the human-readable label for an event level.
     *
     * @param string|null $value
     * @return string
     */
    public static function label(?string $value): string
    {
        if (self::enumSupported()) {
            try {
                return AuthEventLevelEnum::from($value)->label();
            } catch (\Throwable) {
                // Fallback to constant
            }
        }

        return AuthEventLevel::label($value);
    }

    /**
     * Get all supported event level values.
     *
     * @return array
     */
    public static function values(): array
    {
        return AuthEventLevel::values();
    }

    /**
     * Check if a value is a valid event level.
     *
     * @param string|null $value
     * @return bool
     */
    public static function isValid(?string $value): bool
    {
        return in_array($value, self::values(), true);
    }
}