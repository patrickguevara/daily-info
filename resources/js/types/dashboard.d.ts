export interface DashboardData {
    date: string; // "Dec 03, 2025"
    dateParam: string; // "2025-12-03"
    news: NewsArticle[];
    weather: WeatherData[];
    stocks: StockData[];
    availableDates: DateOption[];
    lastUpdated: string;
}

export interface NewsArticle {
    id: number;
    headline: string;
    description?: string;
    url: string;
    source: string;
    published_at: string;
}

export interface WeatherData {
    id: number;
    location: string;
    temperature: number;
    description: string;
}

export interface StockData {
    id: number;
    company_name: string;
    ticker_symbol: string;
    price: number;
}

export interface DateOption {
    label: string; // "Mon Dec 03, 2025"
    value: string; // "2025-12-03"
}
