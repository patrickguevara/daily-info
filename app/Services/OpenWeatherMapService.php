<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenWeatherMapService
{
    private ?string $apiKey;
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
