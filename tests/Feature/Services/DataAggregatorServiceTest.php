<?php

use App\Models\News;
use App\Models\Weather;
use App\Models\Stock;
use App\Services\DataAggregatorService;
use App\Services\NewsApiService;
use App\Services\OpenWeatherMapService;
use App\Services\TiingoService;
use App\Services\KeywordMatcherService;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    // Run migrations for in-memory database
    Artisan::call('migrate', [
        '--database' => 'memory',
        '--path' => 'database/migrations/daily_info',
        '--force' => true,
    ]);
});

test('aggregates data from all services and stores in database', function () {
    // Arrange: Mock all services
    $newsService = Mockery::mock(NewsApiService::class);
    $newsService->shouldReceive('fetchNews')
        ->with('2025-12-03')
        ->once()
        ->andReturn([
            [
                'headline' => 'Apple launches new product in New York',
                'description' => 'Apple unveiled new tech in New York City',
                'url' => 'https://example.com/1',
                'source' => 'Tech News',
                'published_at' => '2025-12-03T10:00:00Z',
            ],
        ]);

    $weatherService = Mockery::mock(OpenWeatherMapService::class);
    $weatherService->shouldReceive('fetchMultipleWeather')
        ->once()
        ->andReturn([
            ['location' => 'New York', 'temperature' => 20.0, 'description' => 'Sunny'],
        ]);

    $stockService = Mockery::mock(TiingoService::class);
    $stockService->shouldReceive('fetchStocks')
        ->once()
        ->andReturn([
            ['company_name' => 'Apple Inc.', 'ticker_symbol' => 'AAPL', 'price' => 175.23],
        ]);

    $keywordMatcher = new KeywordMatcherService();

    $aggregator = new DataAggregatorService(
        $newsService,
        $weatherService,
        $stockService,
        $keywordMatcher
    );

    // Act
    $result = $aggregator->aggregateData('2025-12-03');

    // Assert
    expect($result)->toBeArray()
        ->and($result)->toHaveKeys(['news', 'weather', 'stocks'])
        ->and($result['news'])->toHaveCount(1)
        ->and($result['weather'])->toHaveCount(1)
        ->and($result['stocks'])->toHaveCount(1);

    // Verify data is stored in database
    expect(News::count())->toBe(1)
        ->and(Weather::count())->toBe(1)
        ->and(Stock::count())->toBe(1);
});

test('returns cached data if already exists for date', function () {
    // Arrange: Create existing data
    News::create([
        'headline' => 'Existing News',
        'url' => 'https://example.com',
        'source' => 'Source',
        'published_at' => now(),
        'fetched_for_date' => '2025-12-03',
    ]);

    $newsService = Mockery::mock(NewsApiService::class);
    $newsService->shouldNotReceive('fetchNews'); // Should not be called

    $weatherService = Mockery::mock(OpenWeatherMapService::class);
    $stockService = Mockery::mock(TiingoService::class);
    $keywordMatcher = new KeywordMatcherService();

    $aggregator = new DataAggregatorService(
        $newsService,
        $weatherService,
        $stockService,
        $keywordMatcher
    );

    // Act
    $result = $aggregator->aggregateData('2025-12-03');

    // Assert: Should return cached data
    expect($result['news'])->toHaveCount(1)
        ->and($result['news'][0]['headline'])->toBe('Existing News');
});
