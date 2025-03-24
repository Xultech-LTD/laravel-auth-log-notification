<?php

namespace Xultech\AuthLogNotification;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Xultech\AuthLogNotification\Console\Commands\CleanAuthLogs;
use Xultech\AuthLogNotification\Console\Commands\PruneSuspiciousLogs;
use Xultech\AuthLogNotification\Console\Commands\SyncGeoLocation;
use Xultech\AuthLogNotification\Http\Middleware\BlockSuspiciousLoginAttempt;
use Xultech\AuthLogNotification\Http\Middleware\EnforceLoginRateLimit;
use Xultech\AuthLogNotification\Http\Middleware\VerifySessionFingerprint;
use Xultech\AuthLogNotification\Support\AuthLogUserScopes;
use Xultech\AuthLogNotification\Support\PathHelper;


class AuthLogNotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->commands([
            // Register all package Artisan commands
            CleanAuthLogs::class,         // Handles log retention cleanup
            PruneSuspiciousLogs::class,   // Removes logs with new device/location
            SyncGeoLocation::class,       // Synchronize geo-locations
        ]);
    }

    public function boot(): void
    {
        // Register middleware aliases ONLY inside a Laravel application
        $this->app->booted(function () {
            if (class_exists('Illuminate\\Routing\\Router')) {
                $router = $this->app->make('router');

                if (method_exists($router, 'aliasMiddleware')) {
                    $router->aliasMiddleware('authlog.enforce-lockout', EnforceLoginRateLimit::class);
                    $router->aliasMiddleware('authlog.block-suspicious', BlockSuspiciousLoginAttempt::class);
                    $router->aliasMiddleware('authlog.verify-session', VerifySessionFingerprint::class);
                }
            }
        });

        // Publish all middleware classes to app/Http/Middleware/AuthLog
        $this->publishes([
            __DIR__ . '/Http/Middleware/EnforceLoginRateLimit.php' => PathHelper::publishMiddlewarePath($this->app) . '/EnforceLoginRateLimit.php',
            __DIR__ . '/Http/Middleware/BlockSuspiciousLoginAttempt.php' => PathHelper::publishMiddlewarePath($this->app) . '/BlockSuspiciousLoginAttempt.php',
            __DIR__ . '/Http/Middleware/VerifySessionFingerprint.php' => PathHelper::publishMiddlewarePath($this->app) . '/VerifySessionFingerprint.php',
        ], 'authlog-middleware');


        // Load package views (default namespace)
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'authlog');

        // Register blade component namespace
        Blade::componentNamespace('Xultech\\AuthLogNotification\\View\\Components', 'authlog');

        // Publish all views (markdown + html + components)
        $this->publishes([
            __DIR__ . '/../resources/views' => PathHelper::publishPath($this->app),
        ], 'views');

        // Publish only Blade components
        $this->publishes([
            __DIR__ . '/../resources/views/components' => PathHelper::publishPath($this->app, 'components'),
        ], 'authlog-components');

        //Register query scopes (macros) for any model using HasAuthLogs
        AuthLogUserScopes::register();

        // Helper to resolve publish target path
        $targetPath = fn (string $path) => dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . $path;

        // Publish listeners, events, and notifications
        $this->publishes([
            __DIR__ . '/Listeners' => $targetPath('app/Listeners/AuthLog'),
        ], 'authlog-listeners');

        $this->publishes([
            __DIR__ . '/Events' => $targetPath('app/Events/AuthLog'),
        ], 'authlog-events');

        $this->publishes([
            __DIR__ . '/Notifications' => $targetPath('app/Notifications/AuthLog'),
        ], 'authlog-notifications');

        // ✅ Register event bindings
        $this->registerEventListeners();
    }

    protected function registerEventListeners(): void
    {
        if (! class_exists(\Illuminate\Support\Facades\Event::class)) {
            return; // Not running inside Laravel, skip event registration
        }

        Event::listen(
            \Illuminate\Auth\Events\Login::class,
            \Xultech\AuthLogNotification\Listeners\LoginEventListener::class
        );

        Event::listen(
            \Illuminate\Auth\Events\Failed::class,
            \Xultech\AuthLogNotification\Listeners\FailedLoginEventListener::class
        );

        Event::listen(
            \Illuminate\Auth\Events\Logout::class,
            \Xultech\AuthLogNotification\Listeners\LogoutEventListener::class
        );

        Event::listen(
            \Illuminate\Auth\Events\PasswordReset::class,
            \Xultech\AuthLogNotification\Listeners\PasswordResetEventListener::class
        );

        Event::listen(
            \Xultech\AuthLogNotification\Events\ReAuthenticated::class,
            \Xultech\AuthLogNotification\Listeners\ReAuthenticatedEventListener::class
        );
    }
}
