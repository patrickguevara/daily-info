<?php

namespace App\Http\Controllers;

use App\Services\DataAggregatorService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private DataAggregatorService $aggregator
    ) {}

    public function index()
    {
        return redirect()->route('dashboard.date', ['date' => now()->format('Y-m-d')]);
    }

    public function show(string $date): Response|\Illuminate\Http\RedirectResponse
    {
        // Validate date
        try {
            $requestedDate = Carbon::parse($date);
        } catch (\Exception $e) {
            return redirect()->route('dashboard')
                ->with('error', 'Invalid date format.');
        }

        $oldestAllowed = now()->subDays(6)->startOfDay();

        if ($requestedDate->lt($oldestAllowed) || $requestedDate->gt(now()->endOfDay())) {
            return redirect()->route('dashboard')
                ->with('error', 'We can only show data from the past week. Showing today\'s data instead.');
        }

        // Aggregate data
        $data = $this->aggregator->aggregateData($date);

        return Inertia::render('Dashboard', [
            'date' => $requestedDate->format('M d, Y'),
            'dateParam' => $date,
            'news' => $data['news'],
            'weather' => $data['weather'],
            'stocks' => $data['stocks'],
            'availableDates' => $this->getAvailableDates(),
            'lastUpdated' => now()->toIso8601String(),
        ]);
    }

    private function getAvailableDates(): array
    {
        $dates = [];

        for ($i = 0; $i < 7; $i++) {
            $date = now()->subDays($i);
            $dates[] = [
                'label' => $date->format('D M d, Y'),
                'value' => $date->format('Y-m-d'),
            ];
        }

        return $dates;
    }
}
