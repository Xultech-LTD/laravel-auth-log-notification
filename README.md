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

## Usage Guide

This package integrates deeply into your Laravel app with traits, scopes, components, and helpers. Here’s how to get started and make the most of it.

### 🧬 Add the Trait to Your User Model

To enable logging, session tracking, and notifications, you must add the `HasAuthLogs` trait to your `User` model (or any model implementing `Authenticatable` and `Notifiable`):

```php
use Xultech\AuthLogNotification\Traits\HasAuthLogs;

class User extends Authenticatable
{
    use HasAuthLogs;

    // ...
}
```
This trait wires in all the core features of the package, including:
- Access to login and session history
- Device and location details for each login
- Notification routing (e.g. phone for SMS)
- Query scopes for fetching login activity
- Helpers for previous and current sessions

### 🧠 Helper Methods from the Trait
After adding the trait, your model now has several powerful methods:
#### ⏱️ Login Timestamps
```php
$user->lastLoginAt();         // Timestamp of latest login
$user->previousLoginAt();     // Timestamp of login before last
```
#### 🌐 IP Address History
```php
$user->lastLoginIp();         // IP of last login
$user->previousLoginIp();     // IP before last
```
#### 📖 Login History & Collections
```php
$user->authentications();     // All login logs (latest first)
$user->logins();              // Last 5 successful logins
$user->logins(10);            // Last 10 logins
$user->loginsToday();         // Logins from today only
```
#### ❌ Failed & Suspicious Logins
```php
$user->failedLogins();        // All failed attempts
$user->failedLoginsCount();   // Count of failed attempts
$user->suspiciousLogins();    // New device or location
$user->lastLoginWasSuspicious(); // Was last login suspicious?
$user->lastLoginWasFromIp('1.2.3.4'); // Did user login from a specific IP?
```
#### 📍 Location & Device Awareness
```php
$user->lastKnownLocation();   // e.g., "Lagos, Nigeria"
$user->lastKnownDevice();     // e.g., "Windows / Chrome (Desktop)"
```
#### 👥 Sessions & Activity
```php
$user->activeSessions();      // Logins not yet logged out
$user->hasMultipleSessions(); // More than one active session?
```

### 📌 Query Scopes (AuthLogUserScopes)
This package also registers custom query scopes dynamically via AuthLogUserScopes::register():
Example Queries:
```php
User::loggedInToday()->get();       // Users active today
User::withFailedLogins()->get();    // Users with failed attempts
User::suspiciousActivity()->get();  // Users with flagged logins
User::multipleSessions()->get();    // Users with concurrent sessions
User::inactiveSince(60)->get();     // Users inactive for 60+ days
```
> Ensure AuthLogUserScopes::register() is called in a service provider (usually the package does this automatically).

### 📊 The AuthLog Model
All authentication events are stored in the auth_logs table via the AuthLog model.

##### Relationships:
```php
$log->authenticatable; // Returns the related User/Admin/etc.
```
##### Scopes
```php
AuthLog::completed();          // Sessions with logout_at
AuthLog::active();             // Currently active sessions
AuthLog::failed();             // Failed login attempts
AuthLog::fromIp('1.2.3.4');    // Filter by IP
AuthLog::withSession($id);     // Filter by session_id
```
##### State Checkers:
```php
$log->isActive();              // Session still active?
$log->isFailed();              // Was this a failed login?
$log->isSuspicious();          // Marked suspicious?
$log->isLogin();               // Is this a login event?
$log->isLogout();              // Is this a logout event?
```
##### Accessors
```php
$log->formatted_location;      // "Lagos, Nigeria"
$log->device_summary;          // "MacOS / Safari (Mobile)"
$log->event_type;              // "Login", "Failed Login"
$log->login_at_formatted;      // "2025-03-23 18:00:00"
$log->referrer_domain;         // Extracts domain from referrer
$log->user_agent_fragment;     // First 80 chars of user-agent
```
## 🔔 Notifications

This package allows you to notify users of login activity using Laravel’s built-in notification channels.

Notifications are sent when a user logs in, and can be routed through:

- **Email (Mail)**
- **Slack (via webhook)**
- **SMS (via Nexmo/Vonage)**

You have full control over:

- Which channels to use
- What content is sent
- When the notification is triggered (e.g., always or only on suspicious activity)
- Whether to use Laravel's `Notification` system or a custom `Mailable`


### 🔧 Notification Configuration

In your `config/authlog.php`, you’ll find the full notification settings:

```php
'notification' => [
    'channels' => ['mail'], // Options: 'mail', 'slack', 'nexmo'
    'mode' => 'notification', // or 'mailable'
    'only_on_suspicious_activity' => true, // Only notify when flagged
],
```
### 🔘 Notification Modes
##### 1. notification (default)
This uses Laravel's standard Notification class, meaning it's queued, well-integrated, and flexible.
##### 2. mailable
If you prefer a full Mailable class (like a styled HTML email with branding), set this mode.
```php
'mode' => 'mailable',
```
Both modes are supported, and you can publish and override the default templates.
### 📡 Notification Channels
Define the channels you want to use:
```php
'channels' => ['mail', 'slack', 'nexmo']
```
Each channel is configurable individually under channels_config:
```php
'channels_config' => [
    'mail' => [
        'enabled' => true,
        'template' => 'authlog::mail.login-alert',
    ],
    'slack' => [
        'enabled' => true,
        'channel' => '#security-alerts',
    ],
    'nexmo' => [
        'enabled' => true,
        'phone_field' => 'phone',
    ],
],
```
You can enable or disable each channel independently.
### 🖼️ Customizing Mail Templates
To customize the content or layout of your login alert emails:
```bash
php artisan vendor:publish --provider="Xultech\AuthLogNotification\ServiceProvider" --tag="views"
```
This will publish views like:
```php
resources/views/vendor/authlog/mail/login-alert.blade.php
```
Update the layout, wording, or include branding as needed.
### 🧭 Notification Routing (Slack & SMS)
Laravel needs to know where to send Slack messages or SMS alerts. You can define this in your User model.
##### Slack Routing
```php
public function routeNotificationForSlack()
{
    return 'https://hooks.slack.com/services/XXX/YYY/ZZZ';
}
```
Or dynamically from the user:
```php
public function routeNotificationForSlack()
{
    return $this->slack_webhook_url;
}
```
##### Nexmo/Vonage SMS Routing
By default, the system will look for the field defined in phone_field:
```php
'nexmo' => [
    'phone_field' => 'phone',
]
```
If your model uses a different field name or you want more control:
```php
public function routeNotificationForNexmo()
{
    return $this->phone_number;
}
```
#### ✉️ Default Notification Settings
You can also globally define subject lines, fallback Slack channels, and sender emails:
```php
'default_notification' => [
    'subject' => 'New Login Detected',
    'slack_channel' => '#security-alerts',
    'mail_from' => env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
    'mail_view' => 'authlog::mail.login-alert',
],
```
These settings are used when no user-specific overrides exist.

#### 🔐 Suspicious Login Notifications Only
If you want to notify users only when a login is suspicious, enable this:
```php
'only_on_suspicious_activity' => true,
```
Suspicious logins are flagged when:
- The user logs in from a new device
- The user logs in from a new location

This helps reduce noise and only notifies users when their account might be at risk.

---
This notification system is fully extensible, so you can create your own custom notifications, modify the channels, or build your own logic based on the AuthLog model or Laravel events.

## 🧩 Custom Hooks

This package allows you to hook into authentication events like login, logout, and failed login — giving you the power to run custom logic when those events fire.

You can define callbacks, dispatch jobs, send alerts, trigger audits, or anything else you want when a user authenticates.

---

### 🔧 Configuration

In your `config/authlog.php`, you'll find the `hooks` section:

```php
'hooks' => [
    'on_login'  => null,
    'on_logout' => null,
    'on_failed' => null,
],
```
Each hook accepts one of the following:
- A class name that implements __invoke($user, $log)
- A closure
- A job class name
- A listener class

---
#### 🛠 Example: Logging Admin Login
Let’s say you want to log a message whenever an admin logs in:
```php
use Illuminate\Support\Facades\Log;

'hooks' => [
    'on_login' => function ($user, $log, $request) {
        if ($user->is_admin) {
            Log::info("Admin {$user->name} logged in from {$log->ip_address}");
        }
    },
],
```
---
#### 🚀 Example: Dispatch a Job
```php
'hooks' => [
    'on_failed' => \App\Jobs\HandleFailedLogin::class,
],
```
> Your job should implement the __invoke() method or a handle() method that receives the $user, $log, and optionally $request.

```php
class HandleFailedLogin implements ShouldQueue
{
    public function handle($user, $log, $request)
    {
        // Block IP, alert security, log audit, etc.
    }
}
```
---
### 🔁 Hook Parameters

Each hook receives an array of three values, passed in the following order:

| Parameter | Type                          | Description                                  |
|-----------|-------------------------------|----------------------------------------------|
| `$user`   | `Illuminate\Contracts\Auth\Authenticatable` | The user who triggered the event             |
| `$log`    | `Xultech\AuthLogNotification\Models\AuthLog` | The AuthLog instance that was just saved     |
| `$request`| `Illuminate\Http\Request`      | The Laravel Request object (IP, agent, etc.) |

If you’re using a closure or a class, make sure it accepts these arguments in the correct order:

```php
function ($user, $log, $request) {
    // your logic here
}
```
---
#### 📦 How Laravel Resolves Your Hook
When you pass a class name to a hook (like a job or listener), the package will resolve it using Laravel’s service container:
```php
App::make(YourHookClass::class)->__invoke($user, $log, $request);
```
This means:

- ✅ Laravel will automatically **instantiate the class**
- ✅ Any constructor dependencies (e.g., services, config, logger) will be **injected**
- ✅ You don’t have to call `new ClassName(...)` manually

For example, this works perfectly:
```php
class NotifySecurityTeam
{
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke($user, $log, $request)
    {
        $this->logger->info("User {$user->email} logged in from suspicious IP.");
    }
}
```
### ✅ Supported Hook Events

| Hook Key    | Triggered When                    |
|-------------|-----------------------------------|
| `on_login`  | After a successful login          |
| `on_logout` | When a user logs out              |
| `on_failed` | After a failed login attempt      |

---
## 🚨 Suspicious Login Detection

This package includes built-in support for detecting suspicious login activity. It helps identify unusual behavior and protects user accounts by flagging logins from unfamiliar devices or locations.

---

### 🧠 What Counts as Suspicious?

By default, a login is considered suspicious if it matches **either** of the following:

- The login comes from a **new device** (based on user agent/device metadata)
- The login comes from a **new location** (based on IP address or geolocation)

These rules are configurable in `config/authlog.php`:

```php
'suspicion_rules' => [
    'new_device' => true,
    'new_location' => true,
    'block_suspicious_logins' => false,
],
```
### ⚙️ How It Works

When a user logs in:

1. The system compares the current device and location against previously seen records for the user.
2. If either is different **and enabled** in the config, the login is marked as suspicious.
3. The `AuthLog` record is saved with:

```php
'is_new_device'   => true,
'is_new_location' => true,
```
4. You can act on this using:
- Notifications
- Custom hooks
- Blocking logic
- Admin alerts

### 📩 Notify on Suspicious Activity Only
To notify users only when a login is suspicious, update this in authlog.php:
```php
'notification' => [
    'only_on_suspicious_activity' => true,
]
```
If set to `false`, users will be notified on every login, not just suspicious ones.