<?php

namespace Xultech\AuthLogNotification\Services\GeoLocation;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

/**
 * Class GeoLocationService
 *
 * This is the default geo-location service using `torann/geoip`.
 * Developers may override this class by extending it and setting
 * their own implementation in `config('authlog.location_service')`.
 */

class GeoLocationService
{
    /**
     * Resolve geo-location details for a given IP address.
     *
     * @param string|null $ip
     * @return array
     */
    public function getGeoData(?string $ip = null): array
    {
        $ip = $ip ?? Request::ip();

        try {
            $location = geoip($ip);

            return [
                'ip'              => $ip,
                'country'         => $location->country ?? null,
                'country_code'    => $location->iso_code ?? null,
                'state'           => $location->state ?? null,
                'state_code'      =>  null,
                'city'            => $location->city ?? null,
                'postal_code'     => $location->postal_code ?? null,
                'latitude'        => $location->lat ?? null,
                'longitude'       => $location->lon ?? null,
                'timezone'        => $location->timezone ?? null,
                'continent'       => $location->continent ?? null,
                'continent_code'  =>  null,
                'raw'             => $location->toArray() ?? [],
            ];
        } catch (\Throwable $e) {
            Log::warning("[AuthLog] GeoIP lookup failed for IP {$ip}: {$e->getMessage()}");

            return [
                'ip' => $ip,
                'error' => true,
                'message' => $e->getMessage(),
            ];
        }
    }
}