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
