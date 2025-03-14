<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use Xultech\AuthLogNotification\Events\ReAuthenticated;
use Xultech\AuthLogNotification\Listeners\ReAuthenticatedEventListener;
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
    Config::set('authlog.log_events.re-authenticated', true);

    Session::start();
});

it('logs re-authentication and triggers hook', function () {
    // 🔹 User setup
    $user = new UserStub(['name' => 'Test']);
    $user->save();

    // 🔹 Mock GeoLocationService
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

    // 🔹 HookExecutor mock
    $hookMock = Mockery::mock(HookExecutor::class);
    $hookMock->shouldReceive('run')
        ->once()
        ->with('on_re_authenticated', Mockery::on(fn ($data) =>
        isset($data['user'], $data['auth_log'], $data['request'])
        ));
    App::instance(HookExecutor::class, $hookMock);

    // 🔹 Fake request
    $request = Request::create('/re-auth', 'POST', [], [], [], [
        'REMOTE_ADDR' => '192.168.1.2',
        'HTTP_USER_AGENT' => 'Mozilla/5.0',
        'HTTP_REFERER' => 'https://example.com',
    ]);
    App::instance('request', $request);

    // 🔹 Dispatch event
    $event = new ReAuthenticated($user);
    (new ReAuthenticatedEventListener)->handle($event);

    // 🔹 Assert log
    $log = AuthLog::first();
    expect($log)->not()->toBeNull()
        ->and($log->city)->toBe('Enugu')
        ->and($log->event_level)->toBe('re-authenticated');
});
