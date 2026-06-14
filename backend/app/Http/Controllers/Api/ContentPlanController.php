<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\ContentPlan;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ContentPlanController extends Controller
{
    public function __construct(
        private AiService $aiService
    ) {}

    private function ownsDomain(Request $request, Domain $domain): bool
    {
        return $domain->user_id === $request->user()->id;
    }

    /**
     * List content plans for a domain (with clusters + article counts).
     */
    public function index(Request $request, Domain $domain): JsonResponse
    {
        if (!$this->ownsDomain($request, $domain)) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        $plans = $domain->contentPlans()
            ->with(['clusters.articles:id,cluster_id,keyword,intent,priority,status'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['data' => $plans]);
    }

    /**
     * Generate a topic-cluster plan via AI and persist it as draft articles.
     */
    public function store(Request $request, Domain $domain): JsonResponse
    {
        if (!$this->ownsDomain($request, $domain)) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        $validated = $request->validate([
            'business_description' => ['required', 'string', 'max:2000'],
            'language' => ['nullable', 'in:nb,nn,en'],
            'pillars' => ['nullable', 'integer', 'min:1', 'max:8'],
            'articles_per_pillar' => ['nullable', 'integer', 'min:3', 'max:15'],
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        if (!$request->user()->canPerformAiAnalysis()) {
            return response()->json([
                'message' => 'Du har brukt opp AI-analysene for denne maneden. Vennligst oppgrader.',
            ], 403);
        }

        $language = $validated['language'] ?? 'nb';

        try {
            $aiResult = $this->aiService->generateClusterPlan(
                $request->user(),
                $validated['business_description'],
                $language,
                $validated['pillars'] ?? 4,
                $validated['articles_per_pillar'] ?? 10
            );
        } catch (\Exception $e) {
            return response()->json(['message' => 'En feil oppstod under genereringen.'], 500);
        }

        $clustersData = $aiResult['clusters'] ?? [];
        if (empty($clustersData)) {
            return response()->json(['message' => 'Klarte ikke a lage en plan. Prov en tydeligere beskrivelse.'], 422);
        }

        $plan = DB::transaction(function () use ($domain, $validated, $language, $clustersData) {
            $plan = $domain->contentPlans()->create([
                'title' => $validated['title'] ?? 'Innholdsplan',
                'business_description' => $validated['business_description'],
                'language' => $language,
                'status' => 'active',
            ]);

            foreach ($clustersData as $ci => $clusterData) {
                $cluster = $plan->clusters()->create([
                    'domain_id' => $domain->id,
                    'pillar_title' => $clusterData['pillar_title'] ?? ('Pilar ' . ($ci + 1)),
                    'pillar_keyword' => $clusterData['pillar_keyword'] ?? null,
                    'position' => $ci,
                ]);

                foreach (($clusterData['articles'] ?? []) as $ai => $articleData) {
                    $domain->articles()->create([
                        'cluster_id' => $cluster->id,
                        'keyword' => $articleData['keyword'] ?? ($articleData['h1'] ?? 'artikkel'),
                        'intent' => $articleData['intent'] ?? null,
                        'priority' => $articleData['priority'] ?? ($ai + 1),
                        'h1' => $articleData['h1'] ?? null,
                        'slug' => $this->aiService->slugify($articleData['h1'] ?? ($articleData['keyword'] ?? 'artikkel')),
                        'internal_links' => $articleData['internal_links'] ?? null,
                        'status' => 'draft',
                    ]);
                }
            }

            return $plan;
        });

        $plan->load(['clusters.articles']);

        return response()->json([
            'data' => $plan,
            'message' => 'Innholdsplan opprettet.',
        ], 201);
    }

    /**
     * Show one plan.
     */
    public function show(Request $request, Domain $domain, ContentPlan $contentPlan): JsonResponse
    {
        if (!$this->ownsDomain($request, $domain) || $contentPlan->domain_id !== $domain->id) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        $contentPlan->load(['clusters.articles']);

        return response()->json(['data' => $contentPlan]);
    }

    /**
     * Delete a plan (cascades clusters; articles' cluster_id set null).
     */
    public function destroy(Request $request, Domain $domain, ContentPlan $contentPlan): JsonResponse
    {
        if (!$this->ownsDomain($request, $domain) || $contentPlan->domain_id !== $domain->id) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        $contentPlan->delete();

        return response()->json(['message' => 'Plan slettet.']);
    }
}
