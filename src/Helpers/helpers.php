<?php
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

if (! function_exists('authlog_geo')) {
    /**
     * Resolve the geo-location service from config and return the location data.
     *
     * @param string|null $ip
     * @return array
     */
    function authlog_geo(?string $ip = null): array
    {
        $class = Config::get('authlog.location_service');

        return App::make($class)->getGeoData($ip);
    }
}