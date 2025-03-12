<?php

namespace Xultech\AuthLogNotification\Helpers;


use Illuminate\Support\Facades\Request;
use Jenssegers\Agent\Agent;

/**
 * Class DeviceInfo
 *
 * Parses User-Agent strings and returns detailed information about
 * the user's browser, platform (OS), device type, and whether it's mobile.
 *
 * This class wraps Jenssegers\Agent to provide a clean and reusable interface
 * for extracting device-related metadata.
 *
 * @package Xultech\AuthLogNotification\Helpers
 */
class DeviceInfo
{
    /**
     * The Jenssegers Agent instance used for device detection.
     *
     * @var Agent
     */
    protected Agent $agent;

    /**
     * The original user agent string.
     *
     * @var string|null
     */
    protected ?string $userAgent;

    /**
     * DeviceInfo constructor.
     *
     * @param string|null $userAgent Optional User-Agent string.
     *                                If null, it will use the current request's User-Agent.
     */
    public function __construct(?string $userAgent = null)
    {
        $this->userAgent = $userAgent ?? Request::userAgent();

        $this->agent = new Agent();
        $this->agent->setUserAgent($this->userAgent);
    }

    /*
    |--------------------------------------------------------------------------
    | Primary Device Properties
    |--------------------------------------------------------------------------
    */

    /**
     * Get the browser name (e.g., Chrome, Safari, Firefox).
     *
     * @return string
     */
    public function browser(): string
    {
        return $this->agent->browser() ?? 'Unknown Browser';
    }

    /**
     * Get the platform or operating system (e.g., Windows, Android, macOS).
     *
     * @return string
     */
    public function platform(): string
    {
        return $this->agent->platform() ?? 'Unknown OS';
    }

    /**
     * Get the device name (e.g., iPhone, Samsung, Infinix).
     * If no device is identified, fallback to "Unknown Device".
     *
     * @return string
     */
    public function device(): string
    {
        return $this->agent->device() ?? 'Unknown Device';
    }

    /**
     * Check whether the user is on a mobile device.
     *
     * @return bool
     */
    public function isMobile(): bool
    {
        return $this->agent->isMobile();
    }

    /*
    |--------------------------------------------------------------------------
    | Raw and Structured Output
    |--------------------------------------------------------------------------
    */

    /**
     * Get the original User-Agent string from the request or injected input.
     *
     * @return string
     */
    public function userAgent(): string
    {
        return $this->userAgent ?? 'Unknown';
    }

    /**
     * Return all parsed data in a clean associative array format.
     * Ideal for storing in metadata fields or debugging logs.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'browser'     => $this->browser(),
            'platform'    => $this->platform(),
            'device'      => $this->device(),
            'is_mobile'   => $this->isMobile(),
            'user_agent'  => $this->userAgent(),
        ];
    }

    /**
     * Return a brief, readable summary string of the device information.
     * Format: "Platform / Browser (Device)"
     *
     * Example: "Windows / Chrome (HP Laptop)"
     *
     * @return string
     */
    public function summary(): string
    {
        return "{$this->platform()} / {$this->browser()} ({$this->device()})";
    }

    /*
    |--------------------------------------------------------------------------
    | Convenience Helpers (Optional)
    |--------------------------------------------------------------------------
    */

    /**
     * Determine if the user is on a desktop/laptop device.
     *
     * @return bool
     */
    public function isDesktop(): bool
    {
        return !$this->isMobile();
    }

    /**
     * Return the agent instance for advanced use cases (e.g., bot detection).
     *
     * @return Agent
     */
    public function getAgent(): Agent
    {
        return $this->agent;
    }
}