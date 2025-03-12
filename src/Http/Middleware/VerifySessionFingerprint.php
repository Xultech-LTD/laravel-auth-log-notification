<?php

namespace Xultech\AuthLogNotification\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Xultech\AuthLogNotification\Services\SessionFingerprintService;
use Xultech\AuthLogNotification\Notifications\SessionHijackDetected;

/**
 * Middleware to detect session hijacking via fingerprint mismatch.
 */
class VerifySessionFingerprint
{
    /**
     * Handle an incoming request and validate session fingerprint integrity.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle($request, Closure $next): Response
    {
        if (! Config::get('authlog.session_fingerprint.validate_on_request', false)) {
            return $next($request);
        }

        if (! Auth::check()) {
            return $next($request);
        }

        $stored = Session::get('authlog_fingerprint');
        $current = SessionFingerprintService::generate();

        if ($stored !== $current) {
            // Hijack suspected — logout and flush session
            Auth::logout();
            Session::flush();

            // Notify users/admins
            if (Config::get('authlog.session_fingerprint.notify_on_mismatch')) {
                $ip = $request->getClientIp();
                $agent = $request->headers->get('User-Agent');
                $route = $request->getPathInfo();

                $notification = new SessionHijackDetected(
                    ip: $ip,
                    userAgent: $agent,
                    location: null,
                    route: $route
                );

                // Notify user
                if (Config::get('authlog.session_fingerprint.notify_user') && Auth::user()) {
                    Auth::user()->notify($notification);
                }

                // Notify admin emails (existing or raw)
                foreach (Config::get('authlog.session_fingerprint.notify_admins.emails', []) as $adminEmail) {

                    $adminModelClass = Config::get('auth.providers.users.model');

                    $admin = class_exists($adminModelClass)
                        ? $adminModelClass::where('email', $adminEmail)->first()
                        : null;

                    if ($admin) {
                        $admin->notify(clone $notification);
                    } else {
                        Notification::route('mail', $adminEmail)->notify(clone $notification);
                    }

                }

                // Notify admin Slack webhooks
                foreach (Config::get('authlog.session_fingerprint.notify_admins.slack_webhooks', []) as $webhook) {
                    Notification::route('slack', $webhook)->notify(clone $notification);
                }
            }

            // Respond based on config
            if (Config::get('authlog.session_fingerprint.abort_on_mismatch')) {
                return new Response(
                    'Session integrity check failed.',
                    Response::HTTP_FORBIDDEN
                );
            }

            $redirectPath = Config::get('authlog.session_fingerprint.redirect_to', '/login');
            return new RedirectResponse($redirectPath);
        }

        return $next($request);
    }
}