<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;
use Xultech\AuthLogNotification\Models\AuthLog;
use Xultech\AuthLogNotification\Http\Middleware\BlockSuspiciousLoginAttempt;
use Tests\Stubs\UserStub;
use Xultech\AuthLogNotification\Services\GeoLocation\GeoLocationService;

beforeEach(function () {
    // ✅ Migrate schema
    Schema::dropIfExists('user_stubs');
    Schema::dropIfExists('auth_logs');

    Schema::create('user_stubs', function ($table) {
        $table->id();
        $table->string('name')->nullable();
        $table->string('email')->nullable();
        $table->string('password')->nullable();
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
    // Seed a known user
    $user = UserStub::create([
        'name' => 'Test User',
        'email' => 'known@example.com',
        'password' => "TestPassword",
    ]);

    Config::set('authlog.middleware_blocking.user_model', UserStub::class);
    Config::set('authlog.suspicion_rules.new_device', true);
    Config::set('authlog.suspicion_rules.new_location', true);
    Config::set('authlog.suspicion_rules.block_suspicious_logins', true);

    Config::set('authlog.suspicious_login_handler', \Xultech\AuthLogNotification\Handlers\SuspiciousLoginHandler::class);



    $type = Config::get('authlog.middleware_blocking.user_model');

    // Create known AuthLog for that user
    AuthLog::create([
        'authenticatable_type' => $type,
        'authenticatable_id'   => $user->id,
        'ip_address'           => '192.168.1.1',
        'user_agent'           => 'Mozilla/5.0',
        'event_level'          => 'login',
        'is_new_device'        => false,
        'is_new_location'      => false,
        'login_at'             => \Carbon\Carbon::now(),
    ]);



    // Base config
    Config::set('authlog.enabled', true);
    Config::set('authlog.suspicion_rules.block_suspicious_logins', true);
    Config::set('authlog.middleware_blocking.enabled', true);
    Config::set('authlog.middleware_blocking.user_model', UserStub::class);
    Config::set('authlog.middleware_blocking.email_column', 'email');
    Config::set('authlog.middleware_blocking.request_input_key', 'email');

    // Bind mock GeoLocationService
    app()->bind(GeoLocationService::class, fn () => new class {
        public function getGeoData(string $ip): array {
            return [
                'country' => 'Nigeria',
                'city' => 'Lagos',
                'location' => 'Nigeria, Lagos',
                'browser' => 'Firefox',
                'platform' => 'Linux',
                'device' => 'Desktop',
                'is_mobile' => false,
            ];
        }
    });
});

it('allows request if login is not suspicious', function () {
    $middleware = new BlockSuspiciousLoginAttempt();

    $request = Request::create('/login', 'POST', [
        'email' => 'known@example.com',
    ], [], [], [
        'REMOTE_ADDR' => '192.168.1.1',
        'HTTP_USER_AGENT' => 'Mozilla/5.0',
    ]);

    $response = $middleware->handle($request, fn () => new Response('OK'));

    expect($response->getContent())->toBe('OK');
});

it('blocks request if login is from new device or location', function () {
    $middleware = new BlockSuspiciousLoginAttempt();

    $request = Request::create('/login', 'POST', [
        'email' => 'known@example.com',
    ]);

    $request->server->set('REMOTE_ADDR', '10.10.10.10'); // New IP
    $request->headers->set('User-Agent', 'DifferentAgent/1.0'); // New agent

    $response = $middleware->handle($request, fn () => new Response('OK'));

    expect($response->getStatusCode())->toBe(Response::HTTP_FORBIDDEN);
    expect($response->getContent())->toContain('Login blocked');
});


it('skips blocking if user is not found', function () {
    $middleware = new BlockSuspiciousLoginAttempt();

    $request = Request::create('/login', 'POST', [
        'email' => 'unknown@example.com',
    ], [], [], [
        'REMOTE_ADDR' => '1.2.3.4',
        'HTTP_USER_AGENT' => 'Mozilla/5.0',
    ]);

    $response = $middleware->handle($request, fn () => new Response('OK'));

    expect($response->getContent())->toBe('OK');
});
