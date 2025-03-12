<?php

use Xultech\AuthLogNotification\Support\HookExecutor;

beforeEach(function () {
    config()->set('authlog.hooks', [
        'on_login' => null,
        'on_logout' => null,
        'on_failed' => null,
    ]);
});

test('executes a closure hook', function () {
    $output = null;

    config()->set('authlog.hooks.on_login', function ($event) use (&$output) {
        $output = "Closure says: {$event['user_id']}";
    });

    HookExecutor::run('on_login', ['user_id' => 10]);

    expect($output)->toBe("Closure says: 10");
});

test('executes an invokable class hook', function () {
    config()->set('authlog.hooks.on_login', InvokableHook::class);

    ob_start(); // Start output buffering
    HookExecutor::run('on_login', ['user_id' => 20]);
    $output = ob_get_clean(); // End buffer and get output

    expect($output)->toBe("Invoked: 20\n");
});

test('executes a handle() method class hook', function () {
    config()->set('authlog.hooks.on_login', HandleHook::class);

    ob_start();
    HookExecutor::run('on_login', ['user_id' => 30]);
    $output = ob_get_clean();

    expect($output)->toBe("Handled: 30\n");
});

class InvokableHook
{
    public function __invoke($event)
    {
        echo "Invoked: {$event['user_id']}\n";
    }
}

class HandleHook
{
    public function handle($event)
    {
        echo "Handled: {$event['user_id']}\n";
    }
}
