# Contributing to Daily Info

Thank you for considering contributing to Daily Info! This document outlines the development workflow, coding standards, and best practices for contributors.

## Table of Contents

- [Getting Started](#getting-started)
- [Development Workflow](#development-workflow)
- [Coding Standards](#coding-standards)
- [Testing Requirements](#testing-requirements)
- [QA Process with Chrome DevTools MCP](#qa-process-with-chrome-devtools-mcp)
- [Pull Request Process](#pull-request-process)
- [Git Conventions](#git-conventions)
- [Project Architecture](#project-architecture)
- [API Integration Guidelines](#api-integration-guidelines)

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- npm
- SQLite
- Git
- API keys (NewsAPI, OpenWeatherMap, Tiingo)

### Initial Setup

1. Fork the repository on GitHub
2. Clone your fork locally:
   ```bash
   git clone https://github.com/YOUR_USERNAME/daily-info.git
   cd daily-info
   ```

3. Install dependencies:
   ```bash
   composer install
   npm install
   ```

4. Configure environment:
   ```bash
   cp .env.example .env
   # Add your API keys to .env
   php artisan key:generate
   ```

5. Run migrations:
   ```bash
   php artisan migrate
   ```

6. Start development environment:
   ```bash
   composer dev
   ```

7. Verify tests pass:
   ```bash
   composer test
   ```

## Development Workflow

### 1. Create a Feature Branch

Always work on a feature branch, never directly on `main`:

```bash
git checkout -b feature/your-feature-name
```

Branch naming conventions:
- `feature/feature-name` - New features
- `fix/bug-description` - Bug fixes
- `refactor/component-name` - Code refactoring
- `docs/what-changed` - Documentation updates
- `test/what-testing` - Test additions/improvements

### 2. Make Your Changes

- Follow [coding standards](#coding-standards)
- Write tests for new functionality
- Update documentation as needed
- Keep commits atomic and well-described

### 3. Test Your Changes

Before pushing:

```bash
# Run test suite
composer test

# Format code
./vendor/bin/pint
npm run format

# Lint code
npm run lint

# Manual testing
composer dev
# Test in browser
```

### 4. Use Chrome DevTools MCP for QA

For UI changes, perform browser-based QA:

```bash
# Ensure app is running
composer dev
```

Use AI assistant with Chrome DevTools MCP to:
- Navigate to affected pages
- Take snapshots to verify structure
- Take screenshots to document changes
- Test interactions (clicks, forms, navigation)
- Check console for errors
- Verify responsive design
- Run performance traces

See [AGENTS.md](./AGENTS.md) for detailed QA workflows.

### 5. Commit Your Changes

Follow [git conventions](#git-conventions):

```bash
git add .
git commit -m "feat: add weather forecast detail view"
```

### 6. Push and Create Pull Request

```bash
git push origin feature/your-feature-name
```

Create a pull request on GitHub following the [PR template](#pull-request-process).

## Coding Standards

### PHP Standards

**Style Guide**: PSR-12 (enforced by Laravel Pint)

```bash
# Format PHP code
./vendor/bin/pint

# Check specific files
./vendor/bin/pint path/to/file.php
```

**Best Practices**:

```php
// Use type declarations
public function fetchNews(string $date): array
{
    // Implementation
}

// Use named arguments for clarity
$service->fetchData(
    date: '2024-01-15',
    limit: 10,
    includeImages: true
);

// Use early returns
public function processData(?string $data): array
{
    if ($data === null) {
        return [];
    }

    return $this->transform($data);
}

// Use Eloquent scopes
// In Model:
public function scopeForDate($query, string $date)
{
    return $query->where('fetched_for_date', $date);
}

// In Controller:
$news = News::forDate($date)->get();
```

### JavaScript/TypeScript Standards

**Style Guide**: Prettier + ESLint (configured)

```bash
# Format JS/Vue files
npm run format

# Check formatting
npm run format:check

# Lint code
npm run lint
```

**Best Practices**:

```typescript
// Use TypeScript for all new code
interface NewsItem {
    headline: string;
    description: string;
    url: string;
    source: string;
    published_at: string;
}

// Vue 3 Composition API
<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';

interface Props {
    items: NewsItem[];
}

const props = defineProps<Props>();

const filteredItems = computed(() => {
    return props.items.filter(item => item.headline.length > 0);
});

onMounted(() => {
    // Initialization logic
});
</script>

// Use composables for shared logic
// composables/useFormatting.ts
export function useFormatting() {
    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString();
    };

    return { formatDate };
}
```

### Vue Component Standards

```vue
<script setup lang="ts">
// 1. Imports
import { ref } from 'vue';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/card';

// 2. Props
interface Props {
    title: string;
    count?: number;
}

const props = withDefaults(defineProps<Props>(), {
    count: 0,
});

// 3. Emits (if needed)
const emit = defineEmits<{
    click: [id: number];
}>();

// 4. State
const isExpanded = ref(false);

// 5. Computed
const displayText = computed(() => {
    return `${props.title}: ${props.count}`;
});

// 6. Methods
const handleClick = () => {
    emit('click', props.count);
};

// 7. Lifecycle
onMounted(() => {
    // Initialization
});
</script>

<template>
    <Card @click="handleClick">
        <CardHeader>
            <CardTitle>{{ displayText }}</CardTitle>
        </CardHeader>
        <CardContent>
            <!-- Content -->
        </CardContent>
    </Card>
</template>
```

### Naming Conventions

| Type | Convention | Example |
|------|------------|---------|
| PHP Classes | PascalCase | `DataAggregatorService` |
| PHP Methods | camelCase | `fetchWeatherData()` |
| PHP Variables | camelCase | `$newsArticles` |
| Vue Components | PascalCase | `NewsCard.vue` |
| JS Functions | camelCase | `formatDate()` |
| JS Variables | camelCase | `newsItems` |
| Constants | UPPER_SNAKE | `MAX_RETRIES` |
| Routes | kebab-case | `dashboard.date` |
| CSS Classes | kebab-case | `news-card` |

## Testing Requirements

### Test Coverage

All contributions must include appropriate tests:

- **New features**: Feature tests + unit tests for services
- **Bug fixes**: Regression tests to prevent recurrence
- **Refactoring**: Ensure existing tests still pass

### Writing Tests

Use Pest for all PHP tests:

```php
// tests/Feature/DashboardTest.php
<?php

use App\Models\News;
use function Pest\Laravel\get;

test('dashboard displays news for date', function () {
    $date = '2024-01-15';
    $news = News::factory()->create([
        'fetched_for_date' => $date,
    ]);

    $response = get(route('dashboard.date', ['date' => $date]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->has('news.0', fn ($item) => $item
                ->where('headline', $news->headline)
            )
        );
});

test('dashboard handles missing data gracefully', function () {
    $response = get(route('dashboard.date', ['date' => '2024-01-15']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('news', [])
        );
});
```

```php
// tests/Unit/Services/KeywordMatcherServiceTest.php
<?php

use App\Services\KeywordMatcherService;

test('extracts company tickers from text', function () {
    $service = new KeywordMatcherService();

    $articles = [
        ['headline' => 'Apple releases new iPhone'],
        ['headline' => 'Microsoft announces Azure updates'],
    ];

    $companies = $service->extractCompanies($articles);

    expect($companies)
        ->toHaveKey('AAPL')
        ->toHaveKey('MSFT');
});

test('extracts locations from text', function () {
    $service = new KeywordMatcherService();

    $articles = [
        ['headline' => 'Breaking news in New York'],
        ['headline' => 'London sees heavy rainfall'],
    ];

    $locations = $service->extractLocations($articles);

    expect($locations)
        ->toContain('New York')
        ->toContain('London');
});
```

### Running Tests

```bash
# Run all tests
composer test

# Run specific test file
php artisan test --filter=DashboardTest

# Run with coverage
php artisan test --coverage

# Run in parallel
php artisan test --parallel
```

## QA Process with Chrome DevTools MCP

### Manual QA Requirements

For all UI changes, perform manual QA using Chrome DevTools MCP:

1. **Visual Verification**
   - Navigate to affected pages
   - Take snapshots to verify element structure
   - Take screenshots to document appearance
   - Test at mobile, tablet, and desktop sizes

2. **Interaction Testing**
   - Click buttons and links
   - Fill and submit forms
   - Test keyboard navigation
   - Verify hover states

3. **Error Checking**
   - Check browser console for errors
   - Verify network requests succeed
   - Test error states (invalid input, missing data)

4. **Performance Validation**
   - Run performance traces
   - Check Core Web Vitals (LCP, FID, CLS)
   - Verify acceptable load times

5. **Accessibility Audit**
   - Take verbose snapshots
   - Verify ARIA labels and roles
   - Test keyboard navigation
   - Ensure proper focus management

### QA Documentation

Document QA results in your pull request:

```markdown
## QA Results

### Visual Testing
- ✓ Desktop (1920x1080): [screenshot link]
- ✓ Tablet (768x1024): [screenshot link]
- ✓ Mobile (375x667): [screenshot link]

### Interaction Testing
- ✓ Date picker works correctly
- ✓ News cards clickable
- ✓ Keyboard navigation functional

### Console/Network
- ✓ No console errors
- ✓ All API requests succeed
- ✓ Average load time: 1.2s

### Performance
- LCP: 1.8s (Good)
- FID: 45ms (Good)
- CLS: 0.05 (Good)

### Accessibility
- ✓ Proper ARIA labels
- ✓ Logical tab order
- ✓ Focus indicators visible
```

See [AGENTS.md](./AGENTS.md) for detailed MCP testing workflows.

## Pull Request Process

### Before Submitting

Checklist:
- [ ] Tests written and passing (`composer test`)
- [ ] Code formatted (`./vendor/bin/pint`, `npm run format`)
- [ ] Code linted (`npm run lint`)
- [ ] Manual QA performed with Chrome DevTools MCP (for UI changes)
- [ ] Documentation updated (if applicable)
- [ ] Commit messages follow conventions
- [ ] Branch rebased on latest `main`

### PR Title Format

Follow conventional commits:

```
feat: add weather detail modal
fix: resolve date picker timezone issue
refactor: extract news service logic
docs: update API integration guide
test: add coverage for stock service
```

### PR Description Template

```markdown
## Description
Brief description of what this PR does.

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Refactoring
- [ ] Documentation update
- [ ] Test improvement

## Changes Made
- Added WeatherDetailModal component
- Updated DataAggregatorService to fetch extended forecast
- Added tests for new functionality

## Testing
### Automated Tests
- All tests passing: ✓
- New tests added: ✓
- Coverage maintained/improved: ✓

### Manual QA (Chrome DevTools MCP)
- Visual verification: ✓
- Interaction testing: ✓
- Performance check: ✓
- Accessibility audit: ✓

[Link to QA screenshots/videos]

## Screenshots
[If UI changes, include before/after screenshots]

## Breaking Changes
None / [Description of breaking changes]

## Related Issues
Closes #123
```

### Review Process

1. Automated checks must pass (tests, linting)
2. At least one approval from maintainer
3. QA verification for UI changes
4. Discussion/changes if requested
5. Squash and merge when approved

## Git Conventions

### Commit Message Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

**Types**:
- `feat`: New feature
- `fix`: Bug fix
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `docs`: Documentation changes
- `style`: Code style changes (formatting)
- `perf`: Performance improvements
- `chore`: Maintenance tasks

**Examples**:

```bash
feat(services): add weather forecast caching

Implement caching layer for OpenWeatherMap API responses
to reduce API calls and improve response time.

- Add WeatherCache model
- Update OpenWeatherMapService to check cache
- Set cache TTL to 1 hour

Closes #45
```

```bash
fix(dashboard): handle missing API keys gracefully

Previously the app would crash if API keys were missing.
Now it logs a warning and returns empty data.

Fixes #67
```

### Branch Management

- Keep branches short-lived
- Rebase on `main` regularly
- Delete branches after merge
- One feature/fix per branch

```bash
# Update your branch with latest main
git checkout main
git pull origin main
git checkout feature/your-feature
git rebase main

# If conflicts, resolve and continue
git rebase --continue
```

## Project Architecture

### Service Layer Pattern

Business logic lives in services, not controllers:

```php
// ❌ Bad: Logic in controller
class DashboardController extends Controller
{
    public function index()
    {
        $news = Http::get('https://newsapi.org/...')->json();
        $weather = Http::get('https://api.openweathermap.org/...')->json();
        // ... more logic

        return Inertia::render('Dashboard', compact('news', 'weather'));
    }
}

// ✅ Good: Delegate to service
class DashboardController extends Controller
{
    public function __construct(
        private DataAggregatorService $aggregator
    ) {}

    public function index()
    {
        $data = $this->aggregator->aggregateData(today()->toDateString());

        return Inertia::render('Dashboard', $data);
    }
}
```

### Component Organization

```
resources/js/
├── pages/              # Inertia pages (routes)
│   ├── Welcome.vue
│   └── Dashboard.vue
├── components/         # Reusable components
│   ├── NewsCard.vue
│   ├── WeatherWidget.vue
│   └── ui/            # shadcn-vue components
│       ├── card/
│       ├── button/
│       └── ...
├── composables/        # Shared logic
│   ├── useFormatting.ts
│   └── useAuth.ts
└── lib/               # Utilities
    └── utils.ts
```

### Data Flow

```
User Request
    ↓
Route (web.php)
    ↓
Controller (thin, delegates to service)
    ↓
Service (business logic, API calls)
    ↓
Model (database interaction)
    ↓
Return to Controller
    ↓
Inertia::render() with data
    ↓
Vue Page Component
    ↓
Render UI
```

## API Integration Guidelines

### Creating a New API Integration

1. **Create Service Class**
   ```php
   // app/Services/NewApiService.php
   namespace App\Services;

   use Illuminate\Support\Facades\Http;
   use Illuminate\Support\Facades\Log;

   class NewApiService
   {
       private string $baseUrl;
       private string $apiKey;

       public function __construct()
       {
           $this->baseUrl = config('services.newapi.url');
           $this->apiKey = config('services.newapi.key');
       }

       public function fetchData(string $param): array
       {
           try {
               $response = Http::get($this->baseUrl . '/endpoint', [
                   'apiKey' => $this->apiKey,
                   'param' => $param,
               ]);

               if ($response->failed()) {
                   Log::warning('NewAPI request failed', [
                       'status' => $response->status(),
                       'param' => $param,
                   ]);
                   return [];
               }

               return $this->transformResponse($response->json());
           } catch (\Exception $e) {
               Log::error('NewAPI error', [
                   'error' => $e->getMessage(),
                   'param' => $param,
               ]);
               return [];
           }
       }

       private function transformResponse(array $data): array
       {
           // Transform API response to application format
           return array_map(fn ($item) => [
               'field1' => $item['api_field1'],
               'field2' => $item['api_field2'],
           ], $data['results'] ?? []);
       }
   }
   ```

2. **Add Configuration**
   ```php
   // config/services.php
   'newapi' => [
       'url' => env('NEWAPI_URL', 'https://api.example.com'),
       'key' => env('NEWAPI_KEY'),
   ],
   ```

3. **Update .env.example**
   ```env
   NEWAPI_URL=https://api.example.com
   NEWAPI_KEY=your_api_key_here
   ```

4. **Write Tests**
   ```php
   test('fetches data from NewAPI', function () {
       Http::fake([
           'api.example.com/*' => Http::response(['results' => [
               ['api_field1' => 'value1', 'api_field2' => 'value2'],
           ]], 200),
       ]);

       $service = new NewApiService();
       $result = $service->fetchData('test');

       expect($result)
           ->toHaveCount(1)
           ->and($result[0])->toMatchArray([
               'field1' => 'value1',
               'field2' => 'value2',
           ]);
   });
   ```

### Error Handling

Always handle API failures gracefully:

- Return empty arrays/null for missing data
- Log errors with context
- Don't expose API keys in logs
- Provide fallback UI for missing data

## Questions?

If you have questions about contributing:

1. Check existing documentation (README, CLAUDE.md, AGENTS.md)
2. Review closed pull requests for examples
3. Open an issue for discussion
4. Reach out to maintainers

## Code of Conduct

- Be respectful and constructive
- Welcome newcomers
- Focus on the code, not the person
- Assume good intentions
- Follow project guidelines

Thank you for contributing to Daily Info!
