<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class InMemoryDatabaseServiceProvider extends ServiceProvider
{
    private static bool $migrated = false;

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Run migrations for in-memory database only once per PHP process
        if (!self::$migrated) {
            try {
                Artisan::call('migrate', [
                    '--database' => 'memory',
                    '--path' => 'database/migrations/daily_info',
                    '--force' => true,
                ]);
                self::$migrated = true;
            } catch (\Exception $e) {
                Log::error('Failed to run in-memory database migrations', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
