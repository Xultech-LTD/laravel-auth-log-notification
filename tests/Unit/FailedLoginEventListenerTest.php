<?php

use Illuminate\Auth\Events\Failed;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\App;
use Xultech\AuthLogNotification\Listeners\FailedLoginEventListener;
use Xultech\AuthLogNotification\Models\AuthLog;
use Xultech\AuthLogNotification\Services\GeoLocation\GeoLocationService;
use Xultech\AuthLogNotification\Services\LoginRateLimiter;
use Xultech\AuthLogNotification\Support\HookExecutor;

beforeEach(function () {
    Schema::dropIfExists('auth_logs');

    Schema::create('auth_logs', function ($table) {
        $table->id();
        $table->string('authenticatable_type')->nullable();
        $table->unsignedBigInteger('authenticatable_id')->nullable();
        $table->ipAddress('ip_address')->nullable();
        $table->string('country')->nullable();
        $table->string('city')->nullable();
        $table->string('location')->nullable();
        $table->string('browser')->nullable();
        $table->string('platform')->nullable();
        $table->string('device')->nullable();
        $table->boolean('is_mobile')->default(false);
        $table->text('user_agent')->nullable();
        $table->text('referrer')->nullable();
        $table->json('metadata')->nullable();
        $table->boolean('is_new_device')->default(false);
        $table->boolean('is_new_location')->default(false);
        $table->timestamp('login_at')->nullable();
        $table->timestamp('logout_at')->nullable();
        $table->string('event_level')->default('login');
        $table->string('session_id')->nullable()->index();
        $table->timestamps();
        $table->softDeletes();
    });

    Config::set('authlog.enabled', true);
    Config::set('authlog.log_events.failed_login', true);
});

it('logs failed login attempt, registers failure, and triggers hook', function () {
    // 🧪 Mock GeoLocationService
    $geoMock = Mockery::mock(GeoLocationService::class);
    $geoMock->shouldReceive('getGeoData')->andReturn([
        'country' => 'Nigeria',
        'city' => 'Enugu',
        'location' => 'Nigeria, Enugu',
        'browser' => 'Firefox',
        'platform' => 'Linux',
        'device' => 'PC',
        'is_mobile' => false,
    ]);
    App::instance(GeoLocationService::class, $geoMock);

    // 🧪 Spy LoginRateLimiter
    $rateLimiter =  Mockery::mock(LoginRateLimiter::class);
    $rateLimiter->shouldReceive('resolveIdentifier')->andReturn('192.168.1.1|test@example.com');
    $rateLimiter->shouldReceive('registerFailure')->once();
    App::instance(LoginRateLimiter::class, $rateLimiter);

    // 🧪 HookExecutor mock
    $hookMock = Mockery::mock(HookExecutor::class);
    $hookMock->shouldReceive('run')
        ->once()
        ->with('on_failed', Mockery::on(fn ($data) => $data['email'] === 'test@example.com'));
    App::instance(HookExecutor::class, $hookMock);

    // Simulate request
    $request = \Illuminate\Http\Request::create('/login', 'POST', [], [], [], [
        'REMOTE_ADDR' => '192.168.1.1',
        'HTTP_USER_AGENT' => 'Mozilla/5.0',
        'HTTP_REFERER' => 'https://referrer.test',
    ]);
    App::instance('request', $request);

    $event = new Failed('web', null, ['email' => 'test@example.com']);
    (new FailedLoginEventListener)->handle($event);

    $log = AuthLog::first();
    expect($log)->not()->toBeNull()
        ->and($log->event_level)->toBe('failed')
        ->and($log->ip_address)->toBe('192.168.1.1')
        ->and($log->city)->toBe('Enugu');
});
