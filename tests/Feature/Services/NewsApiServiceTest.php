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
