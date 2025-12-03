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
