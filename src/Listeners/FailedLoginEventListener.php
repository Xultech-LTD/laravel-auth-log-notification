<?php

namespace Xultech\AuthLogNotification\Listeners;

use Carbon\Carbon;
use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Xultech\AuthLogNotification\Models\AuthLog;
use Xultech\AuthLogNotification\Services\GeoLocation\GeoLocationService;
use Xultech\AuthLogNotification\Services\LoginRateLimiter;
use Xultech\AuthLogNotification\Support\EventLevelResolver;
use Xultech\AuthLogNotification\Support\HookExecutor;

/**
 * Listener to capture and log failed login attempts.
 */
class FailedLoginEventListener
{
    /**
     * Handle the failed login event.
     *
     * @param  Failed  $event
     * @return void
     */
    public function handle(Failed $event): void
    {
        // Check if failed login tracking is enabled
        if (! Config::get('authlog.enabled') || ! Config::get('authlog.log_events.failed_login', true)) {
            return;
        }

        $request = Request::instance();

        $ip = $request->getClientIp();
        $userAgent = $request->userAgent();
        $referrer = $request->headers->get('referer');
        $credentials = $event->credentials;
        $email = $credentials['email'] ?? null;

        $location = App::make(GeoLocationService::class)->getGeoData($ip);

        // Log to auth_logs table
        AuthLog::create([
            'authenticatable_type' => null,
            'authenticatable_id' => null,
            'ip_address' => $ip,
            'country' => $location['country'] ?? null,
            'city' => $location['city'] ?? null,
            'location' => $location['location'] ?? null,
            'browser' => $location['browser'] ?? null,
            'platform' => $location['platform'] ?? null,
            'device' => $location['device'] ?? null,
            'is_mobile' => $location['is_mobile'] ?? false,
            'user_agent' => $userAgent,
            'referrer' => $referrer,
            'metadata' => $location,
            'event_level' => EventLevelResolver::resolve('failed'),
            'login_at' => Carbon::now(),
            'session_id' => null,
        ]);

        // Resolve and register with rate limiter
        $limiter = App::make(LoginRateLimiter::class);
        $identifier = $limiter->resolveIdentifier($request, $email);
        $limiter->registerFailure($identifier);


        // Trigger user-defined hook
        App::make(HookExecutor::class)->run('on_failed', [
            'email' => $email,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'request' => $request,
        ]);
    }
}
