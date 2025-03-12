<?php

namespace Xultech\AuthLogNotification\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Xultech\AuthLogNotification\Models\AuthLog;
use Carbon\Carbon;

/**
 * Class PruneSuspiciousLogs
 *
 * This command finds and removes login entries that are flagged as suspicious,
 * based on either new device or new location flags.
 *
 * Optionally, this command can support archiving in the future.
 *
 * Usage:
 * php artisan authlog:prune-suspicious
 */

class PruneSuspiciousLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'authlog:prune-suspicious';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete or archive suspicious login sessions (new device/location)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Optionally support disabling via config later
        $query = AuthLog::query()
            ->where(function ($query) {
                $query->where('is_new_device', true)
                    ->orWhere('is_new_location', true);
            });

        $count = $query->count();

        if ($count === 0) {
            $this->info('No suspicious logs found to prune.');
            return self::SUCCESS;
        }

        // Future: support archiving here

        $query->delete(); // Assumes soft deletes
        $this->info("✔ Pruned {$count} suspicious login logs.");

        return self::SUCCESS;
    }
}