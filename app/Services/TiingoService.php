<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TiingoService
{
    private ?string $apiKey;
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
