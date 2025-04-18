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
---

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
> Before you begin to use this package ensure you have published the GeoIP config package.
> You can find the details about how to do this at [Torann/laravel-geoip](https://github.com/Torann/laravel-geoip)

---
## Configuration

To publish the configuration file, run:

```bash
php artisan vendor:publish --provider="Xultech\AuthLogNotification\AuthLogNotificationServiceProvider" --tag="authlog-config"
```
This will create a file at:
```php
config/authlog.php
```
### Available Configuration Options

#### 🔘 enabled
Enable or disable the entire package globally.
```php
'enabled' => true,
```
#### 🗂️ log_events
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
#### 📣 notification
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
#### 📩 default_notification
Override default values used for notification channels.
```php
'default_notification' => [
    'subject' => 'New Login Detected',
    'slack_channel' => '#security-alerts',
    'mail_from' => env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
    'mail_view' => 'authlog::mail.login-alert',
],
```
#### 🧠 device_detection
Enable device tracking, user-agent, and metadata storage.
```php
'device_detection' => [
    'enabled' => true,
    'store_user_agent' => true,
    'store_device_metadata' => true,
],
```

#### 🌍 location_detection
Control geo-location detection settings.
```php
'location_detection' => [
    'enabled' => true,
    'driver' => 'torann/geoip',
    'store_metadata' => true,
    'strict' => false,
],
```
#### 🧬 session_tracking
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
#### ✅ whitelisted_ips
List of IPs that will never trigger alerts or be flagged.
```php
'whitelisted_ips' => [
    '127.0.0.1',
    '::1',
],
```
#### 🛡️ security_thresholds
Thresholds to detect brute-force and location change behavior.
```php
'security_thresholds' => [
    'failed_attempts_before_alert' => 3,
    'alert_on_geo_change' => true,
    'cooldown_minutes' => 3,
],
```
#### 🧼 retention
Control log retention and cleanup behavior.
```php
'retention' => [
    'enabled' => true,
    'days' => 90,
    'delete_method' => 'soft', // Options: 'soft', 'hard'
],

'auto_cleanup' => true,
```
#### 🔄 hooks
Bind custom callbacks or listeners to authentication events.
```php
'hooks' => [
    'on_login'  => null,
    'on_logout' => null,
    'on_failed' => null,
],
```
#### 🛠️ admin
Optional admin view configuration (for future support or custom use).
```php
'admin' => [
    'viewer_enabled' => false,
    'viewer_route' => '/admin/auth-logs',
    'middleware' => ['web', 'auth', 'can:view-auth-logs'],
],
```
#### 🧭 location_service
Customize the geolocation service class.
```php
'location_service' => \Xultech\AuthLogNotification\Services\GeoLocation\GeoLocationService::class,
```
#### ⚠️ suspicion_rules
Define logic for what counts as a suspicious login and whether to block it
```php
'suspicion_rules' => [
    'new_device' => true,
    'new_location' => true,
    'block_suspicious_logins' => false,
],
'suspicious_login_handler' => \Xultech\AuthLogNotification\Handlers\SuspiciousLoginHandler::class,
```
#### 🔐 lockout
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
#### 🛑 middleware_blocking
Configure the middleware that blocks suspicious login attempts before authentication (i.e., on the login route).
```php
'middleware_blocking' => [

    // Enable or disable the middleware
    'enabled' => true,

    // The Eloquent model class used to identify the user (e.g., App\Models\User::class)
    // Use a string, not ::class, for compatibility with package-based environments
    'user_model' => 'App\\Models\\User',

    // The database column used to match the user (e.g., 'email', 'username')
    'email_column' => 'email',

    // The request input key used in your login form (e.g., 'email', 'login', 'identifier')
    // This should match the input name your users fill out when logging in
    'request_input_key' => 'email',
],
```
> This powers the `authlog.block-suspicious` middleware. When enabled, the system checks if the login attempt is from a new device or IP, and blocks the request before authentication if suspicious.

To use it:
```php
Route::post('/login', [LoginController::class, 'store'])
    ->middleware('authlog.block-suspicious');
```


This gives you full control over how authentication is monitored, logged, and secured within your Laravel app. Adjust the values to fit your security strategy.

---
## Publishing Assets

This package provides some required and optional assets that can be published into your application for customization.

### 🗃️ Migrations (Required)

To publish the database migration:

```bash
php artisan vendor:publish --provider="Xultech\AuthLogNotification\AuthLogNotificationServiceProvider" --tag="authlog-migrations"
```
This will copy the migration file to:

```bash
database/migrations/xxxx_xx_xx_xxxxxx_create_auth_logs_table.php
```
Make sure to run:
```bash
php artisan migrate
```

### 🖼️ Views
To customize the email or notification templates, publish the view files:
```bash
php artisan vendor:publish --provider="Xultech\AuthLogNotification\AuthLogNotificationServiceProvider" --tag="authlog-views"
```
View files will be published to:
```php
resources/views/vendor/authlog/
```
You can edit templates like login-alert.blade.php to match your brand or layout.

### 🧩 Blade Components
The package provides reusable Blade components. To customize them, publish the components:
```bash
php artisan vendor:publish --provider="Xultech\AuthLogNotification\AuthLogNotificationServiceProvider" --tag="authlog-components"
```
This will publish components like:
- `<x-authlog:last-login />`
- `<x-authlog:recent-logins />`
- `<x-authlog:suspicious-alert />`
- `<x-authlog:session-count />`

These components can be embedded in your dashboard or user profile pages to display session-related information at a glance.

### 🎧 Listeners (Optional)
If you'd like to customize what happens on login/logout/failed/password reset:
```bash
php artisan vendor:publish --provider="Xultech\AuthLogNotification\AuthLogNotificationServiceProvider" --tag="authlog-listeners"
```

This will copy listeners like `LoginEventListener`, `LogoutEventListener`, etc., into:
```bash
app/Listeners/AuthLog/
```
You can then customize what each event does, such as triggering custom notifications, logging to other tables, or extending tracking logic.

### 📡 Events (Optional)
To customize or extend the package’s custom events:
```php
php artisan vendor:publish --provider="Xultech\AuthLogNotification\AuthLogNotificationServiceProvider" --tag="authlog-events"
```
This publishes events like ReAuthenticated to:
```bash
app/Events/AuthLog/
```
### 🔔 Notifications (Optional)
To modify how users are notified when login or suspicious activity occurs:
```php
php artisan vendor:publish --provider="Xultech\AuthLogNotification\AuthLogNotificationServiceProvider" --tag="authlog-notifications"

```
This copies notification classes (like LoginAlertNotification) into:
```bash
app/Notifications/AuthLog/
```
### 🛡️ Middleware (Optional)
If you want to customize any of the built-in middlewares (rate limiting, session fingerprinting, or blocking suspicious logins), you can publish them like so:
```bash
php artisan vendor:publish --provider="Xultech\AuthLogNotification\AuthLogNotificationServiceProvider" --tag="authlog-middleware"
```
Middleware classes will be published to:
```bash
app/Http/Middleware/AuthLog/
```
> The middleware documentation below lists all the available middlewares and how you can use them



---
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
$log->is_suspicious;           // Boolean (true if login is suspicious)
```
> `is_suspicious` is a computed property that uses the `SuspicionDetector` service and respects the config settings for new device or new location detection.
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

### Manually Logging Authentication Events
In addition to automatic detection through Laravel's auth events, you can also manually trigger logs and alerts from within your controllers, services, or custom login flows. This gives you full control over when and how logging occurs — especially useful for custom guards or stateless APIs.

##### 🔐 Login
```php
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Http\Request;

// Inside your login controller or service
$user = User::where('email', $request->email)->first();

// Perform login logic...

// Manually dispatch login event
Event::dispatch(new Login('web', $user, false));
```

##### ❌ Failed Login
```php
use Illuminate\Auth\Events\Failed;

Event::dispatch(new Failed('web', null, [
    'email' => $request->email,
    'password' => $request->password,
]));
```
##### 🔓 Logout
```php
use Illuminate\Auth\Events\Logout;

Event::dispatch(new Logout('web', auth()->user()));
```

##### 🔁 Password Reset
```php
use Illuminate\Auth\Events\PasswordReset;

Event::dispatch(new PasswordReset($user));
```

##### 🔁 Re-Authentication (e.g. Password Confirm Screens)
If you implement a flow like password confirmation, you can dispatch:
```php
use Xultech\AuthLogNotification\Events\ReAuthenticated;

Event::dispatch(new ReAuthenticated($user));
```
>You only need to do this in custom logic. Laravel will automatically fire these events when using its built-in authentication system.


### 🔧 Notification Configuration

In your `config/authlog.php`, you’ll find the full notification settings:

```php
'notification' => [
    'channels' => ['mail'], // Options: 'mail', 'slack', 'nexmo'
    'mode' => 'notification', // or 'mailable'
    'only_on_suspicious_activity' => true, // Only notify when flagged
],
'default_notification' => [
        'subject' => 'New Login Detected',
        'slack_channel' => '#security-alerts',
        'mail_from' => env('MAIL_FROM_ADDRESS', 'no-reply@example.com'),
        'mail_view' => 'authlog::mail.login-alert', // or 'authlog::mail.login-alert-html'
        'view_type' => 'markdown', // or 'html'
    ],
```
### 🔘 Notification Modes
##### 1. notification (default)
This uses Laravel's standard Notification class, meaning it's queued, well-integrated, and flexible.
```php
'view_type' => 'markdown',
```
##### 2. mailable
If you prefer a full Mailable class (like a styled HTML email with branding), set this mode.
```php
'view_type' => 'html',
```
Both modes are supported, and you can publish and override the default templates.
> If you are using markdown, you must also publish the Laravel’s Default Mail Views

Run this command to publish the views:
```php
php artisan vendor:publish --provider="Xultech\AuthLogNotification\AuthLogNotificationServiceProvider" --tag="authlog-views"
php artisan vendor:publish --tag=laravel-mail
```
> if you encounter any errors while using `html`, run this:
```php
php artisan vendor:publish --provider="Xultech\AuthLogNotification\AuthLogNotificationServiceProvider" --tag="authlog-views"
```
This publishes the custom HTML mail view for the email. Also ensure you set 
```php
'mail_view' => 'authlog::mail.login-alert', // or 'authlog::mail.login-alert-html'
```
 to the corresponding view. You can define your default view which will be used, only that you need to 
set it in the `mail_view` configuration option.

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
php artisan vendor:publish --provider="Xultech\AuthLogNotification\AuthLogNotificationServiceProvider" --tag="authlog-views"
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
    'view_type' => 'markdown', // or 'html'
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

### 🔐 Block Suspicious Logins (Optional)
You can take it a step further and prevent suspicious logins entirely by enabling:
```php
'block_suspicious_logins' => true,
```
When this is enabled and a suspicious login is detected, the login process can be aborted based on your custom handler.

### 🧩 Custom Handler
If you're using custom logic to handle suspicious logins (e.g., verification steps or two-factor prompts), you can specify your own handler class:
```php
'suspicious_login_handler' => \App\Handlers\MySuspiciousLoginHandler::class,
```
Your handler class should implement a `handle(Request $request): Response` method. You can use this to:
>Your custom handler should return a `Symfony\Component\HttpFoundation\Response`.
- Return a custom response (JSON, HTML, redirect)
- Trigger extra verification steps
- Log or report the attempt
- Notify administrators
- Abort the login process with a custom message

### ⚙️ Default Behavior

If you don’t provide a custom handler, the package uses a default handler:

```php
\Xultech\AuthLogNotification\Handlers\SuspiciousLoginHandler::class
```
It returns a 403 JSON response:
```php
{
  "message": "Login blocked due to suspicious activity."
}
```
> Suspicious login detection and blocking is a powerful, zero-config enhancement to your authentication system. Combined with notifications, hooks, and session tracking, it gives your app a strong layer of security intelligence.

---
## 🔄 Event Listeners

This package automatically listens to key authentication-related events and logs them as `AuthLog` entries.

Each event captures device metadata, location, IP, user agent, timestamp, and more. You can also hook into each event to run your own custom logic.


### 🗂️ Supported Events & Their Listeners

| Event                          | Listener Class                                         | Description                                     |
|-------------------------------|--------------------------------------------------------|-------------------------------------------------|
| `Login`                       | `LoginEventListener`                                   | Logs successful login, detects suspicious login, sends notification, and triggers hook |
| `Logout`                      | `LogoutEventListener`                                  | Updates the logout timestamp on the last login session |
| `Failed`                      | `FailedLoginEventListener`                             | Logs failed login attempt and triggers rate limiter |
| `PasswordReset`               | `PasswordResetEventListener`                           | Logs password reset activity and triggers hook  |
| `ReAuthenticated` *(custom)* | `ReAuthenticatedEventListener`                         | Logs re-authentication (e.g., password confirm) |


### ✅ What Gets Logged

Each event logs the following data into the `auth_logs` table:

- IP address
- City, country, and location (via GeoIP)
- Device, browser, platform
- Whether it's a mobile device
- Referrer URL
- User agent
- Event type (login, logout, failed, etc.)
- Timestamp (`login_at` or `logout_at`)
- Session ID (if enabled)


### 🧩 Hook Support for Each Event

You can define custom logic for each of these in your config:

```php
'hooks' => [
    'on_login' => ...,
    'on_logout' => ...,
    'on_failed' => ...,
    'on_password_reset' => ...,
    'on_re_authenticated' => ...,
],
```
---
## 🚫 Rate Limiting & Lockouts

This package provides a built-in mechanism to track failed login attempts and lock out users after too many failures.

It helps protect your application against brute-force attacks and abusive login behavior, using a customizable and developer-friendly configuration.


### 🔧 Configuration

In your `config/authlog.php`, the lockout settings are found under the `lockout` key:

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
### 🛠️ How It Works
When a failed login event occurs, the system:

1. Tracks the failure using a unique key (based on IP, email, or both).
2. Increments a counter for that identifier.
3. If the number of failed attempts `exceeds max_attempts`, the user is locked out for the duration of `lockout_minutes`.
4. On subsequent login attempts, the login is blocked before authentication occurs.

### 🔌 Using the Middleware
To enforce rate limiting before authentication, use the built-in middleware:
```php
Route::post('/login', [LoginController::class, 'store'])
    ->middleware('authlog.enforce-lockout');
```
This checks the current identifier’s failure count before the login attempt is processed and blocks it if the lockout limit has been exceeded.

#### 💬 Customizing the Response
If `generic_response` is true, the user will receive a plain message:
```bash
Too many login attempts. Please try again later.
```
If false, the system will redirect to the `redirect_to` URL (usually /login) to show your UI or error message.

---
## 🧱 Middleware Overview
AuthLogNotification ships with several powerful middleware that can be used to secure your authentication flow, detect suspicious behavior, and block malicious requests before they reach your controllers.

These middleware are fully optional, but when enabled, they offer pre-authentication defense layers that can block bad actors early.

### ✅ Available Middleware

| Middleware                      | Alias                      | Purpose                                                              |
|----------------------------------|----------------------------|----------------------------------------------------------------------|
| `EnforceLoginRateLimit`         | `authlog.enforce-lockout`  | Blocks users with too many failed login attempts                    |
| `BlockSuspiciousLoginAttempt`   | `authlog.block-suspicious` | Prevents login from new devices or locations (before authentication)|
| `VerifySessionFingerprint`      | `authlog.verify-session`   | Detects session hijacking and mismatched device fingerprints        |

These middleware are registered and ready to use once you publish them and attach them to your routes.

### 📦 Publishing Middleware
To copy the middleware classes into your Laravel app `(e.g., app/Http/Middleware/AuthLog/)`:
```bash
php artisan vendor:publish --provider="Xultech\AuthLogNotification\AuthLogNotificationServiceProvider" --tag="authlog-middleware"
```
After publishing, you can customize them as needed.

### 📌 Registering the Middleware Alias
#### 🔹 For Laravel 10 and Below (Using Kernel.php)
If you want to add aliases manually in your `App\Http\Kernel`:
```php
protected $routeMiddleware = [
    // ...
    'authlog.enforce-lockout'   => \App\Http\Middleware\AuthLog\EnforceLoginRateLimit::class,
    'authlog.block-suspicious'  => \App\Http\Middleware\AuthLog\BlockSuspiciousLoginAttempt::class,
    'authlog.verify-session'    => \App\Http\Middleware\AuthLog\VerifySessionFingerprint::class,
];
```
> The package automatically registers the aliases if your app supports it (Laravel 7+), but manual registration is also fine.

#### 🔹 For Laravel 11+ and 12 
In Laravel 11 and 12, middlewares are no longer registered in `Kernel.php`. Instead, you can use them directly in your routes or register them globally in `bootstrap/app.php`.

##### 🏷️ Option 1: Apply Middleware Directly in Routes
Since Laravel 11+ supports middleware discovery, you can use fully qualified class names directly in your routes:
```php
use Xultech\AuthLogNotification\Http\Middleware\EnforceLoginRateLimit;
use Xultech\AuthLogNotification\Http\Middleware\BlockSuspiciousLoginAttempt;

Route::post('/login', LoginController::class)
    ->middleware([
        EnforceLoginRateLimit::class,
        BlockSuspiciousLoginAttempt::class,
    ]);
```
##### 🏷️ Option 2: Register Middleware in bootstrap/app.php
If you want to use named aliases (like `authlog.block-suspicious`), manually register middleware in `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'authlog.enforce-lockout' => \Xultech\AuthLogNotification\Http\Middleware\EnforceLoginRateLimit::class,
        'authlog.block-suspicious' => \Xultech\AuthLogNotification\Http\Middleware\BlockSuspiciousLoginAttempt::class,
        'authlog.verify-session' => \Xultech\AuthLogNotification\Http\Middleware\VerifySessionFingerprint::class,
    ]);
})
```
Now you can use the alias in routes:
```php
Route::post('/login', [LoginController::class, 'store'])
    ->middleware(['authlog.block-suspicious']);
```

### 🏗️ Publishing Middleware (Optional)
If you want to customize the middleware, you can publish them into your Laravel application:
```bash
php artisan vendor:publish --provider="Xultech\AuthLogNotification\AuthLogNotificationServiceProvider" --tag="authlog-middleware"
```
---
## 🧪 Blade Components
This package includes a set of pre-built Blade components to display useful login and session-related data on your frontend — like dashboards, profile pages, or admin panels.

These components help you quickly show login insights without writing extra code.
### 🔹 Available Components

> You must add the `HasAuthLogs` trait to your model for these components to work.

| Component                         | Usage                                | Description                                      |
|----------------------------------|--------------------------------------|--------------------------------------------------|
| `<x-authlog:last-login />`       | `@component('authlog::last-login')`  | Shows the user's last login time and location    |
| `<x-authlog:recent-logins />`    | `@component('authlog::recent-logins')` | Shows the last 5 login entries (with flags)    |
| `<x-authlog:suspicious-alert />` | `@component('authlog::suspicious-alert')` | Warning if last login was suspicious        |
| `<x-authlog:session-count />`    | `@component('authlog::session-count')` | Number of active sessions for the current user |

### 🛠 How to Publish Blade Components
If you want to customize how these components look or behave, publish them to your app:
```bash
php artisan vendor:publish --provider="Xultech\AuthLogNotification\AuthLogNotificationServiceProvider" --tag="authlog-components"
```
This will copy them to:
```bash
resources/views/vendor/authlog/components/
```
> You can now edit the components like regular Blade templates.

## 🧼 Cleanup & Maintenance
Over time, your `auth_logs` table can grow significantly. To help you manage storage and improve performance, this package includes Artisan commands for log cleanup and geo-location updates.

### 🧹 Clean Old Auth Logs
Remove logs older than the retention period (defined in `config/authlog.php`):
```bash
php artisan authlog:clean
```
> This command uses either soft or hard delete based on the `retention.delete_method` config.

#### ✅ Scheduling (Laravel 10 and below)
If you're using Laravel 10 or earlier, schedule this in `app/Console/Kernel.php`:
```php
$schedule->command('authlog:clean')->daily();
```
#### ✅ Scheduling (Laravel 11+)
Use the new `routes/console.php` format:
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('authlog:clean')->daily();
```
### 🧽 Prune Suspicious Logs
If you're storing a lot of logs marked as suspicious (e.g., `is_new_device or is_new_location`), and you no longer need them, run:

Laravel 10 and below:
```php
$schedule->command('authlog:prune-suspicious')->weekly();
```

Laravel 11 and 12:
```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('authlog:prune-suspicious')->weekly();
```

### 🌍 Sync Geo-Location
If your IP geolocation source has changed or some entries have missing location data, you can reprocess them using:
```bash
php artisan authlog:sync-location
```
> This will update all logs missing country, city, or location fields using your configured location service.

## 🧪 Testing

This package includes built-in Pest tests to ensure its core components are working as expected. You can also write your own tests to validate behavior in your application.

### ✅ Requirements

To run tests locally, make sure you have:

- PHP 8.0+
- Composer
- [Pest PHP](https://pestphp.com) installed globally or locally (installed via `dev` dependencies)

### 📦 Running Tests

To run the full test suite:

```bash
./vendor/bin/pest
```
Or, if Pest is installed globally:
```bash
pest
```

## 🤝 Contributing

Thank you for considering contributing to `AuthLogNotification`! Your help is deeply appreciated.

Whether you're reporting a bug, suggesting a feature, or submitting a pull request — you’re making the package better for everyone.

### 🛠️ How to Contribute

1. **Fork** the repository.
2. **Clone** your fork:
   ```bash
   git clone https://github.com/Xultech-LTD/laravel-auth-log-notification.git
   cd auth-log-notification
   ```
3. Install dependencies and set up the test environment.
4. Create a new branch:
    ```bash
   git checkout -b feature/my-improvement
   ```
5. Write your changes and cover them with tests.
6. Run the test suite
7. Commit with a clear message
    ```bash
   git commit -m "feat: added XYZ support to login hook"
   ```
8. Push and open a Pull Request.

### ✅ Coding Guidelines

- Follow **PSR-12** standards.
- Use meaningful variable and method names.
- Write **tests** for any new feature or fix.
- Keep pull requests **focused** and **descriptive**.
- Avoid breaking **backward compatibility** unless discussed.


### 📦 Testing Notes

This package uses **Pest PHP** and includes a fully bootstrapped container to simulate a Laravel-like environment.  
You can write **unit and feature tests** without needing a full Laravel app.

---

If you're unsure how to begin or where to contribute, feel free to [open an issue](https://github.com/Xultech-LTD/laravel-auth-log-notification/issues) and start the conversation.

> Let's build safer Laravel apps together.

## 🔐 Security

If you discover a vulnerability within this package, please **do not** report it publicly.

Instead, please send an email to [open-source@xultechng.com](mailto:open-source@xultechng.com) or open a private issue on the repository.

We take security seriously and will respond promptly.

### Responsible Disclosure

We appreciate responsible security disclosures and will:

- Acknowledge your report.
- Investigate and fix valid issues quickly.
- Credit you (if desired) once resolved.

## 🛣️ Roadmap

Here are some planned and proposed features for future versions of this package:

- [x] Core login/logout/failed event tracking
- [x] Device & IP intelligence (suspicious login detection)
- [x] Multi-channel notifications (Mail, Slack, SMS)
- [x] Custom hooks for login lifecycle
- [x] Session fingerprinting & hijack detection
- [x] Rate limiting and lockout middleware
- [x] Artisan commands for cleanup and geo sync
- [x] Blade components for recent activity
- [x] Middleware for pre-auth blocking
- [x] Configuration-driven and extendable

### Planned:

- [ ] Web UI for browsing logs
- [ ] Graphs & dashboard widgets (Jetstream/Livewire support)
- [ ] Admin review panel for suspicious events
- [ ] Custom log channel support
- [ ] First-party support for Fortify and Breeze
- [ ] Official Nova & Filament integrations
- [ ] Better localization support (i18n)
- [ ] Optional hashed user agents for privacy-sensitive apps
- [ ] Fine-grained control over notification triggers
- [ ] Export logs to CSV / external tools

> Want to see something else? Open an issue or submit a feature request!

## 📄 License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

You are free to use, modify, and distribute it within the terms of the license. Contributions are welcome and encouraged.

## 📝 Changelog

All notable changes to this project will be documented in this section.

This project follows [Semantic Versioning](https://semver.org/).

### [v1.0.0] - Initial Release
- ✅ Login, logout, and failed login tracking
- 🔐 Suspicious login detection (new device or location)
- 📣 Real-time notifications (Mail, Slack, SMS)
- 🧬 Session fingerprinting & hijack detection
- 🚫 Rate limiting with lockout support
- 🧩 Blade components for login/session insights
- 🛠️ Hooks & custom handlers
- 🗃️ Artisan commands for cleanup and geolocation sync
- 🧱 Fully documented and testable outside Laravel app

## 👥 Credits & Authors

AuthLogNotification was crafted with care by [Michael Erastus](https://github.com/michaelerastus) under [XulTech](https://github.com/Xultech-LTD) as part of our mission to build secure and developer-friendly Laravel tools.

### Core Maintainer
- **Michael Erastus** – [GitHub](https://github.com/michaelerastus)

### Contributors
Special thanks to everyone who provided feedback, reported issues, or helped shape the direction of this package. Your support makes open source better.

---

If you find this package helpful, consider giving it a ⭐️ on GitHub or sharing it with others in the Laravel community.

For contributions, ideas, or collaborations, feel free to reach out!

### ⚠️ Disclaimer

This package is provided as-is and is intended to **enhance security awareness** around authentication activity. While it offers advanced features such as suspicious login detection, session tracking, and brute-force protection, it **does not guarantee absolute security**.

You are responsible for ensuring that your Laravel application adheres to best practices, including:

- Keeping dependencies up to date
- Using secure authentication flows
- Regularly auditing and reviewing security configurations
- Complying with data protection regulations (e.g., GDPR)

By using this package, you acknowledge that:

- The authors and contributors are **not liable** for any security breaches, data loss, or misuse of the package.
- You should **review and test all features** before deploying them to production environments.

Use this package at your own discretion and always in conjunction with your existing security policies.
