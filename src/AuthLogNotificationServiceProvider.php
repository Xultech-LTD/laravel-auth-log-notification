<?php

namespace Xultech\AuthLogNotification;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Xultech\AuthLogNotification\Console\Commands\CleanAuthLogs;
use Xultech\AuthLogNotification\Console\Commands\PruneSuspiciousLogs;
use Xultech\AuthLogNotification\Console\Commands\SyncGeoLocation;
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

    }

}
