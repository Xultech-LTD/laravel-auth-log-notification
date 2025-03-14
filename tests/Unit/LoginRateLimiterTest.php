<?php
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Request;
use Xultech\AuthLogNotification\Services\LoginRateLimiter;

beforeEach(function () {
    Cache::clear(); // Clear cache before each test
    config()->set('authlog.lockout', [
        'enabled' => true,
        'key_prefix' => 'authlog:test:',
        'max_attempts' => 3,
        'lockout_minutes' => 1,
        'track_by' => 'email',
    ]);
});

test('can register failed attempts and trigger lockout', function () {
    $identifier = 'user@example.com';

    expect(LoginRateLimiter::isLockedOut($identifier))->toBeFalse();

    LoginRateLimiter::registerFailure($identifier);
    LoginRateLimiter::registerFailure($identifier);
    LoginRateLimiter::registerFailure($identifier);

    expect(LoginRateLimiter::attempts($identifier))->toBe(3);
    expect(LoginRateLimiter::isLockedOut($identifier))->toBeTrue();
});

test('can clear failed attempts and lockout', function () {
    $identifier = 'user@example.com';

    LoginRateLimiter::registerFailure($identifier);
    LoginRateLimiter::registerFailure($identifier);
    LoginRateLimiter::registerFailure($identifier);

    LoginRateLimiter::clear($identifier);

    expect(LoginRateLimiter::attempts($identifier))->toBe(0);
    expect(LoginRateLimiter::isLockedOut($identifier))->toBeFalse();
});

test('can resolve identifier by email', function () {
    $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);

    $id = LoginRateLimiter::resolveIdentifier($request, 'user@example.com');

    expect($id)->toBe('user@example.com');
});

test('can resolve identifier by ip', function () {
    config()->set('authlog.lockout.track_by', 'ip');

    $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '192.168.1.100']);

    $id = LoginRateLimiter::resolveIdentifier($request, null);

    expect($id)->toBe('192.168.1.100');
});

test('can resolve identifier by both', function () {
    config()->set('authlog.lockout.track_by', 'both');

    $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '192.168.1.100']);

    $id = LoginRateLimiter::resolveIdentifier($request, 'user@example.com');

    expect($id)->toBe('user@example.com|192.168.1.100');
});