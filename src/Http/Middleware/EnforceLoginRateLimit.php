<?php

namespace Xultech\AuthLogNotification\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Xultech\AuthLogNotification\Services\LoginRateLimiter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class EnforceLoginRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */

    public function handle(Request $request, Closure $next): Response
    {
        if (! Config::get('authlog.lockout.enabled', false)) {
            return $next($request);
        }

        $email = $request->input('email');
        $identifier = LoginRateLimiter::resolveIdentifier($request, $email);

        if (LoginRateLimiter::tooManyAttempts($identifier)) {
            if (Config::get('authlog.lockout.generic_response', true)) {
                return new Response(
                    'Too many login attempts. Please try again later.',
                    Response::HTTP_TOO_MANY_REQUESTS
                );
            }

            $redirectTo = Config::get('authlog.lockout.redirect_to', '/login');

            return new RedirectResponse($redirectTo);
        }

        return $next($request);
    }
}