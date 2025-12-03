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
