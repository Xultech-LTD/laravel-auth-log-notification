<?php

namespace Xultech\AuthLogNotification\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Xultech\AuthLogNotification\Models\AuthLog;
use Xultech\AuthLogNotification\Services\GeoLocation\GeoLocationService;
use Xultech\AuthLogNotification\Services\SuspicionDetector;

class BlockSuspiciousLoginAttempt
{
    /**
     * Handle an incoming request before authentication.
     * Stops suspicious logins from unknown devices or locations.
     *
     * This middleware is useful for protecting /login routes in advance
     * based on historical authentication patterns.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if core authlog and middleware blocking are enabled
        if (! Config::get('authlog.enabled')) {
            return $next($request);
        }

        $blockingEnabled = Config::get('authlog.suspicion_rules.block_suspicious_logins', false);
        $middlewareEnabled = Config::get('authlog.middleware_blocking.enabled', false);

        if (! $blockingEnabled || ! $middlewareEnabled) {
            return $next($request);
        }

        // Fetch configuration values
        $inputField = Config::get('authlog.middleware_blocking.request_input_key', 'email');
        $emailColumn = Config::get('authlog.middleware_blocking.email_column', 'email');
        $modelClass = ltrim(Config::get('authlog.middleware_blocking.user_model'), '\\');

        // Extract identifier and request context
        $identifier = $request->input($inputField);
        $ip = $request->getClientIp();
        $userAgent = $request->userAgent();

        // Validate identifier and user model class
        if (! $identifier || ! $modelClass || ! class_exists($modelClass)) {
            return $next($request);
        }

        // Attempt to resolve user instance
        $user = $modelClass::where($emailColumn, $identifier)->first();

        if (! $user) {
            return $next($request);
        }

        $userType = get_class($user);

        // Check login history for device and location
        $hasUsedIp = AuthLog::where('authenticatable_type', $userType)
            ->where('authenticatable_id', $user->id)
            ->where('ip_address', $ip)
            ->exists();

        $hasUsedAgent = AuthLog::where('authenticatable_type', $userType)
            ->where('authenticatable_id', $user->id)
            ->where('user_agent', $userAgent)
            ->exists();

        $isNewDevice = ! $hasUsedAgent;
        $isNewLocation = ! $hasUsedIp;

        // Load optional geo metadata
        $geoData = App::make(GeoLocationService::class)->getGeoData($ip);

        // Build temporary in-memory AuthLog for detection
        $log = new AuthLog([
            'ip_address'      => $ip,
            'user_agent'      => $userAgent,
            'is_new_device'   => $isNewDevice,
            'is_new_location' => $isNewLocation,
            'metadata'        => $geoData,
        ]);

        // Evaluate login suspicion using config flags
        if (SuspicionDetector::isSuspicious($log)) {
            $handlerClass = Config::get('authlog.suspicious_login_handler');
            $handler = App::make($handlerClass);

            return $handler->handle($request);
        }

        return $next($request);
    }
}