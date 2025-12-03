# Daily Info Dashboard Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Build a Laravel 12 + Vue 3 dashboard that integrates NewsAPI, OpenWeatherMap, and Tiingo to display daily news with contextual weather and stock data.

**Architecture:** Service-oriented backend with in-memory SQLite for session caching, keyword-based matching for locations/companies, and Vue components with Inertia for the frontend.

**Tech Stack:** Laravel 12, Vue 3, Inertia.js, TypeScript, Tailwind CSS, Pest for testing, in-memory SQLite

---

## Prerequisites

Before starting, ensure:
- PHP 8.2+ installed
- Composer installed
- Node.js and npm installed
- Laravel Herd running (for PHP process)

---

## Task 1: Environment Setup and Database Configuration

**Files:**
- Modify: `.env`
- Modify: `config/database.php`
- Create: `app/Providers/InMemoryDatabaseServiceProvider.php`

### Step 1: Add API keys to .env

Open `.env` and add these lines at the end:

```env
NEWS_API_KEY=64bbfa65330e4d838bf9a197505b2e01
OPENWEATHER_API_KEY=b478e44facd0c82ef304e04c80ca9e0f
TIINGO_API_KEY=27465c83310d7739ab0767eb939a179db9241985
```

### Step 2: Configure in-memory SQLite connection

Open `config/database.php` and add a new connection in the `connections` array (after the `sqlite` connection):

```php
'memory' => [
    'driver' => 'sqlite',
    'database' => ':memory:',
    'prefix' => '',
    'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
],
```

### Step 3: Create service provider for in-memory database

Create `app/Providers/InMemoryDatabaseServiceProvider.php`:

```php
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
```

### Step 4: Register the service provider

Open `bootstrap/providers.php` and add the new provider:

```php
<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,
    App\Providers\InMemoryDatabaseServiceProvider::class, // Add this line
];
```

### Step 5: Verify configuration

Run: `php artisan tinker`

In tinker, run:
```php
config('database.connections.memory');
```

Expected: Should output the memory connection configuration.

Exit tinker: `exit`

### Step 6: Commit environment setup

```bash
git add .env config/database.php app/Providers/InMemoryDatabaseServiceProvider.php bootstrap/providers.php
git commit -m "feat: configure in-memory SQLite and API keys"
```

---

## Task 2: Create Database Migrations

**Files:**
- Create: `database/migrations/daily_info/2025_12_03_000001_create_news_table.php`
- Create: `database/migrations/daily_info/2025_12_03_000002_create_weather_table.php`
- Create: `database/migrations/daily_info/2025_12_03_000003_create_stocks_table.php`
- Create: `database/migrations/daily_info/2025_12_03_000004_create_news_related_data_table.php`

### Step 1: Create migrations directory

Run: `mkdir -p database/migrations/daily_info`

Expected: Directory created without errors.

### Step 2: Create news table migration

Create `database/migrations/daily_info/2025_12_03_000001_create_news_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('memory')->create('news', function (Blueprint $table) {
            $table->id();
            $table->string('headline');
            $table->text('description')->nullable();
            $table->string('url');
            $table->string('source');
            $table->timestamp('published_at');
            $table->date('fetched_for_date');
            $table->timestamps();

            $table->index('fetched_for_date');
        });
    }

    public function down(): void
    {
        Schema::connection('memory')->dropIfExists('news');
    }
};
```

### Step 3: Create weather table migration

Create `database/migrations/daily_info/2025_12_03_000002_create_weather_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('memory')->create('weather', function (Blueprint $table) {
            $table->id();
            $table->string('location');
            $table->decimal('temperature', 5, 2);
            $table->string('description');
            $table->date('fetched_for_date');
            $table->timestamps();

            $table->index('fetched_for_date');
        });
    }

    public function down(): void
    {
        Schema::connection('memory')->dropIfExists('weather');
    }
};
```

### Step 4: Create stocks table migration

Create `database/migrations/daily_info/2025_12_03_000003_create_stocks_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('memory')->create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('ticker_symbol');
            $table->decimal('price', 10, 2);
            $table->date('fetched_for_date');
            $table->timestamps();

            $table->index('fetched_for_date');
        });
    }

    public function down(): void
    {
        Schema::connection('memory')->dropIfExists('stocks');
    }
};
```

### Step 5: Create news_related_data pivot table migration

Create `database/migrations/daily_info/2025_12_03_000004_create_news_related_data_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('memory')->create('news_related_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_id')->constrained('news')->onDelete('cascade');
            $table->foreignId('weather_id')->nullable()->constrained('weather')->onDelete('cascade');
            $table->foreignId('stock_id')->nullable()->constrained('stocks')->onDelete('cascade');
            $table->timestamps();

            $table->index('news_id');
        });
    }

    public function down(): void
    {
        Schema::connection('memory')->dropIfExists('news_related_data');
    }
};
```

### Step 6: Test migrations run successfully

Run: `php artisan migrate --database=memory --path=database/migrations/daily_info --force`

Expected: All 4 migrations should run successfully with "Migrating" and "DONE" messages.

### Step 7: Commit migrations

```bash
git add database/migrations/daily_info/
git commit -m "feat: add database migrations for news, weather, stocks, and relationships"
```

---

## Task 3: Create Eloquent Models

**Files:**
- Create: `app/Models/News.php`
- Create: `app/Models/Weather.php`
- Create: `app/Models/Stock.php`
- Create: `app/Models/NewsRelatedData.php`

### Step 1: Create News model

Create `app/Models/News.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class News extends Model
{
    use HasFactory;

    protected $connection = 'memory';
    protected $table = 'news';

    protected $fillable = [
        'headline',
        'description',
        'url',
        'source',
        'published_at',
        'fetched_for_date',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'fetched_for_date' => 'date',
    ];

    public function relatedData(): HasMany
    {
        return $this->hasMany(NewsRelatedData::class);
    }

    public function scopeForDate($query, string $date)
    {
        return $query->where('fetched_for_date', $date);
    }
}
```

### Step 2: Create Weather model

Create `app/Models/Weather.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Weather extends Model
{
    use HasFactory;

    protected $connection = 'memory';
    protected $table = 'weather';

    protected $fillable = [
        'location',
        'temperature',
        'description',
        'fetched_for_date',
    ];

    protected $casts = [
        'temperature' => 'decimal:2',
        'fetched_for_date' => 'date',
    ];

    public function scopeForDate($query, string $date)
    {
        return $query->where('fetched_for_date', $date);
    }
}
```

### Step 3: Create Stock model

Create `app/Models/Stock.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasFactory;

    protected $connection = 'memory';
    protected $table = 'stocks';

    protected $fillable = [
        'company_name',
        'ticker_symbol',
        'price',
        'fetched_for_date',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'fetched_for_date' => 'date',
    ];

    public function scopeForDate($query, string $date)
    {
        return $query->where('fetched_for_date', $date);
    }
}
```

### Step 4: Create NewsRelatedData pivot model

Create `app/Models/NewsRelatedData.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsRelatedData extends Model
{
    use HasFactory;

    protected $connection = 'memory';
    protected $table = 'news_related_data';

    protected $fillable = [
        'news_id',
        'weather_id',
        'stock_id',
    ];

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    public function weather(): BelongsTo
    {
        return $this->belongsTo(Weather::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}
```

### Step 5: Test models can be instantiated

Run: `php artisan tinker`

In tinker:
```php
new App\Models\News();
new App\Models\Weather();
new App\Models\Stock();
```

Expected: All models instantiate without errors.

Exit tinker: `exit`

### Step 6: Commit models

```bash
git add app/Models/News.php app/Models/Weather.php app/Models/Stock.php app/Models/NewsRelatedData.php
git commit -m "feat: add Eloquent models for News, Weather, Stock, and relationships"
```

---

## Task 4: Write Test for NewsApiService (TDD)

**Files:**
- Create: `tests/Feature/Services/NewsApiServiceTest.php`

### Step 1: Create test file

Create `tests/Feature/Services/NewsApiServiceTest.php`:

```php
<?php

use App\Services\NewsApiService;
use Illuminate\Support\Facades\Http;

test('fetches top 10 news articles for a given date', function () {
    // Arrange: Mock the HTTP response
    Http::fake([
        'newsapi.org/*' => Http::response([
            'status' => 'ok',
            'totalResults' => 10,
            'articles' => [
                [
                    'title' => 'Test Headline 1',
                    'description' => 'Test description 1',
                    'url' => 'https://example.com/1',
                    'source' => ['name' => 'Test Source 1'],
                    'publishedAt' => '2025-12-03T10:00:00Z',
                ],
                [
                    'title' => 'Test Headline 2',
                    'description' => 'Test description 2',
                    'url' => 'https://example.com/2',
                    'source' => ['name' => 'Test Source 2'],
                    'publishedAt' => '2025-12-03T11:00:00Z',
                ],
            ],
        ], 200),
    ]);

    $service = new NewsApiService();

    // Act: Fetch news for a date
    $news = $service->fetchNews('2025-12-03');

    // Assert: Check results
    expect($news)->toBeArray()
        ->and($news)->toHaveCount(2)
        ->and($news[0])->toHaveKeys(['headline', 'description', 'url', 'source', 'published_at'])
        ->and($news[0]['headline'])->toBe('Test Headline 1');
});

test('returns empty array when API fails', function () {
    // Arrange: Mock failed response
    Http::fake([
        'newsapi.org/*' => Http::response([], 500),
    ]);

    $service = new NewsApiService();

    // Act
    $news = $service->fetchNews('2025-12-03');

    // Assert
    expect($news)->toBeArray()
        ->and($news)->toBeEmpty();
});
```

### Step 2: Run test to verify it fails

Run: `php artisan test --filter=NewsApiServiceTest`

Expected: Test fails with "Class 'App\Services\NewsApiService' not found"

### Step 3: Commit the test

```bash
git add tests/Feature/Services/NewsApiServiceTest.php
git commit -m "test: add failing tests for NewsApiService"
```

---

## Task 5: Implement NewsApiService

**Files:**
- Create: `app/Services/NewsApiService.php`

### Step 1: Create NewsApiService

Create `app/Services/NewsApiService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NewsApiService
{
    private string $apiKey;
    private string $baseUrl = 'https://newsapi.org/v2';

    public function __construct()
    {
        $this->apiKey = config('services.newsapi.key') ?? env('NEWS_API_KEY');
    }

    public function fetchNews(string $date): array
    {
        try {
            $response = Http::timeout(10)
                ->get("{$this->baseUrl}/everything", [
                    'apiKey' => $this->apiKey,
                    'from' => $date,
                    'to' => $date,
                    'sortBy' => 'publishedAt',
                    'pageSize' => 10,
                    'language' => 'en',
                ]);

            if ($response->failed()) {
                Log::error('NewsAPI request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $data = $response->json();

            if (!isset($data['articles'])) {
                return [];
            }

            return collect($data['articles'])->map(function ($article) {
                return [
                    'headline' => $article['title'] ?? '',
                    'description' => $article['description'] ?? null,
                    'url' => $article['url'] ?? '',
                    'source' => $article['source']['name'] ?? 'Unknown',
                    'published_at' => $article['publishedAt'] ?? now()->toIso8601String(),
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error('NewsAPI exception', [
                'message' => $e->getMessage(),
                'date' => $date,
            ]);
            return [];
        }
    }
}
```

### Step 2: Add NewsAPI configuration

Open `config/services.php` and add this to the array:

```php
'newsapi' => [
    'key' => env('NEWS_API_KEY'),
],
```

### Step 3: Run tests to verify they pass

Run: `php artisan test --filter=NewsApiServiceTest`

Expected: Both tests pass with green checkmarks.

### Step 4: Commit the implementation

```bash
git add app/Services/NewsApiService.php config/services.php
git commit -m "feat: implement NewsApiService with error handling"
```

---

## Task 6: Write Test for OpenWeatherMapService (TDD)

**Files:**
- Create: `tests/Feature/Services/OpenWeatherMapServiceTest.php`

### Step 1: Create test file

Create `tests/Feature/Services/OpenWeatherMapServiceTest.php`:

```php
<?php

use App\Services\OpenWeatherMapService;
use Illuminate\Support\Facades\Http;

test('fetches weather data for a city', function () {
    // Arrange
    Http::fake([
        'api.openweathermap.org/*' => Http::response([
            'name' => 'New York',
            'main' => [
                'temp' => 22.5,
            ],
            'weather' => [
                ['description' => 'clear sky'],
            ],
        ], 200),
    ]);

    $service = new OpenWeatherMapService();

    // Act
    $weather = $service->fetchWeather('New York');

    // Assert
    expect($weather)->toBeArray()
        ->and($weather)->toHaveKeys(['location', 'temperature', 'description'])
        ->and($weather['location'])->toBe('New York')
        ->and($weather['temperature'])->toBe(22.5)
        ->and($weather['description'])->toBe('clear sky');
});

test('returns null when weather API fails', function () {
    // Arrange
    Http::fake([
        'api.openweathermap.org/*' => Http::response([], 500),
    ]);

    $service = new OpenWeatherMapService();

    // Act
    $weather = $service->fetchWeather('Invalid City');

    // Assert
    expect($weather)->toBeNull();
});

test('caches weather requests within same instance', function () {
    // Arrange
    Http::fake([
        'api.openweathermap.org/*' => Http::response([
            'name' => 'London',
            'main' => ['temp' => 15.0],
            'weather' => [['description' => 'cloudy']],
        ], 200),
    ]);

    $service = new OpenWeatherMapService();

    // Act
    $weather1 = $service->fetchWeather('London');
    $weather2 = $service->fetchWeather('London');

    // Assert: Only 1 HTTP request should be made
    Http::assertSentCount(1);
    expect($weather1)->toBe($weather2);
});
```

### Step 2: Run test to verify it fails

Run: `php artisan test --filter=OpenWeatherMapServiceTest`

Expected: Test fails with "Class 'App\Services\OpenWeatherMapService' not found"

### Step 3: Commit the test

```bash
git add tests/Feature/Services/OpenWeatherMapServiceTest.php
git commit -m "test: add failing tests for OpenWeatherMapService"
```

---

## Task 7: Implement OpenWeatherMapService

**Files:**
- Create: `app/Services/OpenWeatherMapService.php`

### Step 1: Create OpenWeatherMapService

Create `app/Services/OpenWeatherMapService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenWeatherMapService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.openweathermap.org/data/2.5';
    private array $cache = [];

    public function __construct()
    {
        $this->apiKey = config('services.openweather.key') ?? env('OPENWEATHER_API_KEY');
    }

    public function fetchWeather(string $city): ?array
    {
        // Check cache first
        if (isset($this->cache[$city])) {
            return $this->cache[$city];
        }

        try {
            $response = Http::timeout(10)
                ->get("{$this->baseUrl}/weather", [
                    'q' => $city,
                    'appid' => $this->apiKey,
                    'units' => 'metric', // Celsius
                ]);

            if ($response->failed()) {
                Log::warning('OpenWeatherMap request failed', [
                    'city' => $city,
                    'status' => $response->status(),
                ]);
                return null;
            }

            $data = $response->json();

            $result = [
                'location' => $data['name'] ?? $city,
                'temperature' => $data['main']['temp'] ?? 0,
                'description' => $data['weather'][0]['description'] ?? 'Unknown',
            ];

            // Cache the result
            $this->cache[$city] = $result;

            return $result;

        } catch (\Exception $e) {
            Log::error('OpenWeatherMap exception', [
                'message' => $e->getMessage(),
                'city' => $city,
            ]);
            return null;
        }
    }

    public function fetchMultipleWeather(array $cities): array
    {
        $results = [];

        foreach ($cities as $city) {
            $weather = $this->fetchWeather($city);
            if ($weather !== null) {
                $results[] = $weather;
            }
        }

        return $results;
    }
}
```

### Step 2: Add OpenWeatherMap configuration

Open `config/services.php` and add to the array:

```php
'openweather' => [
    'key' => env('OPENWEATHER_API_KEY'),
],
```

### Step 3: Run tests to verify they pass

Run: `php artisan test --filter=OpenWeatherMapServiceTest`

Expected: All 3 tests pass.

### Step 4: Commit the implementation

```bash
git add app/Services/OpenWeatherMapService.php config/services.php
git commit -m "feat: implement OpenWeatherMapService with caching"
```

---

## Task 8: Write Test for TiingoService (TDD)

**Files:**
- Create: `tests/Feature/Services/TiingoServiceTest.php`

### Step 1: Create test file

Create `tests/Feature/Services/TiingoServiceTest.php`:

```php
<?php

use App\Services\TiingoService;
use Illuminate\Support\Facades\Http;

test('fetches stock price for a ticker', function () {
    // Arrange
    Http::fake([
        'api.tiingo.com/*' => Http::response([
            [
                'ticker' => 'AAPL',
                'name' => 'Apple Inc.',
                'last' => 175.23,
            ],
        ], 200),
    ]);

    $service = new TiingoService();

    // Act
    $stocks = $service->fetchStocks(['AAPL']);

    // Assert
    expect($stocks)->toBeArray()
        ->and($stocks)->toHaveCount(1)
        ->and($stocks[0])->toHaveKeys(['company_name', 'ticker_symbol', 'price'])
        ->and($stocks[0]['ticker_symbol'])->toBe('AAPL')
        ->and($stocks[0]['price'])->toBe(175.23);
});

test('fetches multiple stock prices', function () {
    // Arrange
    Http::fake([
        'api.tiingo.com/tiingo/daily/AAPL/prices*' => Http::response([
            [
                'ticker' => 'AAPL',
                'close' => 175.23,
            ],
        ], 200),
        'api.tiingo.com/tiingo/daily/MSFT/prices*' => Http::response([
            [
                'ticker' => 'MSFT',
                'close' => 380.45,
            ],
        ], 200),
    ]);

    $service = new TiingoService();

    // Act
    $stocks = $service->fetchStocks(['AAPL', 'MSFT']);

    // Assert
    expect($stocks)->toHaveCount(2);
});

test('returns empty array when API fails', function () {
    // Arrange
    Http::fake([
        'api.tiingo.com/*' => Http::response([], 500),
    ]);

    $service = new TiingoService();

    // Act
    $stocks = $service->fetchStocks(['AAPL']);

    // Assert
    expect($stocks)->toBeArray()
        ->and($stocks)->toBeEmpty();
});
```

### Step 2: Run test to verify it fails

Run: `php artisan test --filter=TiingoServiceTest`

Expected: Test fails with "Class 'App\Services\TiingoService' not found"

### Step 3: Commit the test

```bash
git add tests/Feature/Services/TiingoServiceTest.php
git commit -m "test: add failing tests for TiingoService"
```

---

## Task 9: Implement TiingoService

**Files:**
- Create: `app/Services/TiingoService.php`

### Step 1: Create TiingoService

Create `app/Services/TiingoService.php`:

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TiingoService
{
    private string $apiKey;
    private string $baseUrl = 'https://api.tiingo.com';

    public function __construct()
    {
        $this->apiKey = config('services.tiingo.key') ?? env('TIINGO_API_KEY');
    }

    public function fetchStocks(array $tickers): array
    {
        if (empty($tickers)) {
            return [];
        }

        $results = [];

        foreach ($tickers as $ticker) {
            try {
                $response = Http::timeout(10)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                    ])
                    ->get("{$this->baseUrl}/tiingo/daily/{$ticker}/prices", [
                        'token' => $this->apiKey,
                    ]);

                if ($response->failed()) {
                    Log::warning('Tiingo request failed', [
                        'ticker' => $ticker,
                        'status' => $response->status(),
                    ]);
                    continue;
                }

                $data = $response->json();

                if (empty($data)) {
                    continue;
                }

                $latestData = $data[0];

                $results[] = [
                    'company_name' => $this->getCompanyName($ticker),
                    'ticker_symbol' => $ticker,
                    'price' => $latestData['close'] ?? $latestData['last'] ?? 0,
                ];

            } catch (\Exception $e) {
                Log::error('Tiingo exception', [
                    'message' => $e->getMessage(),
                    'ticker' => $ticker,
                ]);
                continue;
            }
        }

        return $results;
    }

    private function getCompanyName(string $ticker): string
    {
        // Simple mapping for common tickers
        $names = [
            'AAPL' => 'Apple Inc.',
            'MSFT' => 'Microsoft Corporation',
            'GOOGL' => 'Alphabet Inc.',
            'AMZN' => 'Amazon.com Inc.',
            'TSLA' => 'Tesla Inc.',
            'META' => 'Meta Platforms Inc.',
            'NVDA' => 'NVIDIA Corporation',
            'SPY' => 'S&P 500 ETF',
        ];

        return $names[$ticker] ?? $ticker;
    }
}
```

### Step 2: Add Tiingo configuration

Open `config/services.php` and add:

```php
'tiingo' => [
    'key' => env('TIINGO_API_KEY'),
],
```

### Step 3: Run tests to verify they pass

Run: `php artisan test --filter=TiingoServiceTest`

Expected: All 3 tests pass.

### Step 4: Commit the implementation

```bash
git add app/Services/TiingoService.php config/services.php
git commit -m "feat: implement TiingoService for stock data"
```

---

## Task 10: Write Test for KeywordMatcherService (TDD)

**Files:**
- Create: `tests/Feature/Services/KeywordMatcherServiceTest.php`

### Step 1: Create test file

Create `tests/Feature/Services/KeywordMatcherServiceTest.php`:

```php
<?php

use App\Services\KeywordMatcherService;

test('extracts locations from news articles', function () {
    $service = new KeywordMatcherService();

    $articles = [
        ['headline' => 'Breaking news in New York today', 'description' => 'Something happened in New York.'],
        ['headline' => 'London markets surge', 'description' => 'Stock markets in London are up.'],
        ['headline' => 'New York weather alert', 'description' => 'Heavy rain expected in New York.'],
    ];

    $locations = $service->extractLocations($articles);

    expect($locations)->toBeArray()
        ->and($locations)->toContain('New York')
        ->and($locations)->toContain('London')
        ->and(count($locations))->toBeLessThanOrEqual(3);
});

test('extracts companies from news articles', function () {
    $service = new KeywordMatcherService();

    $articles = [
        ['headline' => 'Apple announces new product', 'description' => 'Apple unveiled today...'],
        ['headline' => 'Microsoft and Google partnership', 'description' => 'Microsoft teams up with Google.'],
        ['headline' => 'Apple stock rises', 'description' => 'Apple shares increased.'],
    ];

    $companies = $service->extractCompanies($articles);

    expect($companies)->toBeArray()
        ->and($companies)->toHaveKey('AAPL')
        ->and($companies)->toHaveKey('MSFT')
        ->and($companies)->toHaveKey('GOOGL')
        ->and(count($companies))->toBeLessThanOrEqual(5);
});

test('returns default location when none found', function () {
    $service = new KeywordMatcherService();

    $articles = [
        ['headline' => 'Generic news headline', 'description' => 'Generic description'],
    ];

    $locations = $service->extractLocations($articles);

    expect($locations)->toContain('New York');
});

test('returns default ticker when no companies found', function () {
    $service = new KeywordMatcherService();

    $articles = [
        ['headline' => 'Generic news headline', 'description' => 'Generic description'],
    ];

    $companies = $service->extractCompanies($articles);

    expect($companies)->toHaveKey('SPY');
});
```

### Step 2: Run test to verify it fails

Run: `php artisan test --filter=KeywordMatcherServiceTest`

Expected: Test fails with "Class 'App\Services\KeywordMatcherService' not found"

### Step 3: Commit the test

```bash
git add tests/Feature/Services/KeywordMatcherServiceTest.php
git commit -m "test: add failing tests for KeywordMatcherService"
```

---

## Task 11: Implement KeywordMatcherService

**Files:**
- Create: `app/Services/KeywordMatcherService.php`

### Step 1: Create KeywordMatcherService

Create `app/Services/KeywordMatcherService.php`:

```php
<?php

namespace App\Services;

class KeywordMatcherService
{
    private array $majorCities = [
        'New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix',
        'Philadelphia', 'San Antonio', 'San Diego', 'Dallas', 'San Jose',
        'Austin', 'Jacksonville', 'Fort Worth', 'Columbus', 'Charlotte',
        'San Francisco', 'Indianapolis', 'Seattle', 'Denver', 'Washington',
        'Boston', 'El Paso', 'Nashville', 'Detroit', 'Oklahoma City',
        'Portland', 'Las Vegas', 'Memphis', 'Louisville', 'Baltimore',
        'Milwaukee', 'Albuquerque', 'Tucson', 'Fresno', 'Sacramento',
        'Kansas City', 'Mesa', 'Atlanta', 'Omaha', 'Colorado Springs',
        'Raleigh', 'Miami', 'Long Beach', 'Virginia Beach', 'Oakland',
        'Minneapolis', 'Tulsa', 'Tampa', 'Arlington', 'New Orleans',
        // International cities
        'London', 'Paris', 'Tokyo', 'Berlin', 'Sydney', 'Toronto',
        'Mumbai', 'Shanghai', 'Beijing', 'Moscow', 'Dubai', 'Singapore',
        'Hong Kong', 'Seoul', 'Madrid', 'Rome', 'Amsterdam', 'Brussels',
        'Vienna', 'Dublin', 'Zurich', 'Copenhagen', 'Stockholm', 'Oslo',
    ];

    private array $companies = [
        'Apple' => 'AAPL', 'Microsoft' => 'MSFT', 'Google' => 'GOOGL',
        'Alphabet' => 'GOOGL', 'Amazon' => 'AMZN', 'Tesla' => 'TSLA',
        'Meta' => 'META', 'Facebook' => 'META', 'NVIDIA' => 'NVDA',
        'Berkshire Hathaway' => 'BRK.B', 'JPMorgan' => 'JPM',
        'Johnson & Johnson' => 'JNJ', 'Visa' => 'V', 'Walmart' => 'WMT',
        'Procter & Gamble' => 'PG', 'UnitedHealth' => 'UNH',
        'Mastercard' => 'MA', 'Home Depot' => 'HD', 'Chevron' => 'CVX',
        'Pfizer' => 'PFE', 'AbbVie' => 'ABBV', 'Coca-Cola' => 'KO',
        'PepsiCo' => 'PEP', 'Costco' => 'COST', 'Netflix' => 'NFLX',
        'Adobe' => 'ADBE', 'Cisco' => 'CSCO', 'Intel' => 'INTC',
        'Comcast' => 'CMCSA', 'Verizon' => 'VZ', 'AT&T' => 'T',
        'Disney' => 'DIS', 'McDonald\'s' => 'MCD', 'Nike' => 'NKE',
        'Boeing' => 'BA', 'IBM' => 'IBM', 'Salesforce' => 'CRM',
        'Oracle' => 'ORCL', 'PayPal' => 'PYPL', 'Broadcom' => 'AVGO',
        'Texas Instruments' => 'TXN', 'Qualcomm' => 'QCOM',
        'AMD' => 'AMD', 'Starbucks' => 'SBUX', 'Goldman Sachs' => 'GS',
        'Morgan Stanley' => 'MS', 'Bank of America' => 'BAC',
        'Wells Fargo' => 'WFC', 'Citigroup' => 'C', 'American Express' => 'AXP',
    ];

    public function extractLocations(array $newsArticles): array
    {
        $locationCounts = [];

        foreach ($newsArticles as $article) {
            $text = strtolower(
                ($article['headline'] ?? '') . ' ' . ($article['description'] ?? '')
            );

            foreach ($this->majorCities as $city) {
                if (stripos($text, strtolower($city)) !== false) {
                    $locationCounts[$city] = ($locationCounts[$city] ?? 0) + 1;
                }
            }
        }

        // Sort by count descending
        arsort($locationCounts);

        // Get top 3
        $topLocations = array_slice(array_keys($locationCounts), 0, 3);

        // If no locations found, return default
        if (empty($topLocations)) {
            return ['New York'];
        }

        return $topLocations;
    }

    public function extractCompanies(array $newsArticles): array
    {
        $companyCounts = [];

        foreach ($newsArticles as $article) {
            $text = ($article['headline'] ?? '') . ' ' . ($article['description'] ?? '');

            foreach ($this->companies as $company => $ticker) {
                // Case-insensitive search for company name
                if (stripos($text, $company) !== false) {
                    $companyCounts[$ticker] = ($companyCounts[$ticker] ?? 0) + 1;
                }
            }
        }

        // Sort by count descending
        arsort($companyCounts);

        // Get top 5
        $topCompanies = array_slice($companyCounts, 0, 5, true);

        // If no companies found, return S&P 500
        if (empty($topCompanies)) {
            return ['SPY' => 1];
        }

        return $topCompanies;
    }
}
```

### Step 2: Run tests to verify they pass

Run: `php artisan test --filter=KeywordMatcherServiceTest`

Expected: All 4 tests pass.

### Step 3: Commit the implementation

```bash
git add app/Services/KeywordMatcherService.php
git commit -m "feat: implement KeywordMatcherService with location and company extraction"
```

---

## Task 12: Write Test for DataAggregatorService (TDD)

**Files:**
- Create: `tests/Feature/Services/DataAggregatorServiceTest.php`

### Step 1: Create test file

Create `tests/Feature/Services/DataAggregatorServiceTest.php`:

```php
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
```

### Step 2: Run test to verify it fails

Run: `php artisan test --filter=DataAggregatorServiceTest`

Expected: Test fails with "Class 'App\Services\DataAggregatorService' not found"

### Step 3: Commit the test

```bash
git add tests/Feature/Services/DataAggregatorServiceTest.php
git commit -m "test: add failing tests for DataAggregatorService"
```

---

## Task 13: Implement DataAggregatorService

**Files:**
- Create: `app/Services/DataAggregatorService.php`

### Step 1: Create DataAggregatorService

Create `app/Services/DataAggregatorService.php`:

```php
<?php

namespace App\Services;

use App\Models\News;
use App\Models\Weather;
use App\Models\Stock;
use App\Models\NewsRelatedData;
use Illuminate\Support\Facades\Log;

class DataAggregatorService
{
    public function __construct(
        private NewsApiService $newsService,
        private OpenWeatherMapService $weatherService,
        private TiingoService $stockService,
        private KeywordMatcherService $keywordMatcher,
    ) {}

    public function aggregateData(string $date): array
    {
        // Check if we already have data for this date
        $existingNews = News::forDate($date)->get();

        if ($existingNews->isNotEmpty()) {
            return $this->getCachedData($date);
        }

        // Fetch news
        $newsArticles = $this->newsService->fetchNews($date);

        if (empty($newsArticles)) {
            Log::warning('No news articles fetched', ['date' => $date]);
            return [
                'news' => [],
                'weather' => [],
                'stocks' => [],
            ];
        }

        // Extract keywords
        $locations = $this->keywordMatcher->extractLocations($newsArticles);
        $companyTickers = array_keys($this->keywordMatcher->extractCompanies($newsArticles));

        // Fetch weather and stocks in parallel (simulate with collection)
        $weatherData = $this->weatherService->fetchMultipleWeather($locations);
        $stockData = $this->stockService->fetchStocks($companyTickers);

        // Store everything in database
        $this->storeData($date, $newsArticles, $weatherData, $stockData);

        return [
            'news' => News::forDate($date)->limit(5)->get()->toArray(),
            'weather' => Weather::forDate($date)->get()->toArray(),
            'stocks' => Stock::forDate($date)->get()->toArray(),
        ];
    }

    private function getCachedData(string $date): array
    {
        return [
            'news' => News::forDate($date)->limit(5)->get()->toArray(),
            'weather' => Weather::forDate($date)->get()->toArray(),
            'stocks' => Stock::forDate($date)->get()->toArray(),
        ];
    }

    private function storeData(string $date, array $newsArticles, array $weatherData, array $stockData): void
    {
        // Store news
        $newsRecords = [];
        foreach ($newsArticles as $article) {
            $newsRecords[] = News::create([
                'headline' => $article['headline'],
                'description' => $article['description'],
                'url' => $article['url'],
                'source' => $article['source'],
                'published_at' => $article['published_at'],
                'fetched_for_date' => $date,
            ]);
        }

        // Store weather
        $weatherRecords = [];
        foreach ($weatherData as $weather) {
            $weatherRecords[] = Weather::create([
                'location' => $weather['location'],
                'temperature' => $weather['temperature'],
                'description' => $weather['description'],
                'fetched_for_date' => $date,
            ]);
        }

        // Store stocks
        $stockRecords = [];
        foreach ($stockData as $stock) {
            $stockRecords[] = Stock::create([
                'company_name' => $stock['company_name'],
                'ticker_symbol' => $stock['ticker_symbol'],
                'price' => $stock['price'],
                'fetched_for_date' => $date,
            ]);
        }

        // Create relationships (simplified: just link first news with all data)
        if (!empty($newsRecords) && (!empty($weatherRecords) || !empty($stockRecords))) {
            foreach ($weatherRecords as $weather) {
                NewsRelatedData::create([
                    'news_id' => $newsRecords[0]->id,
                    'weather_id' => $weather->id,
                ]);
            }

            foreach ($stockRecords as $stock) {
                NewsRelatedData::create([
                    'news_id' => $newsRecords[0]->id,
                    'stock_id' => $stock->id,
                ]);
            }
        }
    }
}
```

### Step 2: Run tests to verify they pass

Run: `php artisan test --filter=DataAggregatorServiceTest`

Expected: Both tests pass.

### Step 3: Commit the implementation

```bash
git add app/Services/DataAggregatorService.php
git commit -m "feat: implement DataAggregatorService with caching logic"
```

---

## Task 14: Create DashboardController with Tests

**Files:**
- Create: `tests/Feature/Controllers/DashboardControllerTest.php`
- Create: `app/Http/Controllers/DashboardController.php`

### Step 1: Write test for DashboardController

Create `tests/Feature/Controllers/DashboardControllerTest.php`:

```php
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
```

### Step 2: Run test to verify it fails

Run: `php artisan test --filter=DashboardControllerTest`

Expected: Tests fail (routes don't exist yet).

### Step 3: Create DashboardController

Create `app/Http/Controllers/DashboardController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Services\DataAggregatorService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private DataAggregatorService $aggregator
    ) {}

    public function index()
    {
        return redirect()->route('dashboard.date', ['date' => now()->format('Y-m-d')]);
    }

    public function show(string $date): Response|\Illuminate\Http\RedirectResponse
    {
        // Validate date
        try {
            $requestedDate = Carbon::parse($date);
        } catch (\Exception $e) {
            return redirect()->route('dashboard')
                ->with('error', 'Invalid date format.');
        }

        $oldestAllowed = now()->subDays(6)->startOfDay();

        if ($requestedDate->lt($oldestAllowed) || $requestedDate->gt(now()->endOfDay())) {
            return redirect()->route('dashboard')
                ->with('error', 'We can only show data from the past week. Showing today\'s data instead.');
        }

        // Aggregate data
        $data = $this->aggregator->aggregateData($date);

        return Inertia::render('Dashboard', [
            'date' => $requestedDate->format('M d, Y'),
            'dateParam' => $date,
            'news' => $data['news'],
            'weather' => $data['weather'],
            'stocks' => $data['stocks'],
            'availableDates' => $this->getAvailableDates(),
            'lastUpdated' => now()->toIso8601String(),
        ]);
    }

    private function getAvailableDates(): array
    {
        $dates = [];

        for ($i = 0; $i < 7; $i++) {
            $date = now()->subDays($i);
            $dates[] = [
                'label' => $date->format('D M d, Y'),
                'value' => $date->format('Y-m-d'),
            ];
        }

        return $dates;
    }
}
```

### Step 4: Update routes

Open `routes/web.php` and replace the existing home route with:

```php
<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/{date}', [DashboardController::class, 'show'])->name('dashboard.date');

// Keep existing routes
Route::get('old-dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('old-dashboard');

require __DIR__.'/settings.php';
```

### Step 5: Run tests to verify they pass

Run: `php artisan test --filter=DashboardControllerTest`

Expected: All tests pass.

### Step 6: Commit controller and routes

```bash
git add app/Http/Controllers/DashboardController.php routes/web.php tests/Feature/Controllers/DashboardControllerTest.php
git commit -m "feat: add DashboardController with date validation"
```

---

## Task 15: Create TypeScript Types

**Files:**
- Create: `resources/js/types/dashboard.d.ts`

### Step 1: Create TypeScript type definitions

Create `resources/js/types/dashboard.d.ts`:

```typescript
export interface DashboardData {
    date: string; // "Dec 03, 2025"
    dateParam: string; // "2025-12-03"
    news: NewsArticle[];
    weather: WeatherData[];
    stocks: StockData[];
    availableDates: DateOption[];
    lastUpdated: string;
}

export interface NewsArticle {
    id: number;
    headline: string;
    description?: string;
    url: string;
    source: string;
    published_at: string;
}

export interface WeatherData {
    id: number;
    location: string;
    temperature: number;
    description: string;
}

export interface StockData {
    id: number;
    company_name: string;
    ticker_symbol: string;
    price: number;
}

export interface DateOption {
    label: string; // "Mon Dec 03, 2025"
    value: string; // "2025-12-03"
}
```

### Step 2: Update main types file

Open `resources/js/types/index.d.ts` and add this export at the end:

```typescript
export * from './dashboard';
```

### Step 3: Verify TypeScript compiles

Run: `npm run build`

Expected: No TypeScript errors.

### Step 4: Commit TypeScript types

```bash
git add resources/js/types/dashboard.d.ts resources/js/types/index.d.ts
git commit -m "feat: add TypeScript type definitions for dashboard"
```

---

## Task 16: Create DatePicker Component

**Files:**
- Create: `resources/js/components/DatePicker.vue`

### Step 1: Create DatePicker component

Create `resources/js/components/DatePicker.vue`:

```vue
<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import type { DateOption } from '@/types/dashboard'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

const props = defineProps<{
  currentDate: string
  availableDates: DateOption[]
}>()

const handleDateChange = (value: string) => {
  router.get(`/dashboard/${value}`, {}, {
    preserveScroll: true,
  })
}
</script>

<template>
  <Select :model-value="currentDate" @update:model-value="handleDateChange">
    <SelectTrigger class="w-[240px]">
      <SelectValue placeholder="Select a date" />
    </SelectTrigger>
    <SelectContent>
      <SelectItem
        v-for="date in availableDates"
        :key="date.value"
        :value="date.value"
      >
        {{ date.label }}
      </SelectItem>
    </SelectContent>
  </Select>
</template>
```

### Step 2: Verify component exports exist

Check that Select components exist:

Run: `ls resources/js/components/ui/select/`

Expected: Select components should be listed.

### Step 3: Commit DatePicker component

```bash
git add resources/js/components/DatePicker.vue
git commit -m "feat: add DatePicker component"
```

---

## Task 17: Create LoadingSpinner Component

**Files:**
- Create: `resources/js/components/LoadingSpinner.vue`

### Step 1: Create LoadingSpinner component

Create `resources/js/components/LoadingSpinner.vue`:

```vue
<script setup lang="ts">
import { Spinner } from '@/components/ui/spinner'

defineProps<{
  message?: string
}>()
</script>

<template>
  <div class="fixed inset-0 bg-background/80 backdrop-blur-sm flex items-center justify-center z-50">
    <div class="flex flex-col items-center gap-4">
      <Spinner class="h-12 w-12" />
      <p v-if="message" class="text-muted-foreground">
        {{ message }}
      </p>
    </div>
  </div>
</template>
```

### Step 2: Commit LoadingSpinner component

```bash
git add resources/js/components/LoadingSpinner.vue
git commit -m "feat: add LoadingSpinner component"
```

---

## Task 18: Create NewsSection Component

**Files:**
- Create: `resources/js/components/NewsSection.vue`

### Step 1: Create NewsSection component

Create `resources/js/components/NewsSection.vue`:

```vue
<script setup lang="ts">
import type { NewsArticle } from '@/types/dashboard'
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'

const props = defineProps<{
  news: NewsArticle[]
  loading?: boolean
}>()
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center gap-2">
      <span class="text-2xl">📰</span>
      <h2 class="text-2xl font-bold">Top News Headlines</h2>
    </div>

    <div v-if="loading" class="space-y-4">
      <Card v-for="i in 5" :key="i">
        <CardHeader>
          <Skeleton class="h-6 w-3/4" />
          <Skeleton class="h-4 w-1/2" />
        </CardHeader>
      </Card>
    </div>

    <div v-else-if="news.length === 0" class="text-center py-12">
      <p class="text-muted-foreground">No news available for this date</p>
    </div>

    <div v-else class="space-y-4">
      <Card v-for="article in news" :key="article.id">
        <CardHeader>
          <CardTitle class="text-lg">
            <a
              :href="article.url"
              target="_blank"
              rel="noopener noreferrer"
              class="hover:text-primary transition-colors"
            >
              {{ article.headline }}
            </a>
          </CardTitle>
          <CardDescription>
            {{ article.source }} • {{ new Date(article.published_at).toLocaleDateString() }}
          </CardDescription>
        </CardHeader>
        <CardContent v-if="article.description">
          <p class="text-sm text-muted-foreground">
            {{ article.description }}
          </p>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
```

### Step 2: Commit NewsSection component

```bash
git add resources/js/components/NewsSection.vue
git commit -m "feat: add NewsSection component"
```

---

## Task 19: Create WeatherSection Component

**Files:**
- Create: `resources/js/components/WeatherSection.vue`

### Step 1: Create WeatherSection component

Create `resources/js/components/WeatherSection.vue`:

```vue
<script setup lang="ts">
import type { WeatherData } from '@/types/dashboard'
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'

const props = defineProps<{
  weather: WeatherData[]
  loading?: boolean
}>()

const formatTemperature = (temp: number) => `${Math.round(temp)}°C`
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center gap-2">
      <span class="text-2xl">🌤</span>
      <h2 class="text-2xl font-bold">Weather</h2>
    </div>

    <div v-if="loading" class="space-y-4">
      <Card v-for="i in 3" :key="i">
        <CardHeader>
          <Skeleton class="h-6 w-32" />
        </CardHeader>
        <CardContent>
          <Skeleton class="h-4 w-24" />
        </CardContent>
      </Card>
    </div>

    <div v-else-if="weather.length === 0" class="text-center py-8">
      <p class="text-muted-foreground">No weather data available</p>
    </div>

    <div v-else class="space-y-4">
      <Card v-for="item in weather" :key="item.id">
        <CardHeader>
          <CardTitle class="text-lg">{{ item.location }}</CardTitle>
        </CardHeader>
        <CardContent>
          <div class="flex items-center gap-2">
            <span class="text-2xl font-bold">{{ formatTemperature(item.temperature) }}</span>
            <span class="text-muted-foreground capitalize">{{ item.description }}</span>
          </div>
        </CardContent>
      </Card>
    </div>
  </div>
</template>
```

### Step 2: Commit WeatherSection component

```bash
git add resources/js/components/WeatherSection.vue
git commit -m "feat: add WeatherSection component"
```

---

## Task 20: Create StockSection Component

**Files:**
- Create: `resources/js/components/StockSection.vue`

### Step 1: Create StockSection component

Create `resources/js/components/StockSection.vue`:

```vue
<script setup lang="ts">
import type { StockData } from '@/types/dashboard'
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'

const props = defineProps<{
  stocks: StockData[]
  loading?: boolean
}>()

const formatPrice = (price: number) => `$${price.toFixed(2)}`
</script>

<template>
  <div class="space-y-4">
    <div class="flex items-center gap-2">
      <span class="text-2xl">📈</span>
      <h2 class="text-2xl font-bold">Stocks</h2>
    </div>

    <div v-if="loading">
      <Card>
        <CardContent class="pt-6">
          <div class="space-y-4">
            <div v-for="i in 5" :key="i" class="flex justify-between items-center">
              <Skeleton class="h-4 w-16" />
              <Skeleton class="h-4 w-24" />
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <div v-else-if="stocks.length === 0" class="text-center py-8">
      <p class="text-muted-foreground">No stock data available</p>
    </div>

    <Card v-else>
      <CardHeader>
        <CardTitle class="text-lg">Market Data</CardTitle>
      </CardHeader>
      <CardContent>
        <div class="space-y-3">
          <div
            v-for="stock in stocks"
            :key="stock.id"
            class="flex justify-between items-center py-2 border-b last:border-0"
          >
            <div>
              <div class="font-semibold">{{ stock.ticker_symbol }}</div>
              <div class="text-sm text-muted-foreground">{{ stock.company_name }}</div>
            </div>
            <div class="text-right">
              <div class="font-semibold">{{ formatPrice(stock.price) }}</div>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  </div>
</template>
```

### Step 2: Commit StockSection component

```bash
git add resources/js/components/StockSection.vue
git commit -m "feat: add StockSection component"
```

---

## Task 21: Create Dashboard Page

**Files:**
- Modify: `resources/js/Pages/Dashboard.vue`

### Step 1: Read existing Dashboard.vue

Run: `cat resources/js/Pages/Dashboard.vue`

Expected: Shows existing Dashboard page (likely the authenticated one).

### Step 2: Replace Dashboard.vue content

Open `resources/js/Pages/Dashboard.vue` and replace with:

```vue
<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import type { DashboardData } from '@/types/dashboard'
import DatePicker from '@/components/DatePicker.vue'
import NewsSection from '@/components/NewsSection.vue'
import WeatherSection from '@/components/WeatherSection.vue'
import StockSection from '@/components/StockSection.vue'
import LoadingSpinner from '@/components/LoadingSpinner.vue'
import { Alert, AlertDescription } from '@/components/ui/alert'

const page = usePage()
const props = defineProps<DashboardData>()

const isLoading = ref(false)
const errorMessage = computed(() => page.props.flash?.error as string | undefined)

// Show loading during navigation
watch(() => page.props, () => {
  isLoading.value = false
}, { deep: true })
</script>

<template>
  <Head title="Daily Info Dashboard" />

  <div class="min-h-screen bg-background p-6">
    <LoadingSpinner
      v-if="isLoading"
      :message="`Fetching data for ${date}...`"
    />

    <div class="max-w-7xl mx-auto space-y-6">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 class="text-4xl font-bold">Daily Info Dashboard</h1>
          <p class="text-muted-foreground mt-1">
            Last updated: {{ new Date(lastUpdated).toLocaleString() }}
          </p>
        </div>
        <DatePicker
          :current-date="dateParam"
          :available-dates="availableDates"
        />
      </div>

      <!-- Error Alert -->
      <Alert v-if="errorMessage" variant="destructive">
        <AlertDescription>{{ errorMessage }}</AlertDescription>
      </Alert>

      <!-- News Section -->
      <NewsSection :news="news" :loading="isLoading" />

      <!-- Weather and Stocks Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <WeatherSection :weather="weather" :loading="isLoading" />
        <StockSection :stocks="stocks" :loading="isLoading" />
      </div>
    </div>
  </div>
</template>
```

### Step 3: Build frontend assets

Run: `npm run build`

Expected: Build completes successfully.

### Step 4: Commit Dashboard page

```bash
git add resources/js/Pages/Dashboard.vue
git commit -m "feat: create Dashboard page with all sections"
```

---

## Task 22: Final Integration Testing

**Files:**
- None (manual testing)

### Step 1: Start the development server

Run: `php artisan serve`

Expected: Server starts on http://localhost:8000

### Step 2: Visit the dashboard

Open browser to: `http://localhost:8000`

Expected: Dashboard loads, shows date picker, and attempts to fetch data.

### Step 3: Test date selection

Click on the date picker and select a different date.

Expected: Page updates with loading spinner, then shows data for selected date.

### Step 4: Test invalid date handling

Manually visit: `http://localhost:8000/dashboard/2020-01-01`

Expected: Redirects to today with error message.

### Step 5: Check browser console for errors

Open browser DevTools console.

Expected: No JavaScript errors.

### Step 6: Stop the server

Press `Ctrl+C` in terminal.

### Step 7: Document any issues found

If issues are found, create GitHub issues or note them for fixing.

---

## Task 23: Final Commit and Summary

**Files:**
- Create: `README-IMPLEMENTATION.md`

### Step 1: Create implementation summary

Create `README-IMPLEMENTATION.md`:

```markdown
# Daily Info Dashboard - Implementation Summary

## Completed Features

✅ In-memory SQLite database configuration
✅ Database migrations for news, weather, stocks, and relationships
✅ Eloquent models with query scopes
✅ NewsAPI integration service
✅ OpenWeatherMap integration service
✅ Tiingo stock data integration service
✅ Keyword matching service for locations and companies
✅ Data aggregation service with caching
✅ Dashboard controller with date validation
✅ Vue 3 components with TypeScript
✅ Date picker for past 7 days
✅ Responsive UI with Tailwind CSS
✅ Loading states and error handling
✅ Comprehensive test coverage

## Running the Application

1. Start development server:
   ```bash
   composer dev
   ```

2. Visit: `http://localhost:8000`

3. Run tests:
   ```bash
   php artisan test
   ```

## API Usage

The application uses three external APIs:
- **NewsAPI**: Fetches top 10 news articles
- **OpenWeatherMap**: Fetches weather for extracted locations
- **Tiingo**: Fetches stock prices for mentioned companies

All API keys are configured in `.env`.

## Architecture

- **Backend**: Laravel 12 services with in-memory SQLite
- **Frontend**: Vue 3 + Inertia.js + TypeScript
- **Testing**: Pest (PHP testing framework)
- **Styling**: Tailwind CSS with shadcn/ui components

## Key Design Decisions

1. **In-memory SQLite**: Data persists during PHP process lifetime (Herd keeps alive)
2. **Lazy loading**: Only fetch data when user requests a specific date
3. **Simple keyword matching**: No NLP libraries, just string matching for cities/companies
4. **Top 3 locations, Top 5 stocks**: Limits API usage and keeps UI clean
5. **TDD approach**: Tests written first for all services

## Future Enhancements

- Persistent database for historical data
- Advanced NLP for better keyword extraction
- Real-time updates with WebSockets
- Export functionality (PDF/CSV)
- User preferences and saved searches
```

### Step 2: Final commit

```bash
git add README-IMPLEMENTATION.md
git commit -m "docs: add implementation summary

Complete Daily Info Dashboard with all features:
- API integrations (NewsAPI, OpenWeatherMap, Tiingo)
- In-memory SQLite caching
- Keyword matching for locations and companies
- Vue 3 frontend with date picker
- Comprehensive test coverage
- TDD approach throughout"
```

### Step 3: Verify all tests pass

Run: `php artisan test`

Expected: All tests pass.

---

## Completion

**Plan complete!** The implementation plan includes:

- 23 bite-sized tasks
- Complete TDD workflow for all services
- Database setup and migrations
- API service integrations
- Vue components with TypeScript
- Controller and routing
- Testing and validation

Each task follows the pattern:
1. Write test (if TDD)
2. Run test to verify failure
3. Implement feature
4. Run test to verify pass
5. Commit

Ready for execution! 🚀
