<?php

namespace Xultech\AuthLogNotification\Helpers;


use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Class GeoLocation
 *
 * Detects user geo-location from an IP address using the torann/geoip package.
 * Retrieves detailed location information for enhanced logging, analytics,
 * and security features.
 *
 * @package Xultech\AuthLogNotification\Helpers
 */

class GeoLocation
{
    /**
     * IP address to resolve.
     *
     * @var string|null
     */
    protected ?string $ip;

    /**
     * Parsed geo data result.
     *
     * @var array|null
     */
    protected ?array $data = null;

    /**
     * GeoLocation constructor.
     *
     * @param string|null $ip
     */
    public function __construct(?string $ip = null)
    {
        $this->ip = $ip ?? Request::ip();
        $this->resolve();
    }

    /**
     * Resolve location using the geoip() helper.
     *
     * Wraps in try/catch for safe fallback.
     */
    protected function resolve(): void
    {
        try {
            $location = geoip($this->ip);

            $this->data = [
                'ip'              => $this->ip,
                'country'         => $location->country ?? null,
                'country_code'    => $location->iso_code ?? null,
                'state'           => $location->state ?? null,
                'state_code'      => $location->state_code ?? null,
                'city'            => $location->city ?? null,
                'postal_code'     => $location->postal_code ?? null,
                'latitude'        => $location->lat ?? null,
                'longitude'       => $location->lon ?? null,
                'timezone'        => $location->timezone ?? null,
                'continent'       => $location->continent ?? null,
                'continent_code'  => $location->continent_code ?? null,
            ];
        } catch (\Throwable $e) {
            Log::warning("GeoLocation lookup failed for IP {$this->ip}: {$e->getMessage()}");
            $this->data = null;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Primary Accessors
    |--------------------------------------------------------------------------
    */

    public function ip(): ?string
    {
        return $this->data['ip'] ?? null;
    }

    public function country(): ?string
    {
        return $this->data['country'] ?? null;
    }

    public function countryCode(): ?string
    {
        return $this->data['country_code'] ?? null;
    }

    public function city(): ?string
    {
        return $this->data['city'] ?? null;
    }

    public function state(): ?string
    {
        return $this->data['state'] ?? null;
    }

    public function stateCode(): ?string
    {
        return $this->data['state_code'] ?? null;
    }

    public function latitude(): ?float
    {
        return $this->data['latitude'] ?? null;
    }

    public function longitude(): ?float
    {
        return $this->data['longitude'] ?? null;
    }

    public function timezone(): ?string
    {
        return $this->data['timezone'] ?? null;
    }

    public function continent(): ?string
    {
        return $this->data['continent'] ?? null;
    }

    public function continentCode(): ?string
    {
        return $this->data['continent_code'] ?? null;
    }

    /*
    |--------------------------------------------------------------------------
    | Fallback / Summary Formatters
    |--------------------------------------------------------------------------
    */

    /**
     * A readable, formatted summary of location.
     */
    public function formatted(): string
    {
        if ($this->city() && $this->country()) {
            return "{$this->city()}, {$this->country()}";
        }

        if ($this->country()) {
            return $this->country();
        }

        return "IP: {$this->ip()}";
    }

    /**
     * Export all geo data as an associative array.
     *
     * @return array|null
     */
    public function toArray(): ?array
    {
        return $this->data;
    }
}