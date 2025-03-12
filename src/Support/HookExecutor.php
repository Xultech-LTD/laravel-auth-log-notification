<?php

namespace Xultech\AuthLogNotification\Support;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Config;

/**
 * Executes user-defined hooks defined in config/authlog.php
 *
 * Supports closures, invokable classes, and listener-style handle() methods.
 */
class HookExecutor
{
    /**
     * Run a configured hook if it exists.
     *
     * @param string $key     Config key under 'hooks' (e.g., 'on_login')
     * @param mixed  $payload Data to pass to the hook (e.g., an event)
     * @return void
     */
    public static function run(string $key, mixed $payload = null): void
    {
        $hook = Config::get("authlog.hooks.$key");

        // Nothing defined
        if (! $hook) {
            return;
        }

        // If closure or global callable
        if (is_callable($hook)) {
            call_user_func($hook, $payload);
            return;
        }

        // If it's a class string
        if (is_string($hook) && class_exists($hook)) {
            $container = Container::getInstance();

            if (! $container) return;

            $instance = $container->make($hook);

            // Invokable class
            if (is_callable($instance)) {
                $instance($payload);
                return;
            }

            // Listener-style class with handle() method
            if (method_exists($instance, 'handle')) {
                $instance->handle($payload);
                return;
            }
        }

        // If it's none of the above, we ignore silently (fail-safe)
    }
}