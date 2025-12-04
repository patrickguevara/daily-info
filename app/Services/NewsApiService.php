<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NewsApiService
{
    private ?string $apiKey;
    private string $baseUrl = 'https://newsapi.org/v2';

    public function __construct()
    {
        $this->apiKey = config('services.newsapi.key') ?? env('NEWS_API_KEY');
    }

    public function fetchNews(string $date): array
    {
        try {
            $response = Http::timeout(10)
                ->get("{$this->baseUrl}/everything", [
                    'apiKey' => $this->apiKey,
                    'q' => 'a OR the OR is', // Broad query to get general news
                    'from' => $date,
                    'to' => $date,
                    'sortBy' => 'publishedAt',
                    'pageSize' => 10,
                    'language' => 'en',
                ]);

            if ($response->failed()) {
                Log::error('NewsAPI request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $data = $response->json();

            if (!isset($data['articles'])) {
                return [];
            }

            return collect($data['articles'])->map(function ($article) {
                return [
                    'headline' => $article['title'] ?? '',
                    'description' => $article['description'] ?? null,
                    'url' => $article['url'] ?? '',
                    'source' => $article['source']['name'] ?? 'Unknown',
                    'published_at' => $article['publishedAt'] ?? now()->toIso8601String(),
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error('NewsAPI exception', [
                'message' => $e->getMessage(),
                'date' => $date,
            ]);
            return [];
        }
    }
}
