<?php

namespace App\Services;

class KeywordMatcherService
{
    private array $majorCities = [
        'New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix',
        'Philadelphia', 'San Antonio', 'San Diego', 'Dallas', 'San Jose',
        'Austin', 'Jacksonville', 'Fort Worth', 'Columbus', 'Charlotte',
        'San Francisco', 'Indianapolis', 'Seattle', 'Denver', 'Washington',
        'Boston', 'El Paso', 'Nashville', 'Detroit', 'Oklahoma City',
        'Portland', 'Las Vegas', 'Memphis', 'Louisville', 'Baltimore',
        'Milwaukee', 'Albuquerque', 'Tucson', 'Fresno', 'Sacramento',
        'Kansas City', 'Mesa', 'Atlanta', 'Omaha', 'Colorado Springs',
        'Raleigh', 'Miami', 'Long Beach', 'Virginia Beach', 'Oakland',
        'Minneapolis', 'Tulsa', 'Tampa', 'Arlington', 'New Orleans',
        // International cities
        'London', 'Paris', 'Tokyo', 'Berlin', 'Sydney', 'Toronto',
        'Mumbai', 'Shanghai', 'Beijing', 'Moscow', 'Dubai', 'Singapore',
        'Hong Kong', 'Seoul', 'Madrid', 'Rome', 'Amsterdam', 'Brussels',
        'Vienna', 'Dublin', 'Zurich', 'Copenhagen', 'Stockholm', 'Oslo',
    ];

    private array $companies = [
        'Apple' => 'AAPL', 'Microsoft' => 'MSFT', 'Google' => 'GOOGL',
        'Alphabet' => 'GOOGL', 'Amazon' => 'AMZN', 'Tesla' => 'TSLA',
        'Meta' => 'META', 'Facebook' => 'META', 'NVIDIA' => 'NVDA',
        'Berkshire Hathaway' => 'BRK.B', 'JPMorgan' => 'JPM',
        'Johnson & Johnson' => 'JNJ', 'Visa' => 'V', 'Walmart' => 'WMT',
        'Procter & Gamble' => 'PG', 'UnitedHealth' => 'UNH',
        'Mastercard' => 'MA', 'Home Depot' => 'HD', 'Chevron' => 'CVX',
        'Pfizer' => 'PFE', 'AbbVie' => 'ABBV', 'Coca-Cola' => 'KO',
        'PepsiCo' => 'PEP', 'Costco' => 'COST', 'Netflix' => 'NFLX',
        'Adobe' => 'ADBE', 'Cisco' => 'CSCO', 'Intel' => 'INTC',
        'Comcast' => 'CMCSA', 'Verizon' => 'VZ', 'AT&T' => 'T',
        'Disney' => 'DIS', 'McDonald\'s' => 'MCD', 'Nike' => 'NKE',
        'Boeing' => 'BA', 'IBM' => 'IBM', 'Salesforce' => 'CRM',
        'Oracle' => 'ORCL', 'PayPal' => 'PYPL', 'Broadcom' => 'AVGO',
        'Texas Instruments' => 'TXN', 'Qualcomm' => 'QCOM',
        'AMD' => 'AMD', 'Starbucks' => 'SBUX', 'Goldman Sachs' => 'GS',
        'Morgan Stanley' => 'MS', 'Bank of America' => 'BAC',
        'Wells Fargo' => 'WFC', 'Citigroup' => 'C', 'American Express' => 'AXP',
    ];

    public function extractLocations(array $newsArticles): array
    {
        $locationCounts = [];

        foreach ($newsArticles as $article) {
            $text = strtolower(
                ($article['headline'] ?? '') . ' ' . ($article['description'] ?? '')
            );

            foreach ($this->majorCities as $city) {
                if (stripos($text, strtolower($city)) !== false) {
                    $locationCounts[$city] = ($locationCounts[$city] ?? 0) + 1;
                }
            }
        }

        // Sort by count descending
        arsort($locationCounts);

        // Get top 3
        $topLocations = array_slice(array_keys($locationCounts), 0, 3);

        // If no locations found, return default
        if (empty($topLocations)) {
            return ['New York'];
        }

        return $topLocations;
    }

    public function extractCompanies(array $newsArticles): array
    {
        $companyCounts = [];

        foreach ($newsArticles as $article) {
            $text = ($article['headline'] ?? '') . ' ' . ($article['description'] ?? '');

            foreach ($this->companies as $company => $ticker) {
                // Case-insensitive search for company name
                if (stripos($text, $company) !== false) {
                    $companyCounts[$ticker] = ($companyCounts[$ticker] ?? 0) + 1;
                }
            }
        }

        // Sort by count descending
        arsort($companyCounts);

        // Get top 5
        $topCompanies = array_slice($companyCounts, 0, 5, true);

        // If no companies found, return S&P 500
        if (empty($topCompanies)) {
            return ['SPY' => 1];
        }

        return $topCompanies;
    }
}
