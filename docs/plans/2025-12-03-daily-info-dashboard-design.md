# Daily Info Dashboard - Design Document

**Date:** December 3, 2025
**Project:** Laravel 12 + Vue 3 + Inertia.js Dashboard
**Purpose:** Integrate NewsAPI, OpenWeatherMap, and Tiingo APIs to display daily news with contextual weather and stock data

---

## Overview

Build an application that fetches top news stories and enriches them with relevant weather and stock market information. Users can view integrated data for today or select any date within the past week. Data is stored in an in-memory SQLite database for session-based caching.

---

## Architecture

### System Components

1. **API Service Layer** - Dedicated service classes for each external API
2. **Data Processing Layer** - Keyword matching and data aggregation
3. **Controller Layer** - Single controller handling dashboard routes
4. **Database Layer** - In-memory SQLite with Eloquent models
5. **Frontend Layer** - Vue 3 components with Inertia.js

### Technology Stack

- **Backend:** Laravel 12, Guzzle HTTP Client, SQLite in-memory
- **Frontend:** Vue 3, Inertia.js, TypeScript, Tailwind CSS
- **APIs:** NewsAPI, OpenWeatherMap, Tiingo

### Data Flow

```
User visits page → Controller checks SQLite cache for date
   ↓ (cache miss)
Controller → DataAggregatorService
   ↓
Fetch top 10 news from NewsAPI
   ↓
KeywordMatcher extracts top 3 locations & top 5 companies
   ↓
Parallel API calls:
   - OpenWeatherMap (3 locations)
   - Tiingo (5 stock tickers)
   ↓
Store all data in SQLite with relationships
   ↓
Return to Vue component with loading states
   ↓
Display integrated dashboard
```

---

## Database Schema

### SQLite Configuration

In-memory SQLite connection in `config/database.php`:

```php
'memory' => [
    'driver' => 'sqlite',
    'database' => ':memory:',
    'prefix' => '',
],
```

### Tables

**news**
- `id` - primary key
- `headline` - string
- `description` - text, nullable
- `url` - string
- `source` - string
- `published_at` - timestamp
- `fetched_for_date` - date (the date this news represents)
- `created_at`, `updated_at`

**weather**
- `id` - primary key
- `location` - string (city name)
- `temperature` - decimal
- `description` - string (weather condition)
- `fetched_for_date` - date
- `created_at`, `updated_at`

**stocks**
- `id` - primary key
- `company_name` - string
- `ticker_symbol` - string
- `price` - decimal
- `fetched_for_date` - date
- `created_at`, `updated_at`

**news_related_data** (pivot table)
- `id` - primary key
- `news_id` - foreign key
- `weather_id` - nullable foreign key
- `stock_id` - nullable foreign key

### Eloquent Models

- `News`, `Weather`, `Stock` models with relationships
- News hasMany Weather/Stock through pivot table
- Query scopes for filtering by date

### Session Management

- Service provider runs migrations on application boot
- Ensures tables exist for every request
- Data persists during PHP process lifetime (Herd keeps process alive)

---

## API Services

### NewsApiService

**Location:** `app/Services/NewsApiService.php`

**Responsibilities:**
- Fetch top 10 headlines for a given date
- Use `everything` endpoint with `sortBy=publishedAt`
- Return structured array: headline, description, url, source, published_at

**Error Handling:**
- Catch API failures, return empty array
- Log errors to Laravel log
- Handle rate limiting gracefully

### OpenWeatherMapService

**Location:** `app/Services/OpenWeatherMapService.php`

**Responsibilities:**
- Accept city name, return current weather
- Use current weather API endpoint
- Return temperature (Celsius), description, location

**Optimization:**
- Cache results per city within same request
- Avoid duplicate API calls for same location

### TiingoService

**Location:** `app/Services/TiingoService.php`

**Responsibilities:**
- Accept ticker symbol(s), return latest stock prices
- Use end-of-day or IEX endpoint
- Support batch requests for multiple tickers

**Return Data:**
- Company name, ticker symbol, current price

### KeywordMatcherService

**Location:** `app/Services/KeywordMatcherService.php`

**Responsibilities:**
- Extract locations and companies from news articles
- Use predefined lists of ~50 major cities and ~100 top S&P companies
- Count mentions, return top 3 locations and top 5 companies

**Data:**

```php
private array $majorCities = [
    'New York', 'Los Angeles', 'Chicago', 'Houston', 'London',
    'Tokyo', 'Paris', 'Berlin', 'Sydney', 'Toronto',
    // ... ~50 major world cities
];

private array $companies = [
    'Apple' => 'AAPL',
    'Microsoft' => 'MSFT',
    'Google' => 'GOOGL',
    'Amazon' => 'AMZN',
    'Tesla' => 'TSLA',
    // ... ~100 top S&P 500 companies
];
```

**Methods:**
- `extractLocations(array $newsArticles): array`
- `extractCompanies(array $newsArticles): array`

**Fallback Logic:**
- No locations found → Default to 'New York'
- No companies found → Default to 'SPY' (S&P 500 ETF)

### DataAggregatorService

**Location:** `app/Services/DataAggregatorService.php`

**Orchestration Flow:**
1. Fetch news for requested date from NewsAPI
2. Pass news to KeywordMatcher to extract locations/companies
3. Fetch weather for top 3 locations (parallel requests)
4. Fetch stocks for top 5 companies (parallel requests)
5. Store all data in SQLite with relationships via pivot table
6. Return structured data to controller

**Performance:**
- Use parallel HTTP requests where possible
- Set 10-second timeout on all external API calls
- Limit to top 3 locations and top 5 stocks to control API usage

---

## Controller & Routes

### Routes

**File:** `routes/web.php`

```php
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/{date}', [DashboardController::class, 'show'])->name('dashboard.date');
```

### DashboardController

**Location:** `app/Http/Controllers/DashboardController.php`

**Methods:**

```php
public function index()
{
    // Redirect to today's data
    return $this->show(now()->format('Y-m-d'));
}

public function show(string $date)
{
    // 1. Validate date is within past 7 days
    $requestedDate = Carbon::parse($date);
    $oldestAllowed = now()->subDays(6)->startOfDay();

    if ($requestedDate->lt($oldestAllowed) || $requestedDate->gt(now())) {
        return redirect()->route('dashboard')
            ->with('error', 'We can only show data from the past week. Showing today\'s data instead.');
    }

    // 2. Check if data exists in SQLite for this date
    // 3. If cache miss, fetch via DataAggregatorService
    // 4. Return Inertia response with data

    return Inertia::render('Dashboard', [
        'date' => $requestedDate->format('M d, Y'), // "Dec 03, 2025"
        'dateParam' => $date, // "2025-12-03" for routing
        'news' => $news, // top 5 articles
        'weather' => $weather, // top 3 locations
        'stocks' => $stocks, // top 5 companies
        'availableDates' => $this->getAvailableDates(),
        'lastUpdated' => now()->toIso8601String(),
    ]);
}

private function getAvailableDates(): array
{
    // Return past 7 days as array for date picker
    // Format: [{ label: 'Dec 03, 2025', value: '2025-12-03' }, ...]
}
```

**Date Format:**
- Route parameter: `Y-m-d` (e.g., "2025-12-03")
- Display format: `M d, Y` (e.g., "Dec 03, 2025")

---

## Frontend Components

### Component Structure

**Main Page:** `resources/js/Pages/Dashboard.vue`

**Child Components:** `resources/js/Components/`
- `DatePicker.vue` - Date selector for past 7 days
- `NewsSection.vue` - Display top 5 news headlines
- `WeatherSection.vue` - Weather cards for top 3 locations
- `StockSection.vue` - Stock ticker display for top 5 companies
- `LoadingSpinner.vue` - Loading state overlay

### Dashboard Layout

```
┌─────────────────────────────────────────┐
│  Daily Info Dashboard                   │
│  [Date Picker: Dec 03, 2025 ▼]         │
└─────────────────────────────────────────┘
┌─────────────────────────────────────────┐
│  📰 Top News Headlines                  │
│  ┌──────────────────────────────────┐  │
│  │ Headline 1                       │  │
│  │ Source • Dec 03, 2025           │  │
│  └──────────────────────────────────┘  │
│  [4 more news cards...]                │
└─────────────────────────────────────────┘
┌──────────────┬──────────────────────────┐
│ 🌤 Weather   │  📈 Stocks               │
│ New York     │  AAPL    $175.23  ↑      │
│ 72°F Sunny   │  MSFT    $380.45  ↑      │
│              │  GOOGL   $142.18  ↓      │
│ London       │  [2 more stocks...]      │
│ 55°F Cloudy  │                          │
└──────────────┴──────────────────────────┘
```

### TypeScript Interfaces

```typescript
interface DashboardData {
    date: string; // "Dec 03, 2025"
    dateParam: string; // "2025-12-03" for routing
    news: NewsArticle[];
    weather: WeatherData[];
    stocks: StockData[];
    availableDates: DateOption[];
    lastUpdated: string;
}

interface NewsArticle {
    id: number;
    headline: string;
    description?: string;
    url: string;
    source: string;
    published_at: string;
}

interface WeatherData {
    id: number;
    location: string;
    temperature: number;
    description: string;
}

interface StockData {
    id: number;
    company_name: string;
    ticker_symbol: string;
    price: number;
}

interface DateOption {
    label: string; // "Dec 03, 2025"
    value: string; // "2025-12-03"
}
```

### DatePicker Component

**Features:**
- Generate array of past 7 days (today to 6 days ago)
- Disable dates outside valid range
- Display in "Mon Dec 03, 2025" format
- Only allow selection of valid dates

**Implementation:**
- Dropdown/select component
- On change, use Inertia router to navigate:
  ```js
  router.get(`/dashboard/${dateParam}`, {}, {
      preserveScroll: true
  })
  ```

### Loading States

**Initial Page Load:**
- Skeleton loaders for news/weather/stock sections

**Date Change:**
1. Show loading overlay with spinner
2. Display "Fetching data for Dec 03, 2025..."
3. Smooth transition when data loads
4. Use Inertia's progress indicator

**User Feedback:**
- Toast notifications for errors
- "Last updated" timestamp
- Retry button if all APIs fail

---

## Error Handling

### API Failures

**Strategy:**
- Wrap all external API calls in try-catch blocks
- Log errors to Laravel log
- Return graceful fallbacks

**Fallback Behavior:**
- News fails → Show "Unable to fetch news" message
- Weather fails → Skip that location, continue with others
- Stock fails → Show S&P 500 (SPY) as fallback

### Edge Cases

1. **No keywords found**
   - Default to New York weather + S&P 500 stocks

2. **Invalid date selection**
   - DatePicker prevents invalid dates in UI
   - If user manually types URL with invalid date:
     - Redirect to today
     - Flash message: "We can only show data from the past week. Showing today's data instead."

3. **API rate limits**
   - Cache responses in SQLite
   - Show cached data with "Last updated" timestamp

4. **Empty news results**
   - Display message: "No news available for this date"

5. **Network timeouts**
   - Set 10-second timeout on all HTTP requests
   - Fail gracefully with user-friendly message

6. **In-memory DB wiped**
   - Migrations run on every request
   - Tables always exist

### User Feedback

- Toast notifications for errors
- Skeleton loaders during initial load
- Spinner overlay during date changes
- "Last updated" timestamp for all data
- Retry button if complete failure

---

## Performance Considerations

1. **Parallel API Requests**
   - Use promises/async for weather and stock fetching
   - Don't wait for sequential completion

2. **Request Caching**
   - Cache API responses within same request
   - Avoid duplicate calls for same city/ticker

3. **API Usage Limits**
   - Limit to top 3 locations and top 5 stocks
   - Reduces API calls and improves response time

4. **Connection Pooling**
   - Use Laravel's HTTP client with connection pooling
   - Reuse connections for multiple API calls

5. **Session Caching**
   - SQLite serves as session cache
   - Data persists during PHP process (Herd keeps alive)
   - No external cache needed (Redis, files, etc.)

---

## API Keys

**NewsAPI:** `64bbfa65330e4d838bf9a197505b2e01`
**OpenWeatherMap:** `b478e44facd0c82ef304e04c80ca9e0f`
**Tiingo:** `27465c83310d7739ab0767eb939a179db9241985`

Store in `.env`:
```
NEWS_API_KEY=64bbfa65330e4d838bf9a197505b2e01
OPENWEATHER_API_KEY=b478e44facd0c82ef304e04c80ca9e0f
TIINGO_API_KEY=27465c83310d7739ab0767eb939a179db9241985
```

---

## Implementation Notes

### Simplifications (YAGNI)

- No Docker/containerization
- No complex caching layer (Redis, etc.)
- No background jobs/schedulers
- No user authentication
- No data persistence between restarts
- No pagination (top 5 news is enough)
- Simple keyword matching (no NLP libraries)

### Focus Areas

1. **Clean service architecture** - Separation of concerns
2. **Smooth UX** - Loading states, error handling
3. **Reliable API integration** - Timeouts, retries, fallbacks
4. **Simple but effective** - Keyword matching that works

### Future Enhancements (Out of Scope)

- User accounts and preferences
- Persistent database for historical data
- Advanced NLP for better keyword extraction
- Email/SMS notifications
- Export to PDF/CSV
- More data sources (Reddit, Twitter, etc.)
- Customizable dashboard widgets
