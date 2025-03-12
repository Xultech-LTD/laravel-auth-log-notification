<?php

namespace Xultech\AuthLogNotification\Support;

use Symfony\Component\HttpFoundation\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\App;
use Xultech\AuthLogNotification\Models\AuthLog;
use Xultech\AuthLogNotification\Services\SuspicionDetector;

/**
 * This utility checks if a login is suspicious and blocks it if configured to do so.
 */
class LoginBlocker
{
    /**
     * Check if the login should be blocked and return a response if so.
     *
     * @param AuthLog $log
     * @param Request|null $request
     * @return \Symfony\Component\HttpFoundation\Response|null
     */
    public static function maybeBlock(AuthLog $log, $request = null)
    {
        if (! Config::get('authlog.block_suspicious_logins')) {
            return null;
        }

        if (! SuspicionDetector::isSuspicious($log)) {
            return null;
        }

        $handlerClass = Config::get('authlog.suspicious_login_handler');
        $handler = App::make($handlerClass);

        // Use Symfony's request object if not passed
        $request = $request ?? Request::createFromGlobals();

        return $handler->handle($request);
    }

}