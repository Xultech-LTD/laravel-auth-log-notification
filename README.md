# Laravel Authentication Log & Notification

**AuthLogNotification** is a robust and extensible Laravel package for logging and monitoring authentication events across your application.

It captures and stores detailed information about login attempts, re-authentication, logout events, password resets, and failed login attempts. It also supports custom event hooks, notifications, rate limiting, session fingerprinting, and more — making it ideal for applications that require transparency, security, or user activity auditing.

## ✨ Features

### ✅ Login Logging
Track successful logins along with:
- IP address
- Device
- Platform
- Location
- Session ID
- Timestamp

---

### ❌ Failed Login Tracking
Record failed login attempts and trigger:
- Rate limiting
- Lockout after repeated failures

---

### 🔓 Logout Event Logging
Automatically update the corresponding login record with a logout timestamp when users log out.

---

### 🔁 Re-authentication Logging
Detect and log when a user re-authenticates (e.g., during password confirmation flows).

---

### 🔑 Password Reset Logging
Capture and store detailed events when a user resets their password.

---

### 🚨 Suspicious Login Detection
Automatically flag:
- New device logins
- New location logins  
  And mark the login event as **suspicious**.

---

### 📣 Notifications
Send login alerts through multiple channels:
- Email
- Slack
- SMS (via Vonage/Nexmo)  
  Each notification channel is fully customizable.

---

### 🔌 Custom Event Hooks
Register and execute user-defined hooks for:
- Login (`on_login`)
- Logout (`on_logout`)
- Failed login (`on_failed`)
- Re-authentication (`on_re_authenticated`)
- Password reset (`on_password_reset`)

---

### 🚫 Rate Limiting
Prevent brute-force attacks by:
- Tracking failed login attempts
- Locking out users by IP, email, or both

---

### 🧬 Session Fingerprinting
Detect and store session fingerprints to improve:
- Visibility of active sessions
- Suspicious activity detection (like hijacked sessions)

---

### ⚙️ Artisan Commands
Manage and maintain logs with built-in commands:

```bash
php artisan authlog:clean           # Delete old logs based on retention policy
php artisan authlog:prune-suspicious  # Remove logs marked as suspicious
php artisan authlog:sync-geo         # Update missing or outdated geo-location info

```
## 🚀 Installation & Requirements

### Requirements

This package supports Laravel **9 and above**. Ensure your project meets the following minimum requirements:

- PHP 8.0 or higher
- Laravel 9.x, 10.x, or later
- Composer (to install the package)
- A supported cache driver (`redis`, `database`, or `array` for testing)
- Eloquent user model implementing `Illuminate\Contracts\Auth\Authenticatable` and `Illuminate\Notifications\Notifiable`

> 💡 You may also optionally use Laravel's default `User` model or a custom one, as long as it uses Eloquent and includes the required traits.

---

### Installation

Install the package via Composer:

```bash
composer require xultech/auth-log-notification
```
## Publish Configuration (Optional)

To customize the behavior of the package (hooks, session fingerprinting, lockout settings, etc.), publish the config file:

```bash
php artisan vendor:publish --provider="Xultech\AuthLogNotification\ServiceProvider" --tag="config"
```
This will publish the config file to:
```bash
config/authlog.php
```
## Publish Blade Views (Optional)
You can publish the package’s Blade views to customize:
- Email templates
- Login alert content
- Blade components for your UI

```bash
php artisan vendor:publish --provider="Xultech\AuthLogNotification\ServiceProvider" --tag="views"
```
This will publish views to:
```bash
resources/views/vendor/authlog/
```
To publish only the Blade components:
```bash
php artisan vendor:publish --provider="Xultech\AuthLogNotification\ServiceProvider" --tag="components"
```

## Publish Event Listeners, Events, and Notifications (Optional)
To override the default login/logout/failed/password-reset logic or notifications, publish the core classes:
```bash
php artisan vendor:publish --provider="Xultech\AuthLogNotification\ServiceProvider" --tag="listeners"
php artisan vendor:publish --provider="Xultech\AuthLogNotification\ServiceProvider" --tag="events"
php artisan vendor:publish --provider="Xultech\AuthLogNotification\ServiceProvider" --tag="notifications"
```
These will publish files into your application under:
```bash
app/Listeners/AuthLog/
app/Events/AuthLog/
app/Notifications/AuthLog/
```
> ⚠️ You only need to publish these if you want to override the default behavior. The package works out of the box without publishing them.

