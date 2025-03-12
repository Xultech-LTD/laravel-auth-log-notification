<?php

namespace Xultech\AuthLogNotification\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Xultech\AuthLogNotification\Models\AuthLog;
use Illuminate\Support\Facades\Config;

/**
 * Class CleanAuthLogs
 *
 * This command deletes old authentication logs based on the configured
 * retention policy in `config/authlog.php`.
 *
 * Retention settings:
 * - enabled: boolean, whether cleanup is active
 * - days: number of days to retain logs
 * - delete_method: 'soft' or 'hard' delete
 *
 * Usage:
 * php artisan authlog:clean
 */

class CleanAuthLogs extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'authlog:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old auth logs based on retention policy';

    public function handle(): int
    {
        // Get config values
        $enabled = Config::get('authlog.retention.enabled', true);
        $days = Config::get('authlog.retention.days', 90);
        $method = Config::get('authlog.retention.delete_method', 'soft');

        // Skip if retention is disabled
        if (! $enabled) {
            $this->info('AuthLog retention is disabled. Nothing was cleaned.');
            return self::SUCCESS;
        }

        // Determine cutoff date
        $cutoff = Carbon::now()->subDays($days);

        // Build query for logs older than cutoff
        $query = AuthLog::where('created_at', '<', $cutoff);
        $count = $query->count();

        // If no logs found, skip deletion
        if ($count === 0) {
            $this->info('No auth logs found for cleanup.');
            return self::SUCCESS;
        }

        // Perform deletion (soft or hard)
        if ($method === 'soft') {
            $query->delete(); // Soft delete (requires SoftDeletes trait)
        } else {
            $query->forceDelete(); // Permanent deletion
        }

        $this->info("✔ {$count} auth logs cleaned (older than {$days} days).");

        return self::SUCCESS;
    }
}