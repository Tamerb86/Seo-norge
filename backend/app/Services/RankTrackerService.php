<?php

namespace App\Services;

use App\Models\Keyword;
use App\Models\Ranking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RankTrackerService
{
    private string $apiKey;
    private string $provider;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('services.scraping.api_key');
        $this->provider = config('services.scraping.provider', 'scrapingrobot');
        
        $this->baseUrl = match($this->provider) {
            'serpapi' => 'https://serpapi.com/search',
            'scrapingrobot' => 'https://api.scrapingrobot.com',
            'brightdata' => 'https://api.brightdata.com/serp',
            default => 'https://api.scrapingrobot.com',
        };
    }

    /**
     * Check ranking for a keyword on Google.no
     */
    public function checkRanking(Keyword $keyword): ?Ranking
    {
        $domain = $keyword->domain->domain;
        $searchQuery = $keyword->keyword;

        try {
            $results = $this->searchGoogle($searchQuery, 'no', 'nb');
            
            $position = null;
            $url = null;
            $title = null;
            $description = null;
            $featuredSnippet = false;

            // Search through results to find our domain
            foreach ($results as $index => $result) {
                if ($this->domainMatches($result['url'] ?? '', $domain)) {
                    $position = $index + 1;
                    $url = $result['url'] ?? null;
                    $title = $result['title'] ?? null;
                    $description = $result['description'] ?? null;
                    $featuredSnippet = $result['featured_snippet'] ?? false;
                    break;
                }
            }

            // Create ranking record
            return Ranking::create([
                'keyword_id' => $keyword->id,
                'position' => $position,
                'url' => $url,
                'title' => $title,
                'description' => $description,
                'featured_snippet' => $featuredSnippet,
                'checked_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Rank tracking failed', [
                'keyword_id' => $keyword->id,
                'keyword' => $searchQuery,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    /**
     * Search Google and return organic results
     */
    private function searchGoogle(string $query, string $country = 'no', string $language = 'nb'): array
    {
        // Cache key for this search
        $cacheKey = "serp:{$country}:{$language}:" . md5($query);
        
        // Check cache first (cache for 1 hour)
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $results = match($this->provider) {
            'serpapi' => $this->searchViaSerpApi($query, $country, $language),
            'scrapingrobot' => $this->searchViaScrapingRobot($query, $country, $language),
            'brightdata' => $this->searchViaBrightData($query, $country, $language),
            default => $this->searchViaScrapingRobot($query, $country, $language),
        };

        // Cache results
        Cache::put($cacheKey, $results, now()->addHour());

        return $results;
    }

    /**
     * Search via SerpAPI
     */
    private function searchViaSerpApi(string $query, string $country, string $language): array
    {
        $response = Http::get($this->baseUrl, [
            'api_key' => $this->apiKey,
            'q' => $query,
            'google_domain' => 'google.no',
            'gl' => $country,
            'hl' => $language,
            'num' => 100,
        ]);

        if (!$response->successful()) {
            throw new \Exception('SerpAPI request failed: ' . $response->body());
        }

        $data = $response->json();
        $results = [];

        // Parse organic results
        foreach ($data['organic_results'] ?? [] as $result) {
            $results[] = [
                'url' => $result['link'] ?? null,
                'title' => $result['title'] ?? null,
                'description' => $result['snippet'] ?? null,
                'featured_snippet' => false,
            ];
        }

        // Check for featured snippet
        if (isset($data['answer_box'])) {
            array_unshift($results, [
                'url' => $data['answer_box']['link'] ?? null,
                'title' => $data['answer_box']['title'] ?? null,
                'description' => $data['answer_box']['snippet'] ?? null,
                'featured_snippet' => true,
            ]);
        }

        return $results;
    }

    /**
     * Search via ScrapingRobot
     */
    private function searchViaScrapingRobot(string $query, string $country, string $language): array
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
        ])->post($this->baseUrl . '/google/search', [
            'query' => $query,
            'country' => $country,
            'language' => $language,
            'num_results' => 100,
        ]);

        if (!$response->successful()) {
            throw new \Exception('ScrapingRobot request failed: ' . $response->body());
        }

        $data = $response->json();
        $results = [];

        foreach ($data['organic_results'] ?? $data['results'] ?? [] as $result) {
            $results[] = [
                'url' => $result['url'] ?? $result['link'] ?? null,
                'title' => $result['title'] ?? null,
                'description' => $result['description'] ?? $result['snippet'] ?? null,
                'featured_snippet' => $result['featured_snippet'] ?? false,
            ];
        }

        return $results;
    }

    /**
     * Search via Bright Data
     */
    private function searchViaBrightData(string $query, string $country, string $language): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->post($this->baseUrl, [
            'query' => $query,
            'country' => $country,
            'language' => $language,
            'pages' => 10,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Bright Data request failed: ' . $response->body());
        }

        $data = $response->json();
        $results = [];

        foreach ($data['organic'] ?? [] as $result) {
            $results[] = [
                'url' => $result['url'] ?? null,
                'title' => $result['title'] ?? null,
                'description' => $result['description'] ?? null,
                'featured_snippet' => $result['featured_snippet'] ?? false,
            ];
        }

        return $results;
    }

    /**
     * Check if URL matches domain
     */
    private function domainMatches(string $url, string $domain): bool
    {
        if (empty($url)) {
            return false;
        }

        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? '';

        // Remove www. prefix for comparison
        $host = preg_replace('/^www\./', '', $host);
        $domain = preg_replace('/^www\./', '', $domain);

        return $host === $domain || str_ends_with($host, '.' . $domain);
    }

    /**
     * Bulk check rankings for multiple keywords
     */
    public function bulkCheckRankings(array $keywords): array
    {
        $results = [];

        foreach ($keywords as $keyword) {
            $results[$keyword->id] = $this->checkRanking($keyword);
            
            // Add delay to avoid rate limiting
            usleep(500000); // 0.5 seconds
        }

        return $results;
    }

    /**
     * Get SERP features for a keyword
     */
    public function getSerpFeatures(string $query, string $country = 'no'): array
    {
        try {
            $results = $this->searchGoogle($query, $country);
            
            return [
                'has_featured_snippet' => collect($results)->contains('featured_snippet', true),
                'total_results' => count($results),
                'top_domains' => collect($results)
                    ->take(10)
                    ->pluck('url')
                    ->map(fn($url) => parse_url($url, PHP_URL_HOST))
                    ->filter()
                    ->values()
                    ->toArray(),
            ];
        } catch (\Exception $e) {
            Log::error('SERP features fetch failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            
            return [];
        }
    }
}
