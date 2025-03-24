<?php
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Xultech\AuthLogNotification\Http\Middleware\EnforceLoginRateLimit;
use Xultech\AuthLogNotification\Services\LoginRateLimiter;

beforeEach(function () {
    config()->set('authlog.lockout', [
        'enabled' => true,
        'key_prefix' => 'authlog:test:',
        'max_attempts' => 2,
        'lockout_minutes' => 1,
        'track_by' => 'email',
        'generic_response' => true,
    ]);
});

it('allows login if under the limit', function () {
    $request = Request::create('/login', 'POST', ['email' => 'test@example.com']);
    $middleware = new EnforceLoginRateLimit();

    $response = $middleware->handle($request, fn () => new Response('OK'));

    expect($response->getContent())->toBe('OK');
});

it('blocks login if over the limit', function () {
    $request = Request::create('/login', 'POST', ['email' => 'locked@example.com']);
    $identifier = LoginRateLimiter::resolveIdentifier($request, 'locked@example.com');

    // Simulate lockout
    LoginRateLimiter::registerFailure($identifier);
    LoginRateLimiter::registerFailure($identifier);

    $middleware = new EnforceLoginRateLimit();
    $response = $middleware->handle($request, fn () => new Response('SHOULD_NOT_HAPPEN'));

    expect($response->getStatusCode())->toBe(Response::HTTP_TOO_MANY_REQUESTS);
    expect($response->getContent())->toContain('Too many login attempts');
});

it('allows request if not locked out', function () {
    $middleware = new EnforceLoginRateLimit();

    $request = Request::create('/login', 'POST', ['email' => 'test@example.com']);

    $response = $middleware->handle($request, fn () => new Response('OK'));

    expect($response->getContent())->toBe('OK');
});

it('blocks request if too many attempts (generic)', function () {
    $middleware = new EnforceLoginRateLimit();

    $email = 'test@example.com';
    $request = Request::create('/login', 'POST', ['email' => $email]);

    $identifier = LoginRateLimiter::resolveIdentifier($request, $email);

    // Simulate failed attempts
    LoginRateLimiter::registerFailure($identifier);
    LoginRateLimiter::registerFailure($identifier);

    $response = $middleware->handle($request, fn () => new Response('OK'));

    expect($response->getStatusCode())->toBe(Response::HTTP_TOO_MANY_REQUESTS);
    expect($response->getContent())->toContain('Too many login attempts');
});

it('redirects if not using generic response', function () {
    config()->set('authlog.lockout.generic_response', false);
    config()->set('authlog.lockout.redirect_to', '/custom-login');

    $middleware = new EnforceLoginRateLimit();

    $email = 'test@example.com';
    $request = Request::create('/login', 'POST', ['email' => $email]);

    $identifier = LoginRateLimiter::resolveIdentifier($request, $email);

    // Simulate lockout
    LoginRateLimiter::registerFailure($identifier);
    LoginRateLimiter::registerFailure($identifier);

    $response = $middleware->handle($request, fn () => new Response('OK'));

    expect($response->isRedirect('/custom-login'))->toBeTrue();
});