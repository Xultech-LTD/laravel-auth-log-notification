<?php

namespace Xultech\AuthLogNotification\Console\Commands;

use Illuminate\Console\Command;
use Xultech\AuthLogNotification\Models\AuthLog;
use Xultech\AuthLogNotification\Services\GeoLocation\GeoLocationService;
use Illuminate\Support\Facades\Config;

/**
 * Class SyncGeoLocation
 *
 * This command loops through existing AuthLogs and re-fetches
 * geo-location data (country, city, etc.) using the configured service.
 *
 * Usage:
 * php artisan authlog:sync-geo
 */
class SyncGeoLocation extends Command
{
    protected $signature = 'authlog:sync-geo 
                            {--limit=500 : Limit the number of records to process} 
                            {--missing-only : Only update logs missing geo data}';

    protected $description = 'Update geo-location data for auth logs using the configured location service';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        $onlyMissing = $this->option('missing-only');

        // Resolve configured geo service
        $geoServiceClass = Config::get('authlog.location_service');
        $geoService = $this->laravel->make($geoServiceClass);

        $query = AuthLog::query();

        if ($onlyMissing) {
            $query->whereNull('country')->orWhereNull('city');
        }

        $logs = $query->latest()->limit($limit)->get();

        if ($logs->isEmpty()) {
            $this->info('No logs found for geo sync.');
            return self::SUCCESS;
        }

        $this->info("🔄 Syncing geo-location data for {$logs->count()} records...");

        foreach ($logs as $log) {
            if (! $log->ip_address) {
                continue;
            }

            $geo = $geoService->getGeoData($log->ip_address);

            if ($geo && is_array($geo)) {
                $log->country = $geo['country'] ?? $log->country;
                $log->city = $geo['city'] ?? $log->city;
                $log->location = $geo['location'] ?? "{$log->city}, {$log->country}";
                $log->metadata = array_merge($log->metadata ?? [], ['geo' => $geo]);
                $log->save();
            }
        }

        $this->info('✅ Geo-location sync complete.');

        return self::SUCCESS;
    }
}