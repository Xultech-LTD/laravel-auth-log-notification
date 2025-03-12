<?php

namespace Xultech\AuthLogNotification\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Xultech\AuthLogNotification\Constants\AuthEventLevel;
use Xultech\AuthLogNotification\Services\SuspicionDetector;
use Xultech\AuthLogNotification\Support\EventLevelResolver;

/**
 * Class AuthLog
 *
 * Stores authentication activity for authenticatable models (e.g., User, Admin).
 */
class AuthLog extends Model
{
    use  SoftDeletes;

    // Table name
    protected $table = 'auth_logs';

    // Mass assignment protection (allow all)
    protected $guarded = [];

    // Attribute casting
    protected $casts = [
        'login_at' => 'datetime',                // Convert login_at to Carbon instance
        'logout_at' => 'datetime',               // Convert logout_at to Carbon instance
        'is_mobile' => 'boolean',                // Ensure boolean values
        'is_new_device' => 'boolean',
        'is_new_location' => 'boolean',
        'metadata' => 'array',                   // Cast metadata as array
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Polymorphic relationship to authenticatable model (e.g., User, Admin)
     */
    public function authenticatable(): MorphTo
    {
        return $this->morphTo();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope: Only completed sessions (logged in + logged out)
     */
    public function scopeCompleted($query)
    {
        return $query->whereNotNull('logout_at');
    }

    /**
     * Scope: Only active sessions (logged in, not yet logged out)
     */
    public function scopeActive($query)
    {
        return $query->whereNull('logout_at');
    }

    /**
     * Scope: Only failed login attempts
     */
    public function scopeFailed($query)
    {
        return $query->where('event_level', AuthEventLevel::FAILED);
    }

    /**
     * Scope: Filter logs by IP address
     */
    public function scopeFromIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }

    /**
     * Scope: Filter logs by session ID
     */
    public function scopeWithSession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /*
    |--------------------------------------------------------------------------
    | State Checkers & Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Check if the session is still active (not logged out)
     */
    public function isActive(): bool
    {
        return is_null($this->logout_at);
    }

    /**
     * Check if this log is a failed login attempt
     */
    public function isFailed(): bool
    {
        return $this->event_level === AuthEventLevel::FAILED;
    }

    /**
     * Check if this log is a login event
     */
    public function isLogin(): bool
    {
        return $this->event_level === AuthEventLevel::LOGIN;
    }

    /**
     * Check if this log is a logout event
     */
    public function isLogout(): bool
    {
        return $this->event_level === AuthEventLevel::LOGOUT;
    }

    /**
     * Check if this log represents a suspicious session (new device or location)
     */
    public function isSuspicious(): bool
    {
        return SuspicionDetector::isSuspicious($this);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Get a formatted location string (e.g., "Enugu, Nigeria")
     */
    public function getFormattedLocationAttribute(): string
    {
        if ($this->country && $this->city) {
            return "{$this->city}, {$this->country}";
        }

        return $this->location ?? $this->ip_address ?? 'Unknown';
    }

    /**
     * Get a summary of the device used during login (OS / Browser (Device))
     */
    public function getDeviceSummaryAttribute(): string
    {
        $platform = $this->platform ?? 'Unknown OS';
        $browser = $this->browser ?? 'Unknown Browser';
        $device = $this->device ?? 'Unknown Device';

        return "{$platform} / {$browser} ({$device})";
    }

    /**
     * Get a user-friendly label of the event level (e.g., "Login", "Failed Login")
     */
    public function getEventTypeAttribute(): string
    {
        return EventLevelResolver::label($this->event_level);
    }

    /**
     * Get a formatted login timestamp (Y-m-d H:i:s)
     */
    public function getLoginAtFormattedAttribute(): ?string
    {
        return optional($this->login_at)->format('Y-m-d H:i:s');
    }

    /**
     * Get a formatted logout timestamp (Y-m-d H:i:s)
     */
    public function getLogoutAtFormattedAttribute(): ?string
    {
        return optional($this->logout_at)->format('Y-m-d H:i:s');
    }

    /**
     * Get just the domain part of the referrer (if available)
     */
    public function getReferrerDomainAttribute(): ?string
    {
        if (!$this->referrer) return null;

        return parse_url($this->referrer, PHP_URL_HOST);
    }

    /**
     * Get the first 80 characters of the user agent (abbreviated)
     */
    public function getUserAgentFragmentAttribute(): ?string
    {
        return $this->user_agent ? substr($this->user_agent, 0, 80) . '...' : null;
    }

    /*
    |--------------------------------------------------------------------------
    | Output Formatting
    |--------------------------------------------------------------------------
    */

    /**
     * Customize array output (e.g., for APIs, dashboards, etc.)
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        $array['formatted_location'] = $this->formatted_location;
        $array['device_summary'] = $this->device_summary;
        $array['event_type'] = $this->event_type;
        $array['login_at_formatted'] = $this->login_at_formatted;
        $array['logout_at_formatted'] = $this->logout_at_formatted;

        return $array;
    }
}
