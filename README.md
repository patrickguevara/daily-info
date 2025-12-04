# Daily Info

A Laravel + Vue 3 application that aggregates daily news, weather, and stock market data into a single dashboard. The application fetches news articles and intelligently extracts location and company information to provide relevant weather forecasts and stock prices.

## Features

- **News Aggregation**: Fetches top headlines using NewsAPI
- **Smart Data Matching**: Automatically extracts locations and company mentions from news articles
- **Weather Forecasts**: Retrieves weather data for locations mentioned in news using OpenWeatherMap
- **Stock Prices**: Displays stock information for companies mentioned in news using Tiingo API
- **Date-Based Views**: Browse news and data from any date
- **Data Caching**: Stores fetched data in SQLite database to avoid redundant API calls
- **Modern UI**: Built with Vue 3, Inertia.js, and Tailwind CSS with shadcn-vue components

## Tech Stack

### Backend
- **Laravel 12**: PHP framework
- **Laravel Fortify**: Authentication
- **Inertia.js**: Server-side routing with SPA experience
- **SQLite**: Database (in-memory for development)
- **Pest**: Testing framework

### Frontend
- **Vue 3**: Progressive JavaScript framework
- **TypeScript**: Type-safe JavaScript
- **Tailwind CSS 4**: Utility-first CSS framework
- **shadcn-vue**: UI component library
- **Vite**: Build tool

### APIs
- **NewsAPI**: News articles
- **OpenWeatherMap**: Weather data
- **Tiingo**: Stock market data

## Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js 18+ and npm
- SQLite (included with PHP)
- API Keys for:
  - [NewsAPI](https://newsapi.org/)
  - [OpenWeatherMap](https://openweathermap.org/api)
  - [Tiingo](https://www.tiingo.com/)

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/patrickguevara/daily-info.git
   cd daily-info
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   ```

4. **Add API keys to `.env`**
   ```env
   NEWSAPI_KEY=your_newsapi_key
   OPENWEATHER_API_KEY=your_openweather_key
   TIINGO_API_KEY=your_tiingo_key
   ```

5. **Generate application key**
   ```bash
   php artisan key:generate
   ```

6. **Run migrations**
   ```bash
   php artisan migrate
   ```

7. **Build frontend assets**
   ```bash
   npm run build
   ```

## Development

### Quick Start

Use the composer development script to run all services concurrently:

```bash
composer dev
```

This starts:
- Laravel development server (port 8000)
- Queue worker
- Log viewer (Pail)
- Vite dev server (HMR)

### Individual Commands

Alternatively, run services separately:

```bash
# Start Laravel server
php artisan serve

# Start Vite dev server
npm run dev

# Process queue jobs
php artisan queue:work

# View logs
php artisan pail
```

### Testing

Run the test suite:

```bash
composer test
# or
php artisan test
```

### Code Quality

```bash
# Format code (Prettier)
npm run format

# Check formatting
npm run format:check

# Lint JavaScript/Vue
npm run lint

# Format PHP (Pint)
./vendor/bin/pint
```

## Development with Chrome DevTools MCP

This project integrates with the Chrome DevTools MCP server for enhanced development and QA workflows. The Chrome DevTools MCP allows AI assistants to interact with the application through browser automation.

### QA and Testing Workflow

Use Claude Code with the Chrome DevTools MCP to:

1. **Automated UI Testing**: Navigate the application and verify UI elements
2. **Visual Regression**: Take screenshots and compare UI states
3. **Performance Analysis**: Run performance traces and analyze Core Web Vitals
4. **Accessibility Audits**: Check accessibility compliance using the a11y tree
5. **Cross-browser Testing**: Test functionality across different browsers
6. **User Flow Testing**: Simulate user interactions and verify behavior

### Example QA Tasks

```bash
# Example Claude Code prompts:

"Navigate to the dashboard and verify all news cards are displaying"

"Take a screenshot of the dashboard at /dashboard/2024-01-15"

"Run a performance trace on the homepage and identify bottlenecks"

"Check for accessibility issues in the date selector"

"Test the news loading flow: navigate to dashboard, wait for data, verify layout"
```

For more details on using the Chrome DevTools MCP in development workflows, see [AGENTS.md](./AGENTS.md).

## Project Structure

```
daily-info/
├── app/
│   ├── Http/Controllers/
│   │   └── DashboardController.php    # Main dashboard controller
│   ├── Models/                        # Eloquent models
│   │   ├── News.php
│   │   ├── Weather.php
│   │   ├── Stock.php
│   │   └── NewsRelatedData.php
│   └── Services/                      # Business logic
│       ├── DataAggregatorService.php  # Orchestrates data fetching
│       ├── NewsApiService.php         # NewsAPI integration
│       ├── OpenWeatherMapService.php  # Weather API integration
│       ├── TiingoService.php          # Stock API integration
│       └── KeywordMatcherService.php  # NLP for entity extraction
├── resources/
│   └── js/
│       ├── pages/                     # Inertia pages
│       │   ├── Welcome.vue
│       │   └── Dashboard.vue
│       └── components/                # Vue components
├── routes/
│   └── web.php                        # Application routes
├── tests/                             # Pest tests
└── database/
    └── migrations/                    # Database schema
```

## How It Works

1. **User requests data for a date**: Via the dashboard or URL parameter
2. **Check cache**: System checks if data exists for that date in the database
3. **Fetch news**: If not cached, fetches top headlines from NewsAPI
4. **Extract entities**: Uses keyword matching to find locations and company tickers in news
5. **Fetch related data**: Retrieves weather for locations and stock prices for companies
6. **Store and display**: Saves all data to database and renders on dashboard
7. **Subsequent requests**: Returns cached data instantly

## API Configuration

### NewsAPI
- Free tier: 100 requests/day
- Returns top headlines from major news sources
- Date parameter required: `from` and `to` with `q=a` (query workaround)

### OpenWeatherMap
- Free tier: 60 calls/minute, 1,000,000 calls/month
- Current weather data by city name
- Returns temperature, description, and conditions

### Tiingo
- Free tier: 500 requests/hour, 50,000 requests/month
- Real-time and end-of-day stock prices
- Access to major stock exchanges

## Environment Variables

Key environment variables (see `.env.example` for full list):

```env
APP_NAME=DailyInfo
APP_URL=http://localhost:8000

DB_CONNECTION=sqlite

NEWSAPI_KEY=
OPENWEATHER_API_KEY=
TIINGO_API_KEY=

QUEUE_CONNECTION=database
CACHE_STORE=database
```

## Contributing

See [CONTRIBUTING.md](./CONTRIBUTING.md) for development guidelines and workflows.

## AI Development

This project includes guidelines for AI-assisted development:

- [CLAUDE.md](./CLAUDE.md): General AI assistant guidelines
- [AGENTS.md](./AGENTS.md): Specialized agent workflows and MCP integration

## License

MIT

## Troubleshooting

### API Rate Limits
If you encounter rate limit errors, the application will return partial data. Check your API usage in respective dashboards.

### Database Issues
Reset the database:
```bash
php artisan migrate:fresh
```

### Asset Build Errors
Clear Vite cache:
```bash
rm -rf node_modules/.vite
npm run build
```

### Queue Not Processing
Ensure queue worker is running:
```bash
php artisan queue:work
```

## Credits

Built by [Patrick Guevara](https://github.com/patrickguevara)
