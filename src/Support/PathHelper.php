<?php

namespace Xultech\AuthLogNotification\Support;

use Illuminate\Contracts\Foundation\Application;

class PathHelper
{
    /**
     * Resolve the resource path for publishing package views/components.
     *
     * @param Application $app
     * @param string $subPath
     * @return string
     */
    public static function publishPath(Application $app, string $subPath = ''): string
    {
        $subPath = trim($subPath, '/');

        // Laravel-aware resolution
        if (method_exists($app, 'resourcePath')) {
            return $app->resourcePath("views/vendor/authlog" . ($subPath ? "/{$subPath}" : ''));
        }

        // Fallback for standalone/test mode
        return dirname(__DIR__, 2) . "/resources/views/vendor/authlog" . ($subPath ? "/{$subPath}" : '');
    }

    public static function publishMiddlewarePath(Application $app): string
    {
        // Laravel-aware: publish to app/Http/Middleware/AuthLog/
        if (method_exists($app, 'basePath')) {
            return $app->basePath('app/Http/Middleware/AuthLog');
        }

        // Fallback for standalone environments
        return dirname(__DIR__, 2) . '/app/Http/Middleware/AuthLog';
    }
    public static function publishListenerPath(Application $app): string
    {
        return method_exists($app, 'basePath')
            ? $app->basePath('app/Listeners/AuthLog')
            : dirname(__DIR__, 2) . '/app/Listeners/AuthLog';
    }

    public static function publishEventPath(Application $app): string
    {
        return method_exists($app, 'basePath')
            ? $app->basePath('app/Events/AuthLog')
            : dirname(__DIR__, 2) . '/app/Events/AuthLog';
    }

    public static function publishNotificationPath(Application $app): string
    {
        return method_exists($app, 'basePath')
            ? $app->basePath('app/Notifications/AuthLog')
            : dirname(__DIR__, 2) . '/app/Notifications/AuthLog';
    }

}