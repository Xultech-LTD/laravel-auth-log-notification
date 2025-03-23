# AuthLogNotification for Laravel

A Laravel package for tracking authentication activity, detecting suspicious logins, and notifying users in real time via Mail, Slack, or SMS.

It logs login, logout, and failed attempts, adds device and location awareness, supports session fingerprinting, rate limiting, and integrates seamlessly with your existing authentication flow.

## Features

* ✅ Tracks login, logout, failed login, and re-authentication events
* 🌍 Detects IP address, device type, browser, and geolocation
* 🔔 Sends real-time login alerts via Mail, Slack, and SMS (Vonage)
* 🧠 Detects suspicious logins based on device, IP, and location history
* 🔒 Supports session fingerprinting to detect hijacked sessions
* 🚫 Applies rate limiting and temporary lockouts for failed login attempts
* 🧩 Allows custom hook execution on auth events
* 📊 Includes Blade components for login/session insights
* 🧼 Artisan commands for log cleanup and geo-location syncing
* 🔌 Easily integrates with existing User models via a trait
* 🔧 Fully configurable and extendable

## Installation

You can install the package via Composer:

```bash
composer require xultech/auth-log-notification
```
The package uses Laravel's auto-discovery, so no additional configuration is needed for Laravel 5.5 and above.

If you're using Laravel below 5.5, you’ll need to manually register the service provider in config/app.php:

```php
'providers' => [
    // ...
    Xultech\AuthLogNotification\AuthLogNotificationServiceProvider::class,
],
```

## Configuration

To publish the configuration file, run:

```bash
php artisan vendor:publish --provider="Xultech\AuthLogNotification\ServiceProvider" --tag="config"
```
This will create a file at:
```php
config/authlog.php
```
## Available Configuration Options

### 🔘 enabled
Enable or disable the entire package globally.
```php
'enabled' => true,
```
### 🗂️ log_events
Control which authentication events should be logged.
```php
'log_events' => [
    'login'             => true,
    'logout'            => true,
    'failed_login'      => true,
    'password_reset'    => false,
    're-authenticated'  => false,
],
```
### 📣 notification
Configure how users should be notified about login events.
```php
'notification' => [
    'channels' => ['mail'], // Supports: mail, slack, nexmo (SMS)
    'mode' => 'notification', // 'notification' or 'mailable'
    'only_on_suspicious_activity' => true,

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
```
### 📩 default_notification
Override default values used for notification channels.
```php
'default_notification' => [
    'subject' => 'New Login Detected',
    'slack_channel' => '#security-alerts',
    'mail_from' => env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
    'mail_view' => 'authlog::mail.login-alert',
],
```
### 🧠 device_detection
Enable device tracking, user-agent, and metadata storage.
```php
'device_detection' => [
    'enabled' => true,
    'store_user_agent' => true,
    'store_device_metadata' => true,
],
```

### 🌍 location_detection
Control geo-location detection settings.
```php
'location_detection' => [
    'enabled' => true,
    'driver' => 'torann/geoip',
    'store_metadata' => true,
    'strict' => false,
],
```
### 🧬 session_tracking
Enable session tracking and fingerprint validation.
```php
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
        'notify_user' => true,

        'notify_admins' => [
            'emails' => ['admin@example.com'],
            'slack_webhooks' => [
                // Add your Slack webhook URLs here
            ],
        ],
    ],
],
```
### ✅ whitelisted_ips
List of IPs that will never trigger alerts or be flagged.
```php
'whitelisted_ips' => [
    '127.0.0.1',
    '::1',
],
```
### 🛡️ security_thresholds
Thresholds to detect brute-force and location change behavior.
```php
'security_thresholds' => [
    'failed_attempts_before_alert' => 3,
    'alert_on_geo_change' => true,
    'cooldown_minutes' => 3,
],
```
### 🧼 retention
Control log retention and cleanup behavior.
```php
'retention' => [
    'enabled' => true,
    'days' => 90,
    'delete_method' => 'soft', // Options: 'soft', 'hard'
],

'auto_cleanup' => true,
```
### 🔄 hooks
Bind custom callbacks or listeners to authentication events.
```php
'hooks' => [
    'on_login'  => null,
    'on_logout' => null,
    'on_failed' => null,
],
```
### 🛠️ admin
Optional admin view configuration (for future support or custom use).
```php
'admin' => [
    'viewer_enabled' => false,
    'viewer_route' => '/admin/auth-logs',
    'middleware' => ['web', 'auth', 'can:view-auth-logs'],
],
```
### 🧭 location_service
Customize the geolocation service class.
```php
'location_service' => \Xultech\AuthLogNotification\Services\GeoLocation\GeoLocationService::class,
```
### ⚠️ suspicion_rules
Define logic for what counts as a suspicious login and whether to block it
```php
'suspicion_rules' => [
    'new_device' => true,
    'new_location' => true,
    'block_suspicious_logins' => false,
],
'suspicious_login_handler' => \Xultech\AuthLogNotification\Handlers\SuspiciousLoginHandler::class,
```
### 🔐 lockout
Rate limiting and lockout configuration for brute-force protection.
```php
'lockout' => [
    'enabled' => true,
    'key_prefix' => 'authlog:lockout:',
    'max_attempts' => 5,
    'lockout_minutes' => 10,
    'track_by' => 'ip', // Options: 'ip', 'email', 'both'
    'generic_response' => true,
    'redirect_to' => '/login',
],
```
This gives you full control over how authentication is monitored, logged, and secured within your Laravel app. Adjust the values to fit your security strategy.

## Publishing Assets

This package provides some required and optional assets that can be published into your application for customization.

### 🗃️ Migrations (Required)

To publish the database migration:

```bash
php artisan vendor:publish --provider="Xultech\AuthLogNotification\ServiceProvider" --tag="migrations"
```
This will copy the necessary tables for logging authentication activity.

### 🖼️ Views
To customize the email or notification templates, publish the view files:
```bash
php artisan vendor:publish --provider="Xultech\AuthLogNotification\ServiceProvider" --tag="views"
```
View files will be published to:
```php
resources/views/vendor/authlog/
```
You can edit templates like login-alert.blade.php to match your brand or layout.

### 🧩 Blade Components
The package provides reusable Blade components. To customize them, publish the components:
```bash
php artisan vendor:publish --provider="Xultech\AuthLogNotification\ServiceProvider" --tag="components"
```
This will publish components like:
- `<x-authlog:last-login />`
- `<x-authlog:recent-logins />`
- `<x-authlog:suspicious-alert />`
- `<x-authlog:session-count />`

These components can be embedded in your dashboard or user profile pages to display session-related information at a glance.
