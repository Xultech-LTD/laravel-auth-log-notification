<?php

namespace Xultech\AuthLogNotification\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Xultech\AuthLogNotification\Constants\AuthEventLevel;

/**
 * Class AuthLogUserScopes
 *
 * Dynamically registers reusable query scopes on any Eloquent model
 * that uses the HasAuthLogs trait (e.g., User model).
 *
 * These scopes allow simple querying of login activity patterns
 * such as suspicious activity, multiple sessions, or recent logins.
 */
class AuthLogUserScopes
{
    /**
     * Register the custom query scopes (macros).
     */
    public static function register(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Users who logged in today
        |--------------------------------------------------------------------------
        */
        Builder::macro('loggedInToday', function () {
            /** @var Builder $this */
            return $this->whereHas('authentications', function ($query) {
                $query->where('event_level', AuthEventLevel::LOGIN)
                    ->whereDate('login_at', Carbon::today());
            });
        });

        /*
        |--------------------------------------------------------------------------
        | Users who have at least one failed login attempt
        |--------------------------------------------------------------------------
        */
        Builder::macro('withFailedLogins', function () {
            /** @var Builder $this */
            return $this->whereHas('authentications', function ($query) {
                $query->where('event_level', AuthEventLevel::FAILED);
            });
        });

        /*
        |--------------------------------------------------------------------------
        | Users flagged with suspicious login activity
        |--------------------------------------------------------------------------
        | This includes logins from new devices or new locations.
        */
        Builder::macro('suspiciousActivity', function () {
            /** @var Builder $this */
            return $this->whereHas('authentications', function ($query) {
                $query->where(function ($q) {
                    $q->where('is_new_device', true)
                        ->orWhere('is_new_location', true);
                });
            });
        });

        /*
        |--------------------------------------------------------------------------
        | Users with multiple active sessions
        |--------------------------------------------------------------------------
        | Active = logged in with no logout timestamp.
        */
        Builder::macro('multipleSessions', function () {
            /** @var Builder $this */
            return $this->whereHas('authentications', function ($query) {
                $query->where('event_level', AuthEventLevel::LOGIN)
                    ->whereNull('logout_at');
            }, '>=', 2);
        });

        /*
        |--------------------------------------------------------------------------
        | Users who have been inactive for X days
        |--------------------------------------------------------------------------
        | Defaults to users who haven't logged in for 30+ days.
        */
        Builder::macro('inactiveSince', function ($days = 30) {
            /** @var Builder $this */
            $cutoff = Carbon::now()->subDays($days);

            return $this->whereDoesntHave('authentications', function ($query) use ($cutoff) {
                $query->where('event_level', AuthEventLevel::LOGIN)
                    ->where('login_at', '>=', $cutoff);
            });
        });
    }
}