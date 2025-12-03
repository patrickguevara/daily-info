<?php

namespace App\Services;

use App\Models\News;
use App\Models\Weather;
use App\Models\Stock;
use App\Models\NewsRelatedData;
use Illuminate\Support\Facades\Log;

class DataAggregatorService
{
    public function __construct(
        private NewsApiService $newsService,
        private OpenWeatherMapService $weatherService,
        private TiingoService $stockService,
        private KeywordMatcherService $keywordMatcher,
    ) {}

    public function aggregateData(string $date): array
    {
        // Check if we already have data for this date
        $existingNews = News::forDate($date)->get();

        if ($existingNews->isNotEmpty()) {
            return $this->getCachedData($date);
        }

        // Fetch news
        $newsArticles = $this->newsService->fetchNews($date);

        if (empty($newsArticles)) {
            Log::warning('No news articles fetched', ['date' => $date]);
            return [
                'news' => [],
                'weather' => [],
                'stocks' => [],
            ];
        }

        // Extract keywords
        $locations = $this->keywordMatcher->extractLocations($newsArticles);
        $companyTickers = array_keys($this->keywordMatcher->extractCompanies($newsArticles));

        // Fetch weather and stocks in parallel (simulate with collection)
        $weatherData = $this->weatherService->fetchMultipleWeather($locations);
        $stockData = $this->stockService->fetchStocks($companyTickers);

        // Store everything in database
        $this->storeData($date, $newsArticles, $weatherData, $stockData);

        return [
            'news' => News::forDate($date)->limit(5)->get()->toArray(),
            'weather' => Weather::forDate($date)->get()->toArray(),
            'stocks' => Stock::forDate($date)->get()->toArray(),
        ];
    }

    private function getCachedData(string $date): array
    {
        return [
            'news' => News::forDate($date)->limit(5)->get()->toArray(),
            'weather' => Weather::forDate($date)->get()->toArray(),
            'stocks' => Stock::forDate($date)->get()->toArray(),
        ];
    }

    private function storeData(string $date, array $newsArticles, array $weatherData, array $stockData): void
    {
        // Store news
        $newsRecords = [];
        foreach ($newsArticles as $article) {
            $newsRecords[] = News::create([
                'headline' => $article['headline'],
                'description' => $article['description'],
                'url' => $article['url'],
                'source' => $article['source'],
                'published_at' => $article['published_at'],
                'fetched_for_date' => $date,
            ]);
        }

        // Store weather
        $weatherRecords = [];
        foreach ($weatherData as $weather) {
            $weatherRecords[] = Weather::create([
                'location' => $weather['location'],
                'temperature' => $weather['temperature'],
                'description' => $weather['description'],
                'fetched_for_date' => $date,
            ]);
        }

        // Store stocks
        $stockRecords = [];
        foreach ($stockData as $stock) {
            $stockRecords[] = Stock::create([
                'company_name' => $stock['company_name'],
                'ticker_symbol' => $stock['ticker_symbol'],
                'price' => $stock['price'],
                'fetched_for_date' => $date,
            ]);
        }

        // Create relationships (simplified: just link first news with all data)
        if (!empty($newsRecords) && (!empty($weatherRecords) || !empty($stockRecords))) {
            foreach ($weatherRecords as $weather) {
                NewsRelatedData::create([
                    'news_id' => $newsRecords[0]->id,
                    'weather_id' => $weather->id,
                ]);
            }

            foreach ($stockRecords as $stock) {
                NewsRelatedData::create([
                    'news_id' => $newsRecords[0]->id,
                    'stock_id' => $stock->id,
                ]);
            }
        }
    }
}
