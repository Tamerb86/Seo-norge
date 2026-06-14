<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    /**
     * Get current user profile.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'company' => $user->company,
                'plan' => $user->plan,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_name' => ['nullable', 'string', 'max:255'],
            'company' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $user->update($validated);

        return response()->json([
            'data' => [
                'id' => $user->id,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'company' => $user->company,
                'plan' => $user->plan,
            ],
            'message' => 'Profil oppdatert.',
        ]);
    }

    /**
     * Get user usage statistics.
     */
    public function usage(Request $request): JsonResponse
    {
        $user = $request->user();
        $limits = $user->getPlanLimits();

        // Calculate current usage
        $domainsUsed = $user->domains()->count();
        $keywordsUsed = $user->domains()->withCount('keywords')->get()->sum('keywords_count');
        $aiAnalysesUsed = $user->aiAnalyses()
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();

        return response()->json([
            'data' => [
                'domains' => [
                    'used' => $domainsUsed,
                    'limit' => $limits['domains'],
                    'percentage' => $limits['domains'] > 0 
                        ? round(($domainsUsed / $limits['domains']) * 100, 1) 
                        : 0,
                ],
                'keywords' => [
                    'used' => $keywordsUsed,
                    'limit' => $limits['keywords'],
                    'percentage' => $limits['keywords'] > 0 
                        ? round(($keywordsUsed / $limits['keywords']) * 100, 1) 
                        : 0,
                ],
                'ai_analyses' => [
                    'used' => $aiAnalysesUsed,
                    'limit' => $limits['ai_analyses'],
                    'percentage' => $limits['ai_analyses'] > 0 
                        ? round(($aiAnalysesUsed / $limits['ai_analyses']) * 100, 1) 
                        : 0,
                ],
                'period_start' => $periodStart->toIso8601String(),
                'period_end' => $periodEnd->toIso8601String(),
            ],
        ]);
    }

    /**
     * Get dashboard overview data.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $user = $request->user();

        // Get domains with latest rankings
        $domains = $user->domains()
            ->withCount('keywords')
            ->with(['keywords' => function ($query) {
                $query->with('latestRanking');
            }])
            ->get();

        // Calculate stats
        $totalKeywords = $domains->sum('keywords_count');
        $keywordsInTop10 = 0;
        $keywordsInTop3 = 0;
        $avgPosition = 0;
        $positionCount = 0;

        foreach ($domains as $domain) {
            foreach ($domain->keywords as $keyword) {
                $position = $keyword->latestRanking?->position;
                if ($position !== null) {
                    $avgPosition += $position;
                    $positionCount++;
                    if ($position <= 10) $keywordsInTop10++;
                    if ($position <= 3) $keywordsInTop3++;
                }
            }
        }

        $avgPosition = $positionCount > 0 ? round($avgPosition / $positionCount, 1) : null;

        // Get recent ranking changes
        $recentChanges = [];
        foreach ($domains as $domain) {
            $rankings = $domain->getLatestRankings();
            foreach ($rankings as $ranking) {
                if ($ranking['change'] != 0) {
                    $recentChanges[] = [
                        'domain' => $domain->domain,
                        'keyword' => $ranking['keyword']->keyword,
                        'change' => $ranking['change'],
                        'current_position' => $ranking['current_position'],
                    ];
                }
            }
        }

        // Sort by absolute change and take top 10
        usort($recentChanges, fn($a, $b) => abs($b['change']) - abs($a['change']));
        $recentChanges = array_slice($recentChanges, 0, 10);

        return response()->json([
            'data' => [
                'stats' => [
                    'total_domains' => $domains->count(),
                    'total_keywords' => $totalKeywords,
                    'keywords_in_top_10' => $keywordsInTop10,
                    'keywords_in_top_3' => $keywordsInTop3,
                    'average_position' => $avgPosition,
                ],
                'recent_changes' => $recentChanges,
                'domains' => $domains->map(fn($d) => [
                    'id' => $d->id,
                    'domain' => $d->domain,
                    'name' => $d->name,
                    'keywords_count' => $d->keywords_count,
                ]),
            ],
        ]);
    }
}
