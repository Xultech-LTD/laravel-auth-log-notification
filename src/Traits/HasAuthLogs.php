<?php

namespace Xultech\AuthLogNotification\Traits;


use Xultech\AuthLogNotification\Models\AuthLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;
use Xultech\AuthLogNotification\Constants\AuthEventLevel;

/**
 * Trait HasAuthLogs
 *
 * Provides authentication log helpers for any model that uses it (e.g., User).
 * Must be used on a model that supports Laravel’s authentication and notifications.
 *
 * @package Xultech\AuthLogNotification\Traits
 */

trait HasAuthLogs
{
    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get all authentication logs for this model, ordered from newest to oldest.
     *
     * @return MorphMany
     */
    public function authentications(): MorphMany
    {
        return $this->morphMany(AuthLog::class, 'authenticatable')->latest('login_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Login Time Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Get the timestamp of the most recent successful login.
     *
     * @return Carbon|null
     */
    public function lastLoginAt(): ?Carbon
    {
        return $this->authentications()
            ->where('event_level', AuthEventLevel::LOGIN)
            ->value('login_at');
    }

    /**
     * Get the timestamp of the login before the most recent one.
     *
     * @return Carbon|null
     */
    public function previousLoginAt(): ?Carbon
    {
        return $this->authentications()
            ->where('event_level', AuthEventLevel::LOGIN)
            ->skip(1)->take(1)
            ->value('login_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Login IP Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Get the IP address of the last login.
     *
     * @return string|null
     */
    public function lastLoginIp(): ?string
    {
        return $this->authentications()
            ->where('event_level', AuthEventLevel::LOGIN)
            ->value('ip_address');
    }

    /**
     * Get the IP address of the previous login.
     *
     * @return string|null
     */
    public function previousLoginIp(): ?string
    {
        return $this->authentications()
            ->where('event_level', AuthEventLevel::LOGIN)
            ->skip(1)->take(1)
            ->value('ip_address');
    }

    /*
    |--------------------------------------------------------------------------
    | Login History & Queries
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the model has any successful login history.
     *
     * @return bool
     */
    public function hasLoggedInBefore(): bool
    {
        return $this->authentications()
            ->where('event_level', AuthEventLevel::LOGIN)
            ->exists();
    }

    /**
     * Get the latest AuthLog model for a successful login.
     *
     * @return AuthLog|null
     */
    public function latestLogin(): ?AuthLog
    {
        return $this->authentications()
            ->where('event_level', AuthEventLevel::LOGIN)
            ->first();
    }

    /**
     * Get the latest successful login event (same as latestLogin).
     *
     * @return AuthLog|null
     */
    public function latestSuccessfulLogin(): ?AuthLog
    {
        return $this->latestLogin();
    }

    /**
     * Get a limited number of recent login logs.
     *
     * @param int $count
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function logins(int $count = 5)
    {
        return $this->authentications()
            ->where('event_level', AuthEventLevel::LOGIN)
            ->take($count)
            ->get();
    }

    /**
     * Get all login logs from today.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function loginsToday()
    {
        return $this->authentications()
            ->where('event_level', AuthEventLevel::LOGIN)
            ->whereDate('login_at', now()->toDateString())
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Failed & Suspicious Logins
    |--------------------------------------------------------------------------
    */

    /**
     * Get all failed login attempts for this model.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function failedLogins()
    {
        return $this->authentications()
            ->where('event_level', AuthEventLevel::FAILED)
            ->get();
    }

    /**
     * Count the number of failed login attempts.
     *
     * @return int
     */
    public function failedLoginsCount(): int
    {

        return $this->authentications()
            ->where('event_level', AuthEventLevel::FAILED)
            ->count();
    }

    /**
     * Get all suspicious login attempts (new device or location).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function suspiciousLogins()
    {
        return $this->authentications()
            ->where(function ($query) {
                $query->where('is_new_device', true)
                    ->orWhere('is_new_location', true);
            })->get();
    }

    /**
     * Determine if the last login was flagged as suspicious.
     *
     * @return bool
     */
    public function lastLoginWasSuspicious(): bool
    {
        $lastLog = $this->latestLogin();

        return $lastLog && (($lastLog->is_new_device || $lastLog->is_new_location));
    }

    /**
     * Check if the last login was from a specific IP address.
     *
     * @param string $ip
     * @return bool
     */
    public function lastLoginWasFromIp(string $ip): bool
    {
        return $this->lastLoginIp() === $ip;
    }

    /*
    |--------------------------------------------------------------------------
    | Last Known Location & Device
    |--------------------------------------------------------------------------
    */

    /**
     * Get a formatted string of the last known location.
     *
     * @return string|null
     */
    public function lastKnownLocation(): ?string
    {
        $last = $this->latestLogin();
        return $last?->formatted_location;
    }

    /**
     * Get the last known device summary.
     *
     * @return string|null
     */
    public function lastKnownDevice(): ?string
    {
        $last = $this->latestLogin();
        return $last?->device_summary;
    }

    /*
    |--------------------------------------------------------------------------
    | Session Awareness
    |--------------------------------------------------------------------------
    */

    /**
     * Get all active login sessions (not yet logged out).
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function activeSessions()
    {
        return $this->authentications()
            ->where('event_level', AuthEventLevel::LOGIN)
            ->whereNull('logout_at')
            ->get();
    }

    /**
     * Determine if the model has more than one active session.
     *
     * @return bool
     */
    public function hasMultipleSessions(): bool
    {
        return $this->activeSessions()->count() > 1;
    }

    /**
     * Determine whether the user should receive a login notification.
     *
     * @param \Xultech\AuthLogNotification\Models\AuthLog $log
     * @return bool
     */
    public function shouldReceiveLoginNotification(AuthLog $log): bool
    {
        $onlySuspicious = config('authlog.notification.only_on_suspicious_activity', true);

        if (! $onlySuspicious) {
            return true;
        }

        return $log->is_suspicious;
    }

}