<?php

namespace Xultech\AuthLogNotification\Listeners;

use Carbon\Carbon;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Xultech\AuthLogNotification\Models\AuthLog;
use Xultech\AuthLogNotification\Services\GeoLocation\GeoLocationService;
use Xultech\AuthLogNotification\Services\SessionFingerprintService;
use Xultech\AuthLogNotification\Support\EventLevelResolver;
use Xultech\AuthLogNotification\Support\HookExecutor;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Xultech\AuthLogNotification\Support\LoginBlocker;

class LoginEventListener
{
    /**
     * Handle the login event.
     *
     * @param  Login  $event
     * @return void
     */
    public function handle(Login $event): void
    {
        $user = $event->user;
        // Use Laravel request helper if available, else fallback for testing
        $request = function_exists('request') ? request() : Request::createFromGlobals();


        // Check if login logging is enabled
        if (!Config::get('authlog.enabled') || !Config::get('authlog.log_events.login', true)) {
            return;
        }

        // Extract IP and agent info
        $ip = $request->getClientIp();
        $userAgent = $request->userAgent();
        $referrer = $request->headers->get('referer');

        // Get geolocation data
        $location = App::make(GeoLocationService::class)->getGeoData($ip);

        // Get session ID (if available)
        $sessionId = Session::getId();

        // Generate session fingerprint
        $fingerprint = SessionFingerprintService::generate();

        // Store fingerprint in session if enabled
        if (Config::get('authlog.session_tracking.session_fingerprint.store_in_session', true)) {
            Session::put('authlog_fingerprint', $fingerprint);
        }

        // Use null-safe check for trait method
        $hasLogs = method_exists($user, 'authentications');

        // Check if this is a new device or location
        $isNewDevice = $hasLogs && !$user->authentications()->where('user_agent', $userAgent)->exists();
        $isNewLocation = $hasLogs && !$user->authentications()->where('ip_address', $ip)->exists();

        // Build AuthLog record
        $log = new AuthLog([
            'ip_address'      => $ip,
            'country'         => $location['country'] ?? null,
            'city'            => $location['city'] ?? null,
            'location'        => $location['location'] ?? null,
            'browser'         => $location['browser'] ?? null,
            'platform'        => $location['platform'] ?? null,
            'device'          => $location['device'] ?? null,
            'is_mobile'       => $location['is_mobile'] ?? false,
            'user_agent'      => $userAgent,
            'referrer'        => $referrer,
            'metadata'        => $location,
            'event_level'     => EventLevelResolver::resolve('login'),
            'is_new_device'   => $isNewDevice,
            'is_new_location' => $isNewLocation,
            'login_at'        => Carbon::now(),
            'session_id'      => $sessionId,
        ]);

        if ($hasLogs) {
            $user->authentications()->save($log);

            // 🛡️ Automatically block if suspicious and configured
            if ($response = LoginBlocker::maybeBlock($log, $request)) {
                $response->send(); // return the blocking response
                exit; // stop execution to prevent login continuation
            }
        }

        // Optionally notify user of login (if configured)
        if (
            method_exists($user, 'notify') &&
            method_exists($user, 'shouldReceiveLoginNotification') &&
            $user->shouldReceiveLoginNotification($log)
        ) {
            $user->notify(new \Xultech\AuthLogNotification\Notifications\LoginAlertNotification($log));
        }

        // Trigger custom hook (e.g., for auditing, custom logging)
        App::make(HookExecutor::class)->run('on_login', [
            'user' => $user,
            'auth_log' => $log,
            'request' => $request,
        ]);
    }
}
