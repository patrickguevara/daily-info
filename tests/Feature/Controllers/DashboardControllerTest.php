<?php

use App\Services\DataAggregatorService;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    Artisan::call('migrate', [
        '--database' => 'memory',
        '--path' => 'database/migrations/daily_info',
        '--force' => true,
    ]);
});

test('index redirects to today', function () {
    $response = $this->get('/');

    $response->assertRedirectContains('/dashboard/');
});

test('show renders dashboard with date', function () {
    // Mock the aggregator service
    $this->mock(DataAggregatorService::class, function ($mock) {
        $mock->shouldReceive('aggregateData')
            ->once()
            ->andReturn([
                'news' => [],
                'weather' => [],
                'stocks' => [],
            ]);
    });

    $today = now()->format('Y-m-d');
    $response = $this->get("/dashboard/{$today}");

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Dashboard'));
});

test('redirects when date is too old', function () {
    $oldDate = now()->subDays(10)->format('Y-m-d');

    $response = $this->get("/dashboard/{$oldDate}");

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error');
});

test('redirects when date is in future', function () {
    $futureDate = now()->addDays(1)->format('Y-m-d');

    $response = $this->get("/dashboard/{$futureDate}");

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('error');
});
