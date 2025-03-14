<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\Stubs\UserStub;

beforeEach(function () {
    // Drop and recreate the user_stubs table
    Schema::dropIfExists('user_stubs');

    Schema::create('user_stubs', function (Blueprint $table) {
        $table->id();
        $table->string('name')->nullable();
    });

    Schema::dropIfExists('auth_logs');

    Schema::create('auth_logs', function (Blueprint $table) {
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

it('can record and retrieve last login IP and timestamp', function () {
    $user = UserStub::create(['name' => 'Test']);

    $user->authentications()->create([
        'event_level' => 'login',
        'ip_address' => '192.168.1.1',
        'login_at' => \Carbon\Carbon::now()->subDay(),
    ]);

    expect($user->lastLoginIp())->toBe('192.168.1.1')
        ->and($user->hasLoggedInBefore())->toBeTrue();
});

it('can detect failed login count and suspicious activity', function () {
    $user = UserStub::create(['name' => 'Test']);

    $user->authentications()->createMany([
        ['event_level' => 'failed'],
        ['event_level' => 'failed'],
        ['event_level' => 'login', 'is_new_device' => true],
    ]);

    expect($user->failedLoginsCount())->toBe(2)
        ->and($user->suspiciousLogins()->count())->toBe(1)
        ->and($user->lastLoginWasSuspicious())->toBeTrue();
});
