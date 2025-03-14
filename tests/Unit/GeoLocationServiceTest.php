<?php

use Xultech\AuthLogNotification\Services\GeoLocation\GeoLocationService;
use Illuminate\Support\Facades\App;

use Mockery as m;

beforeEach(function () {
    // Mock geoip to return an object
    $mockLocation = new class {
        public $ip = '123.123.123.123';
        public $city = 'Lagos';
        public $country = 'Nigeria';

        public function toArray()
        {
            return [
                'ip' => $this->ip,
                'city' => $this->city,
                'country' => $this->country,
            ];
        }
    };

    $mock = m::mock();
    $mock->shouldReceive('getLocation')->andReturn($mockLocation);

    app()->instance('geoip', $mock);
});

it('returns location data for valid IP', function () {
    $service = new GeoLocationService();

    $result = $service->getGeoData('123.123.123.123');

    expect($result)->toHaveKey('ip', '123.123.123.123')
        ->toHaveKey('city', 'Lagos')
        ->toHaveKey('country', 'Nigeria');
});

it('returns empty data when geo lookup fails', function () {
    // Override geoip with failing mock
    $mock = m::mock();
    $mock->shouldReceive('getLocation')->andThrow(new Exception('Geo service unavailable'));
    app()->instance('geoip', $mock);

    $service = new GeoLocationService();
    $result = $service->getGeoData('255.255.255.255');

    expect($result)->toBeArray()
        ->and($result)->not()->toHaveKey('city')
        ->and($result)->not()->toHaveKey('country');
});
