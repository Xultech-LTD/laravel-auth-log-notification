<?php

use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Facade;
use Xultech\AuthLogNotification\Models\AuthLog;
use Xultech\AuthLogNotification\Listeners\LogoutEventListener;
use Xultech\AuthLogNotification\Support\HookExecutor;
use Tests\Stubs\UserStub;

beforeEach(function () {
    Facade::clearResolvedInstances();

    Config::set('authlog.enabled', true);
    Config::set('authlog.log_events.logout', true);
    Config::set('authlog.session_tracking.enabled', true);

    Session::start();

    // Migrate tables
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
        $table->string('event_level');
        $table->timestamp('login_at')->nullable();
        $table->timestamp('logout_at')->nullable();
        $table->string('session_id')->nullable()->index();
        $table->timestamps();
        $table->softDeletes();
    });
});

it('updates logout_at and runs hook on logout', function () {
    // Create a test user
    $user = new UserStub(['name' => 'Test']);
    $user->save();

    $sessionId = Session::getId();

    // Create a login record for the session
    $user->authentications()->create([
        'event_level' => 'login',
        'login_at' => \Carbon\Carbon::now()->subMinutes(5),
        'session_id' => $sessionId,
    ]);

    // Mock the hook runner
    $hookMock = Mockery::mock(HookExecutor::class);
    $hookMock->shouldReceive('run')
        ->once()
        ->with('on_logout', Mockery::on(fn ($data) =>
            isset($data['user'], $data['auth_log'], $data['session_id']) &&
            $data['user']->id === $user->id &&
            $data['session_id'] === $sessionId
        ));
    App::instance(HookExecutor::class, $hookMock);

    // Dispatch the event
    (new LogoutEventListener)->handle(new Logout('web', $user));

    // Assert the logout_at was set
    $updatedLog = $user->authentications()->first();
    expect($updatedLog->logout_at)->not()->toBeNull();
});

it('does nothing when logout logging is disabled', function () {
    Config::set('authlog.enabled', false); // Disable entire logging

    $user = new UserStub(['name' => 'Test']);
    $user->save();

    $sessionId = Session::getId();

    // Create a login record that should not be affected
    $user->authentications()->create([
        'event_level' => 'login',
        'login_at' => \Carbon\Carbon::now()->subMinutes(5),
        'session_id' => $sessionId,
    ]);

    // Mock the hook to ensure it's not called
    $hookMock = Mockery::mock(HookExecutor::class);
    $hookMock->shouldNotReceive('run');
    App::instance(HookExecutor::class, $hookMock);

    // Dispatch logout event
    (new LogoutEventListener)->handle(new Logout('web', $user));

    // Assert logout_at is still null
    $log = $user->authentications()->first();
    expect($log->logout_at)->toBeNull();
});
