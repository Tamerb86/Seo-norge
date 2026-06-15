<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\Keyword;
use App\Services\RankTrackerService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class KeywordController extends Controller
{
    public function __construct(
        private RankTrackerService $rankTracker
    ) {}

    /**
     * Display a listing of keywords for a domain.
     */
    public function index(Request $request, Domain $domain): JsonResponse
    {
        // Ensure domain belongs to user
        if ($domain->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        $keywords = $domain->keywords()
            ->with('latestRanking')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $keywords,
        ]);
    }

    /**
     * Store a newly created keyword.
     */
    public function store(Request $request, Domain $domain): JsonResponse
    {
        // Ensure domain belongs to user
        if ($domain->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        $validated = $request->validate([
            'keyword' => ['required', 'string', 'max:255'],
            'language' => ['nullable', 'in:nb,nn,en'],
        ]);

        // Check if user can add more keywords
        if (!$request->user()->canAddKeyword()) {
            return response()->json([
                'message' => 'Du har nådd grensen for antall søkeord på din plan. Vennligst oppgrader for å legge til flere.',
            ], 403);
        }

        // Normalize first, then check existence against the SAME value we store,
        // so "Oslo" and "oslo " don't slip past the check and hit the unique index.
        $normalized = strtolower(trim($validated['keyword']));

        if ($domain->keywords()->where('keyword', $normalized)->exists()) {
            return response()->json([
                'message' => 'Dette søkeordet er allerede lagt til for dette nettstedet.',
            ], 422);
        }

        $keyword = $domain->keywords()->create([
            'keyword' => $normalized,
            'language' => $validated['language'] ?? 'nb',
        ]);

        // Optionally check ranking immediately
        $this->rankTracker->checkRanking($keyword);

        $keyword->load('latestRanking');

        return response()->json([
            'data' => $keyword,
            'message' => 'Søkeord lagt til.',
        ], 201);
    }

    /**
     * Store multiple keywords at once.
     */
    public function bulkStore(Request $request, Domain $domain): JsonResponse
    {
        // Ensure domain belongs to user
        if ($domain->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        $validated = $request->validate([
            'keywords' => ['required', 'array', 'min:1', 'max:100'],
            'keywords.*' => ['required', 'string', 'max:255'],
            'language' => ['nullable', 'in:nb,nn,en'],
        ]);

        $user = $request->user();
        $limits = $user->getPlanLimits();
        $currentCount = $user->domains()->withCount('keywords')->get()->sum('keywords_count');
        $availableSlots = $limits['keywords'] - $currentCount;

        if ($availableSlots <= 0) {
            return response()->json([
                'message' => 'Du har nådd grensen for antall søkeord på din plan.',
            ], 403);
        }

        $keywordsToAdd = array_slice($validated['keywords'], 0, $availableSlots);
        $added = [];
        $skipped = [];

        foreach ($keywordsToAdd as $keywordText) {
            $normalized = strtolower(trim($keywordText));
            
            if ($domain->keywords()->where('keyword', $normalized)->exists()) {
                $skipped[] = $normalized;
                continue;
            }

            $keyword = $domain->keywords()->create([
                'keyword' => $normalized,
                'language' => $validated['language'] ?? 'nb',
            ]);

            $added[] = $keyword;
        }

        return response()->json([
            'data' => $added,
            'message' => count($added) . ' søkeord lagt til.' . 
                (count($skipped) > 0 ? ' ' . count($skipped) . ' ble hoppet over (duplikater).' : ''),
            'meta' => [
                'added' => count($added),
                'skipped' => count($skipped),
                'skipped_keywords' => $skipped,
            ],
        ], 201);
    }

    /**
     * Display the specified keyword.
     */
    public function show(Request $request, Domain $domain, Keyword $keyword): JsonResponse
    {
        // Ensure domain belongs to user and keyword belongs to domain
        if ($domain->user_id !== $request->user()->id || $keyword->domain_id !== $domain->id) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        $keyword->load('latestRanking');

        return response()->json([
            'data' => $keyword,
        ]);
    }

    /**
     * Remove the specified keyword.
     */
    public function destroy(Request $request, Domain $domain, Keyword $keyword): JsonResponse
    {
        // Ensure domain belongs to user and keyword belongs to domain
        if ($domain->user_id !== $request->user()->id || $keyword->domain_id !== $domain->id) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        $keyword->delete();

        return response()->json([
            'message' => 'Søkeord slettet.',
        ]);
    }

    /**
     * Get ranking history for a keyword.
     */
    public function rankings(Request $request, Domain $domain, Keyword $keyword): JsonResponse
    {
        // Ensure domain belongs to user and keyword belongs to domain
        if ($domain->user_id !== $request->user()->id || $keyword->domain_id !== $domain->id) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        $days = $request->input('days', 30);
        $history = $keyword->getRankingHistory($days);

        return response()->json([
            'data' => $history,
            'meta' => [
                'average_position' => $keyword->getAveragePosition($days),
                'best_position' => $keyword->getBestPosition(),
                'in_top_10' => $keyword->isInTopTen(),
                'in_top_3' => $keyword->isInTopThree(),
            ],
        ]);
    }

    /**
     * Refresh ranking for a keyword.
     */
    public function refreshRanking(Request $request, Domain $domain, Keyword $keyword): JsonResponse
    {
        // Ensure domain belongs to user and keyword belongs to domain
        if ($domain->user_id !== $request->user()->id || $keyword->domain_id !== $domain->id) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        $ranking = $this->rankTracker->checkRanking($keyword);

        if (!$ranking) {
            return response()->json([
                'message' => 'Kunne ikke sjekke rangering. Prøv igjen senere.',
            ], 500);
        }

        return response()->json([
            'data' => $ranking,
            'message' => 'Rangering oppdatert.',
        ]);
    }
}
