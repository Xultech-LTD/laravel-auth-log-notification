<?php

namespace Xultech\AuthLogNotification\Services;

use Xultech\AuthLogNotification\Models\AuthLog;
use Illuminate\Support\Facades\Config;

class SuspicionDetector
{
    /**
     * Determine if a given log is suspicious based on config rules.
     *
     * @param AuthLog $log
     * @return bool
     */
    public static function isSuspicious(AuthLog $log): bool
    {
        $rules = Config::get('authlog.suspicion_rules', []);

        return
            (!empty($rules['new_device']) && $log->is_new_device) ||
            (!empty($rules['new_location']) && $log->is_new_location);
    }
}