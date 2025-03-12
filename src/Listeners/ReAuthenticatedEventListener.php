<?php

namespace Xultech\AuthLogNotification\Listeners;

use Illuminate\Support\Facades\App;
use Xultech\AuthLogNotification\Events\ReAuthenticated;
use Xultech\AuthLogNotification\Models\AuthLog;
use Xultech\AuthLogNotification\Support\EventLevelResolver;
use Xultech\AuthLogNotification\Support\HookExecutor;
use Xultech\AuthLogNotification\Services\GeoLocation\GeoLocationService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Carbon;

/**
 * Listener to log re-authentication events (e.g., confirm password).
 */
class ReAuthenticatedEventListener
{
    public function handle(ReAuthenticated $event): void
    {
        if (! Config::get('authlog.enabled') || ! Config::get('authlog.log_events.re-authenticated', false)) {
            return;
        }

        $user = $event->user;
        $request = Request::instance();

        $ip = $request->getClientIp();
        $userAgent = $request->userAgent();
        $referrer = $request->headers->get('referer');
        $sessionId = Session::getId();

        $location = App::make( GeoLocationService::class)->getGeoData($ip);

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
            'event_level' => EventLevelResolver::resolve('re-authenticated'),
            'login_at' => Carbon::now(),
            'session_id' => $sessionId,
        ]);

        $user->authentications()->save($log);

        HookExecutor::run('on_re_authenticated', [
            'user' => $user,
            'auth_log' => $log,
            'request' => $request,
        ]);
    }
}
