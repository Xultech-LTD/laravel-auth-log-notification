<?php

namespace Xultech\AuthLogNotification\Listeners;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Request;
use Xultech\AuthLogNotification\Models\AuthLog;
use Xultech\AuthLogNotification\Support\EventLevelResolver;
use Xultech\AuthLogNotification\Services\GeoLocation\GeoLocationService;
use Xultech\AuthLogNotification\Support\HookExecutor;
use Illuminate\Support\Carbon;

/**
 * Listener to handle and log password reset events.
 */
class PasswordResetEventListener
{
    /**
     * Handle the event.
     *
     * @param PasswordReset $event
     * @return void
     */
    public function handle(PasswordReset $event): void
    {
        if (! Config::get('authlog.enabled') || ! Config::get('authlog.log_events.password_reset', false)) {
            return;
        }

        $user = $event->user;
        $request = Request::instance();

        $ip = $request->getClientIp();
        $userAgent = $request->userAgent();
        $referrer = $request->headers->get('referer');
        $sessionId = Session::getId();

        $location = App::make(GeoLocationService::class)->getGeoData($ip);

        // Create auth log
        $log = new AuthLog([
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
            'event_level' => EventLevelResolver::resolve('password_reset'),
            'login_at' => Carbon::now(),
            'session_id' => $sessionId,
        ]);

        $user->authentications()->save($log);

        // Trigger custom hook
        HookExecutor::run('on_password_reset', [
            'user' => $user,
            'auth_log' => $log,
            'request' => $request,
        ]);
    }
}
