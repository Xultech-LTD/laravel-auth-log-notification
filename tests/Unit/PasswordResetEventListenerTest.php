<?php

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Xultech\AuthLogNotification\Listeners\PasswordResetEventListener;
use Xultech\AuthLogNotification\Models\AuthLog;
use Xultech\AuthLogNotification\Services\GeoLocation\GeoLocationService;
use Xultech\AuthLogNotification\Support\HookExecutor;
use Tests\Stubs\UserStub;

beforeEach(function () {
    Schema::dropIfExists('user_stubs');
    Schema::dropIfExists('auth_logs');

    Schema::create('user_stubs', function ($table) {
        $table->id();
        $table->string('name')->nullable();
        $table->timestamps();
    });

    Schema::create('auth_logs', function ($table) {
        $table->id();
        $table->morphs('authenticatable');
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
    Config::set('authlog.log_events.password_reset', true);
    Session::start();
});

it('logs password reset and triggers hook', function () {
    $user = new UserStub(['name' => 'ResetUser']);
    $user->save();

    // Mock GeoLocationService
    $geoMock = Mockery::mock(GeoLocationService::class);
    $geoMock->shouldReceive('getGeoData')->andReturn([
        'country' => 'Nigeria',
        'city' => 'Lagos',
        'location' => 'Nigeria, Lagos',
        'browser' => 'Safari',
        'platform' => 'macOS',
        'device' => 'Macbook',
        'is_mobile' => false,
    ]);
    App::instance(GeoLocationService::class, $geoMock);

    // HookExecutor mock
    $hookMock = Mockery::mock(HookExecutor::class);
    $hookMock->shouldReceive('run')
        ->once()
        ->with('on_password_reset', Mockery::on(fn ($data) =>
        isset($data['user'], $data['auth_log'], $data['request'])
        ));
    App::instance(HookExecutor::class, $hookMock);

    // Fake request
    $request = Request::create('/reset-password', 'POST', [], [], [], [
        'REMOTE_ADDR' => '127.0.0.1',
        'HTTP_USER_AGENT' => 'Safari/14.1',
        'HTTP_REFERER' => 'https://example.com/reset',
    ]);
    App::instance('request', $request);

    $event = new PasswordReset($user);
    (new PasswordResetEventListener)->handle($event);

    $log = AuthLog::first();
    expect($log)->not()->toBeNull()
        ->and($log->city)->toBe('Lagos')
        ->and($log->event_level)->toBe('password_reset');
});
