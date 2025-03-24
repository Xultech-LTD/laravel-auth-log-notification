<?php

return [

    'enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Events to Log
    |--------------------------------------------------------------------------
    */
    'log_events' => [
        'login'             => true,
        'logout'            => true,
        'failed_login'      => true,
        'password_reset'    => false,
        're-authenticated'  => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Channels & Behavior
    |--------------------------------------------------------------------------
    */
    'notification' => [
        'channels' => ['mail'], // e.g. ['mail', 'slack', 'nexmo']
        'mode' => 'notification', // 'notification' or 'mailable'
        'only_on_suspicious_activity' => false,

        'channels_config' => [
            'mail' => [
                'enabled' => true,
                'template' => 'authlog::mail.login-alert',
            ],
            'slack' => [
                'enabled' => false,
                'channel' => '#alerts',
            ],
            'nexmo' => [
                'enabled' => false,
                'phone_field' => 'phone',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Notification Template Overrides
    |--------------------------------------------------------------------------
    */
    'default_notification' => [
        'subject' => 'New Login Detected',
        'slack_channel' => '#security-alerts',
        'mail_from' => env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
        'mail_view' => 'authlog::mail.login-alert', // or 'authlog::mail.login-alert-html'
        'view_type' => 'markdown', // or 'html'
    ],

    /*
    |--------------------------------------------------------------------------
    | Device & Location Tracking
    |--------------------------------------------------------------------------
    */
    'device_detection' => [
        'enabled' => true,
        'store_user_agent' => true,
        'store_device_metadata' => true,
    ],

    'location_detection' => [
        'enabled' => true,
        'driver' => 'torann/geoip',
        'store_metadata' => true,
        'strict' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Tracking & Fingerprinting
    |--------------------------------------------------------------------------
    */
    'session_tracking' => [
        'enabled' => true,
        'generate_session_id' => true,
        'enforce_single_session' => false,

        'fingerprint' => [
            'enabled' => true,
            'store_in_session' => true,
            'validate_on_request' => false,
        ],

        'session_fingerprint' => [
            'enabled' => true,
            'validate_on_request' => true,
            'abort_on_mismatch' => true,
            'redirect_to' => '/login',

            // Notifications on hijack
            'notify_user' => true,

            'notify_admins' => [
                'emails' => ['admin@example.com'],
                'slack_webhooks' => [
                    // 'https://hooks.slack.com/services/XXX/YYY/ZZZ',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Whitelisting (Never flagged or notified)
    |--------------------------------------------------------------------------
    */
    'whitelisted_ips' => [
        '127.0.0.1',
        '::1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Brute-force & Geo Trigger Thresholds
    |--------------------------------------------------------------------------
    */
    'security_thresholds' => [
        'failed_attempts_before_alert' => 3,
        'alert_on_geo_change' => true,
        'cooldown_minutes' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention & Cleanup
    |--------------------------------------------------------------------------
    */
    'retention' => [
        'enabled' => true,
        'days' => 90,
        'delete_method' => 'soft', // 'soft' or 'hard'
    ],

    'auto_cleanup' => true,


    /*
    |--------------------------------------------------------------------------
    | Event Hooking / Custom Actions
    |--------------------------------------------------------------------------
    |
    | These allow you to run custom logic on login/logout/failed events.
    | You can bind your own Jobs, Listeners, or Closures in this section.
    |
    */
    'hooks' => [
        'on_login'  => null,
        'on_logout' => null,
        'on_failed' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Tools (Optional future support)
    |--------------------------------------------------------------------------
    */
    'admin' => [
        'viewer_enabled' => false,
        'viewer_route' => '/admin/auth-logs',
        'middleware' => ['web', 'auth', 'can:view-auth-logs'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Location Resolver
    |--------------------------------------------------------------------------
    */
    'location_service' => \Xultech\AuthLogNotification\Services\GeoLocation\GeoLocationService::class,

    /*
    |--------------------------------------------------------------------------
    | Suspicion Rules & Blocking
    |--------------------------------------------------------------------------
    */
    'suspicion_rules' => [
        'new_device' => true,
        'new_location' => true,
        'block_suspicious_logins' => true,
    ],

    'suspicious_login_handler' => \Xultech\AuthLogNotification\Handlers\SuspiciousLoginHandler::class,

    'lockout' => [
        'enabled' => true,

        // Lock key prefix (used with IP or email)
        'key_prefix' => 'authlog:lockout:',

        // How many failed attempts before lockout triggers
        'max_attempts' => 5,

        // How long to lock the user out (in minutes)
        'lockout_minutes' => 10,

        // Whether to track by email, IP, or both
        'track_by' => 'ip', // 'ip', 'email', or 'both'

        // Show generic message (recommended for security)
        'generic_response' => true,

        // Custom redirect route or response on lockout
        'redirect_to' => '/login',
    ],

    /*
    |--------------------------------------------------------------------------
    | Suspicious Login Blocking via Middleware (Pre-Auth)
    |--------------------------------------------------------------------------
    |
    | This section controls whether login requests should be blocked before
    | authentication is attempted — based on suspicious device or location.
    |
    | If enabled, the package will:
    | - Attempt to find the user based on the provided email
    | - Compare the login request (IP/device) with past AuthLog records
    | - If the device or location is new, and blocking is configured, it will
    |   immediately stop the request using the configured handler.
    |
    | This is useful for protecting routes like /login from unknown devices
    | even before Laravel processes authentication.
    |
    */

    'middleware_blocking' => [

        // Enable or disable the middleware
        'enabled' => true,

        // The Eloquent model class used to identify the user (e.g., App\Models\User)
        'user_model' => "App\\Models\\User",

        // The column used to look up the user (e.g., 'email', 'username')
        'email_column' => 'email',

        // The input key in the login form (e.g., 'email', 'login', 'identifier')
        'request_input_key' => 'email',
    ],


];
