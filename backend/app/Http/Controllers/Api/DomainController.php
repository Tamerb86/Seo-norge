<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class DomainController extends Controller
{
    /**
     * Display a listing of the user's domains.
     */
    public function index(Request $request): JsonResponse
    {
        $domains = $request->user()
            ->domains()
            ->withCount('keywords')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $domains,
        ]);
    }

    /**
     * Store a newly created domain.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'domain' => ['required', 'string', 'max:255'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        // Check if user can add more domains
        if (!$request->user()->canAddDomain()) {
            return response()->json([
                'message' => 'Du har nådd grensen for antall nettsteder på din plan. Vennligst oppgrader for å legge til flere.',
            ], 403);
        }

        // Normalize domain
        $domain = $this->normalizeDomain($validated['domain']);

        // Check if domain already exists for this user
        if ($request->user()->domains()->where('domain', $domain)->exists()) {
            throw ValidationException::withMessages([
                'domain' => ['Dette nettstedet er allerede lagt til.'],
            ]);
        }

        $newDomain = $request->user()->domains()->create([
            'domain' => $domain,
            'name' => $validated['name'] ?? $domain,
        ]);

        return response()->json([
            'data' => $newDomain,
            'message' => 'Nettsted lagt til.',
        ], 201);
    }

    /**
     * Display the specified domain.
     */
    public function show(Request $request, Domain $domain): JsonResponse
    {
        // Ensure domain belongs to user
        if ($domain->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        $domain->loadCount('keywords');
        $domain->load(['keywords.latestRanking']);

        return response()->json([
            'data' => $domain,
        ]);
    }

    /**
     * Update the specified domain.
     */
    public function update(Request $request, Domain $domain): JsonResponse
    {
        // Ensure domain belongs to user
        if ($domain->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $domain->update($validated);

        return response()->json([
            'data' => $domain,
            'message' => 'Nettsted oppdatert.',
        ]);
    }

    /**
     * Remove the specified domain.
     */
    public function destroy(Request $request, Domain $domain): JsonResponse
    {
        // Ensure domain belongs to user
        if ($domain->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        $domain->delete();

        return response()->json([
            'message' => 'Nettsted slettet.',
        ]);
    }

    /**
     * Get latest rankings for a domain.
     */
    public function rankings(Request $request, Domain $domain): JsonResponse
    {
        // Ensure domain belongs to user
        if ($domain->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        $rankings = $domain->getLatestRankings();

        return response()->json([
            'data' => $rankings,
        ]);
    }

    /**
     * Normalize domain name.
     */
    private function normalizeDomain(string $domain): string
    {
        // Remove protocol
        $domain = preg_replace('#^https?://#', '', $domain);
        
        // Remove www.
        $domain = preg_replace('#^www\.#', '', $domain);
        
        // Remove trailing slash and path
        $domain = preg_replace('#/.*$#', '', $domain);
        
        // Convert to lowercase
        $domain = strtolower(trim($domain));

        return $domain;
    }
}
