<?php

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Testing\Fakes\NotificationFake;
use Xultech\AuthLogNotification\Models\AuthLog;
use Xultech\AuthLogNotification\Listeners\LoginEventListener;
use Xultech\AuthLogNotification\Notifications\LoginAlertNotification;
use Xultech\AuthLogNotification\Services\GeoLocation\GeoLocationService;
use Xultech\AuthLogNotification\Support\HookExecutor;
use Tests\Stubs\UserStub;

beforeEach(function () {
    // Clear facade cache to avoid leftover instances
    Facade::clearResolvedInstances();

    // Manually create and bind the Notification fake
    $fakeNotification = new NotificationFake();
    Notification::swap($fakeNotification);
    app()->instance('notification', $fakeNotification); //

    // ✅ Migrate schema
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
});

it('creates a log and sends notification on login', function () {
    // ✅ Set config values
    Config::set('authlog.enabled', true);
    Config::set('authlog.log_events.login', true);
    Config::set('authlog.session_tracking.session_fingerprint.store_in_session', true);

    // ✅ Start session
    Session::start();
    Session::put('authlog_fingerprint', null);

    // ✅ Ensure Notification is still fake
    Notification::swap(new NotificationFake());

    // ✅ Create a user with the custom method
    $user = new class extends UserStub {

    };
    $user->name = 'Test User';
    $user->save();

    // ✅ Mock GeoLocationService
    $geoMock = Mockery::mock(GeoLocationService::class);
    $geoMock->shouldReceive('getGeoData')->andReturn([
        'country' => 'Nigeria',
        'city' => 'Enugu',
        'location' => 'Nigeria, Enugu',
        'browser' => 'Chrome',
        'platform' => 'Windows',
        'device' => 'PC',
        'is_mobile' => false,
    ]);
    App::instance(GeoLocationService::class, $geoMock);

    // ✅ Mock HookExecutor
    $hookMock = Mockery::mock(HookExecutor::class);
    $hookMock->shouldReceive('run')
        ->once()
        ->with('on_login', Mockery::on(fn ($data) => isset($data['user'], $data['auth_log'])));
    App::instance(HookExecutor::class, $hookMock);

    // ✅ Simulate a request
    $request = \Illuminate\Http\Request::create('/', 'GET', [], [], [], [
        'REMOTE_ADDR' => '192.168.1.2',
        'HTTP_USER_AGENT' => 'Mozilla/5.0',
        'HTTP_REFERER' => 'https://example.com',
    ]);
    App::instance('request', $request);

    // ✅ Dispatch login event
    $event = new Login('web', $user, false);
    (new LoginEventListener)->handle($event);

    // ✅ Assert log saved
    $log = AuthLog::first();
    expect($log)->not()->toBeNull()
        ->and($log->city)->toBe('Enugu');

    // ✅ Assert notification sent
    Notification::assertSentTo($user, LoginAlertNotification::class);

    // ✅ Assert session fingerprint stored
    expect(Session::get('authlog_fingerprint'))->not()->toBeNull();
});
