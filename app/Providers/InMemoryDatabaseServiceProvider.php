<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class InMemoryDatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Run migrations for in-memory database on every request
        if (config('database.default') === 'memory' || DB::connection('memory')) {
            Artisan::call('migrate', [
                '--database' => 'memory',
                '--path' => 'database/migrations/daily_info',
                '--force' => true,
            ]);
        }
    }
}
