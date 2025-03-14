<?php

namespace Xultech\AuthLogNotification\Listeners;

use Carbon\Carbon;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Xultech\AuthLogNotification\Models\AuthLog;
use Xultech\AuthLogNotification\Support\EventLevelResolver;
use Xultech\AuthLogNotification\Support\HookExecutor;

/**
 * Listener to capture logout events and update the corresponding AuthLog record.
 */
class LogoutEventListener
{
    /**
     * Handle the logout event and update the matching login log.
     *
     * @param  Logout  $event
     * @return void
     */
    public function handle(Logout $event): void
    {
        $user = $event->user;

        // Exit early if logging is disabled
        if (! Config::get('authlog.enabled') || ! Config::get('authlog.log_events.logout', true)) {
            return;
        }

        // Try to retrieve session ID (if enabled)
        $sessionId = Config::get('authlog.session_tracking.enabled', true)
            ? Session::getId()
            : null;

        // Build query to find last login event for this session
        $query = $user->authentications()
            ->where('event_level', EventLevelResolver::resolve('login'))
            ->whereNull('logout_at')
            ->latest('login_at');

        if ($sessionId) {
            $query->where('session_id', $sessionId);
        }

        $log = $query->first();

        if (! $log) {
            return;
        }

        // Update logout timestamp
        $log->update([
            'logout_at' => Carbon::now(),
        ]);

        // Optionally trigger a logout hook
        App::make(HookExecutor::class)->run('on_logout', [
            'user' => $user,
            'auth_log' => $log,
            'session_id' => $sessionId,
        ]);
    }
}