<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('auth_logs', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship to any authenticatable model
            $table->morphs('authenticatable');

            // IP and location data
            $table->ipAddress('ip_address')->nullable();
            $table->string('country')->nullable();     // e.g., Nigeria
            $table->string('city')->nullable();        // e.g., Enugu
            $table->string('location')->nullable();    // e.g., Nigeria, Enugu (fallback summary)

            // Device & environment
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            $table->string('device')->nullable();
            $table->boolean('is_mobile')->default(false);

            // Raw details
            $table->text('user_agent')->nullable();
            $table->text('referrer')->nullable(); // Could also use JSON if structured

            // Optional dump of agent/geo metadata
            $table->json('metadata')->nullable();

            // Security flags
            $table->boolean('is_new_device')->default(false);
            $table->boolean('is_new_location')->default(false);

            // Login activity
            $table->timestamp('login_at')->nullable();
            $table->timestamp('logout_at')->nullable();

            // Event level (login, logout, failed, password_reset, etc.)
            $table->string('event_level')->default('login');

            // Session tracking
            $table->string('session_id')->nullable()->index();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_logs');
    }
};
