# Claude AI Assistant Guidelines

This document provides guidelines for AI assistants (like Claude) when working on the Daily Info project.

## Project Overview

Daily Info is a Laravel + Vue 3 application that aggregates news, weather, and stock data based on keyword extraction from news articles. The architecture follows Laravel best practices with:

- Service-based business logic
- Inertia.js for seamless SPA experience
- SQLite for data persistence (in-memory for dev)
- External API integration (NewsAPI, OpenWeatherMap, Tiingo)

## Architecture Patterns

### Service Layer
All external API integrations and business logic live in `app/Services/`:

- `DataAggregatorService`: Orchestrates the entire data fetching workflow
- `NewsApiService`: Handles NewsAPI integration
- `OpenWeatherMapService`: Manages weather data fetching
- `TiingoService`: Handles stock market data
- `KeywordMatcherService`: Extracts locations and companies from text

**Pattern**: Services are constructor-injected into controllers and other services. Keep services focused on single responsibilities.

### Data Flow
```
User Request → Controller → DataAggregatorService
                                ↓
                    Check Database Cache
                                ↓
                    [If not cached]
                                ↓
                    NewsApiService → Extract Entities
                                ↓
                    ┌───────────┴───────────┐
                    ↓                       ↓
        OpenWeatherMapService    TiingoService
                    ↓                       ↓
                Store in Database
                    ↓
                Return to View
```

### Models and Relationships
- `News`: Core news articles with headline, description, URL, source
- `Weather`: Weather data with location, temperature, description
- `Stock`: Stock prices with company name, ticker, price
- `NewsRelatedData`: Pivot table linking news to weather/stocks

All models use `fetched_for_date` to cache data by date.

## Development Guidelines

### When Adding Features

1. **Check existing patterns first**: Read similar implementations before writing new code
2. **Use services for business logic**: Keep controllers thin, delegate to services
3. **Maintain type safety**: Use TypeScript on frontend, type hints on backend
4. **Follow Laravel conventions**: Use Eloquent scopes, resource routes, form requests
5. **Test with Pest**: Write feature tests for new functionality

### Code Style

**PHP**:
- PSR-12 coding standard (enforced by Laravel Pint)
- Use type declarations for parameters and return types
- Use named arguments for clarity when helpful
- Prefer explicit over implicit

**Vue/TypeScript**:
- Composition API with `<script setup>`
- TypeScript for all new components
- Props with TypeScript interfaces
- Format with Prettier (configured)

**Naming Conventions**:
- Controllers: Singular, resource-based (`DashboardController`)
- Services: Descriptive with `Service` suffix (`DataAggregatorService`)
- Models: Singular (`News`, not `NewsArticle`)
- Routes: RESTful conventions (`dashboard.date` not `get-dashboard-date`)

### Database Queries

- Use Eloquent scopes for common queries (see `News::forDate()` scope)
- Eager load relationships to avoid N+1 queries
- Use query builder for complex queries
- Remember: in-memory SQLite resets on each request in development

### API Integration

All external APIs should:
1. Be wrapped in dedicated service classes
2. Handle rate limits gracefully
3. Return consistent data structures (arrays)
4. Log errors for debugging
5. Use environment variables for API keys

**Example pattern**:
```php
public function fetchData(string $param): array
{
    try {
        $response = Http::get($this->baseUrl, [
            'apiKey' => config('services.api.key'),
            'param' => $param,
        ]);

        if ($response->failed()) {
            Log::warning('API request failed', ['status' => $response->status()]);
            return [];
        }

        return $this->transformResponse($response->json());
    } catch (\Exception $e) {
        Log::error('API error', ['error' => $e->getMessage()]);
        return [];
    }
}
```

### Frontend Development

**Inertia.js Patterns**:
- Use `<Link>` for navigation (not `<a>` tags)
- Pass data via controller through Inertia::render()
- Access page props with `$page.props`
- Use shared data for auth state, flash messages

**Component Organization**:
- Pages: Top-level routes in `resources/js/pages/`
- Components: Reusable UI in `resources/js/components/`
- UI Library: shadcn-vue components in `resources/js/components/ui/`

**State Management**:
- Props for parent-child communication
- Inertia page props for server data
- Composables (VueUse) for shared client state
- No need for Vuex/Pinia for this app's scope

## Testing Strategy

### Feature Tests (Pest)
Focus on testing user-facing functionality:

```php
test('dashboard displays news for today', function () {
    $news = News::factory()->create(['fetched_for_date' => today()->toDateString()]);

    $response = $this->get(route('dashboard'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->has('news')
        );
});
```

### Unit Tests
Test service logic in isolation:

```php
test('keyword matcher extracts company tickers', function () {
    $service = new KeywordMatcherService();
    $text = 'Apple Inc. released new products today.';

    $companies = $service->extractCompanies([$text]);

    expect($companies)->toHaveKey('AAPL');
});
```

### Running Tests
```bash
composer test
php artisan test
php artisan test --filter=DashboardTest
```

## Common Tasks

### Adding a New API Integration

1. Create service class in `app/Services/`
2. Add API key to `.env.example` and config file
3. Implement fetch method with error handling
4. Add method to `DataAggregatorService` to call it
5. Create migration for new data model if needed
6. Write tests for the integration

### Adding a UI Component

1. Check if shadcn-vue has the component
2. If custom, create in `resources/js/components/`
3. Use TypeScript for props definition
4. Follow existing naming patterns
5. Export from index file if shared

### Modifying Data Display

1. Update controller to pass additional data
2. Modify Inertia page component props
3. Update TypeScript interface for page props
4. Render in template with proper formatting
5. Test both cached and fresh data paths

## Debugging

### Backend Issues
```bash
# View logs in real-time
php artisan pail

# Clear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Check routes
php artisan route:list

# Tinker REPL
php artisan tinker
```

### Frontend Issues
- Check browser console for Vue warnings
- Use Vue DevTools for component inspection
- Check Network tab for API/Inertia requests
- Verify Vite is running for HMR

### API Issues
- Check `.env` has valid API keys
- Verify rate limits in provider dashboards
- Check Laravel logs for API errors
- Test API endpoints directly with curl/Postman

## Browser Automation and QA

### Chrome DevTools MCP Integration

This project integrates with the Chrome DevTools MCP server for AI-assisted browser automation and testing. When helping with QA tasks:

1. **Use the Chrome DevTools MCP tools**: Navigate, click, fill forms, take screenshots
2. **Take snapshots before screenshots**: Use `take_snapshot` to get the accessibility tree first
3. **Verify data loading**: Use `wait_for` to ensure content loads before assertions
4. **Performance testing**: Use `performance_start_trace` and `performance_stop_trace` for profiling
5. **Document findings**: Report issues with screenshots and specific element references

**Common QA workflows**:
- Navigate to dashboard and verify news cards display correctly
- Test date navigation by checking different date routes
- Verify responsive design by resizing the viewport
- Check accessibility compliance using verbose snapshots
- Performance trace the data aggregation flow

See [AGENTS.md](./AGENTS.md) for detailed workflows.

## Performance Considerations

### Backend Optimization
- **Database caching**: Data is cached per date to avoid repeated API calls
- **Queue jobs**: Consider moving API fetching to background jobs for large datasets
- **API batching**: Fetch multiple weather/stock items in single request when possible
- **Eager loading**: Always eager load relationships to prevent N+1

### Frontend Optimization
- **Code splitting**: Vite automatically splits routes
- **Lazy loading**: Use dynamic imports for heavy components
- **Asset optimization**: Vite handles minification and tree-shaking
- **Image optimization**: Use appropriate formats and sizes

## Security Considerations

- **API keys**: Never commit to repository, use `.env`
- **Input validation**: Validate date parameters in controller
- **SQL injection**: Use Eloquent/Query Builder (parameterized queries)
- **XSS**: Vue automatically escapes output, use `v-html` cautiously
- **CSRF**: Laravel handles automatically for forms
- **Authentication**: Fortify handles auth, use middleware for protected routes

## Helpful Commands Reference

```bash
# Development
composer dev              # Run all dev services
composer test            # Run test suite
npm run dev             # Vite dev server
npm run build           # Production build

# Code Quality
./vendor/bin/pint       # Format PHP
npm run format          # Format JS/Vue
npm run lint            # Lint JS/Vue

# Database
php artisan migrate     # Run migrations
php artisan migrate:fresh  # Reset database
php artisan tinker      # REPL

# Debugging
php artisan pail        # Tail logs
php artisan route:list  # List routes
php artisan config:clear  # Clear config cache
```

## Getting Help

- **Laravel Docs**: https://laravel.com/docs
- **Vue 3 Docs**: https://vuejs.org/
- **Inertia.js Docs**: https://inertiajs.com/
- **Pest Docs**: https://pestphp.com/
- **Tailwind CSS**: https://tailwindcss.com/

## Workflow Principles

1. **Understand before changing**: Read related code before modifications
2. **Test locally**: Run tests before committing
3. **Keep it simple**: Don't over-engineer solutions
4. **Follow conventions**: Match existing patterns
5. **Document when needed**: Add comments for complex logic
6. **Use type safety**: TypeScript and PHP type hints prevent bugs
7. **Commit atomically**: One logical change per commit
8. **Leverage MCP tools**: Use Chrome DevTools MCP for UI testing and QA

## Common Pitfalls

- **In-memory database**: Remember SQLite resets between requests in development
- **API rate limits**: Free tiers are limited, implement caching
- **Date formatting**: Use Carbon for PHP, native Date for JS, keep formats consistent
- **Inertia navigation**: Always use `<Link>` component, not `<a>` tags
- **Type mismatches**: Vue props are strings by default, parse when needed
- **Missing API keys**: Application will fail silently if keys are missing
- **Queue not running**: Background jobs require `queue:work` process

## Questions to Ask

When working on this project, consider:

1. Does this follow existing patterns in the codebase?
2. Is there a service that already does something similar?
3. Should this be cached in the database?
4. Does this need a background job?
5. What happens if the API fails?
6. Is this testable with Pest?
7. Does the frontend need type definitions?
8. Will this work with cached and fresh data?

## Final Notes

This project prioritizes:
- **Simplicity** over complexity
- **Convention** over configuration
- **Type safety** over runtime errors
- **Service isolation** over god objects
- **Test coverage** over untested code

When in doubt, follow Laravel and Vue best practices, and maintain consistency with existing code patterns.
