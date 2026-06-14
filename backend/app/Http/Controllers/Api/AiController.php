<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class AiController extends Controller
{
    public function __construct(
        private AiService $aiService
    ) {}

    /**
     * Analyze content for SEO optimization.
     */
    public function analyzeContent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'url' => ['nullable', 'url', 'required_without:content'],
            'content' => ['nullable', 'string', 'required_without:url'],
            'keyword' => ['required', 'string', 'max:255'],
        ]);

        $user = $request->user();

        // Check if user can perform AI analysis
        if (!$user->canPerformAiAnalysis()) {
            return response()->json([
                'message' => 'Du har brukt opp AI-analysene for denne måneden. Vennligst oppgrader for å få flere.',
            ], 403);
        }

        // If URL provided, fetch content
        $content = $validated['content'] ?? null;
        $url = $validated['url'] ?? null;

        if ($url && !$content) {
            $content = $this->fetchUrlContent($url);
            if (!$content) {
                return response()->json([
                    'message' => 'Kunne ikke hente innhold fra URL-en. Vennligst prøv igjen eller lim inn innholdet direkte.',
                ], 422);
            }
        }

        try {
            $result = $this->aiService->analyzeContent(
                $user,
                $content,
                $validated['keyword'],
                $url
            );

            return response()->json([
                'data' => $result,
                'message' => 'Analyse fullført.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'En feil oppstod under analysen. Vennligst prøv igjen.',
            ], 500);
        }
    }

    /**
     * Suggest keywords based on seed keyword.
     */
    public function suggestKeywords(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'seed_keyword' => ['required', 'string', 'max:255'],
            'language' => ['nullable', 'in:nb,nn,en'],
            'count' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        $user = $request->user();

        // Check if user can perform AI analysis
        if (!$user->canPerformAiAnalysis()) {
            return response()->json([
                'message' => 'Du har brukt opp AI-analysene for denne måneden. Vennligst oppgrader for å få flere.',
            ], 403);
        }

        try {
            $result = $this->aiService->suggestKeywords(
                $user,
                $validated['seed_keyword'],
                $validated['language'] ?? 'nb',
                $validated['count'] ?? 20
            );

            return response()->json([
                'data' => $result,
                'message' => 'Søkeordforslag generert.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'En feil oppstod under genereringen. Vennligst prøv igjen.',
            ], 500);
        }
    }

    /**
     * Generate SEO-optimized content.
     */
    public function generateContent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'keyword' => ['required', 'string', 'max:255'],
            'content_type' => ['required', 'in:blog_post,product_description,meta_description,title'],
            'language' => ['nullable', 'in:nb,nn,en'],
            'tone' => ['nullable', 'in:professional,casual,friendly'],
        ]);

        $user = $request->user();

        // Check if user can perform AI analysis
        if (!$user->canPerformAiAnalysis()) {
            return response()->json([
                'message' => 'Du har brukt opp AI-analysene for denne måneden. Vennligst oppgrader for å få flere.',
            ], 403);
        }

        try {
            $result = $this->aiService->generateContent(
                $user,
                $validated['keyword'],
                $validated['content_type'],
                $validated['language'] ?? 'nb',
                $validated['tone'] ?? 'professional'
            );

            return response()->json([
                'data' => $result,
                'message' => 'Innhold generert.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'En feil oppstod under genereringen. Vennligst prøv igjen.',
            ], 500);
        }
    }

    /**
     * Generate a topic-cluster content plan (pillars + supporting articles).
     */
    public function clusterPlan(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'business_description' => ['required', 'string', 'max:2000'],
            'language' => ['nullable', 'in:nb,nn,en'],
            'pillars' => ['nullable', 'integer', 'min:1', 'max:8'],
            'articles_per_pillar' => ['nullable', 'integer', 'min:3', 'max:15'],
        ]);

        $user = $request->user();

        if (!$user->canPerformAiAnalysis()) {
            return response()->json([
                'message' => 'Du har brukt opp AI-analysene for denne maneden. Vennligst oppgrader for a fa flere.',
            ], 403);
        }

        try {
            $result = $this->aiService->generateClusterPlan(
                $user,
                $validated['business_description'],
                $validated['language'] ?? 'nb',
                $validated['pillars'] ?? 4,
                $validated['articles_per_pillar'] ?? 10
            );

            return response()->json([
                'data' => $result,
                'message' => 'Innholdsplan generert.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'En feil oppstod under genereringen. Vennligst prov igjen.',
            ], 500);
        }
    }

    /**
     * Run the Norwegian-quality guardrail on a piece of text.
     */
    public function validateNorwegian(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'text' => ['required', 'string'],
            'language' => ['nullable', 'in:nb,nn,en'],
        ]);

        $warnings = $this->aiService->validateNorwegian(
            $validated['text'],
            $validated['language'] ?? 'nb'
        );

        return response()->json([
            'data' => [
                'passed' => count($warnings) === 0,
                'warnings' => $warnings,
            ],
        ]);
    }

    /**
     * Fetch content from URL.
     */
    private function fetchUrlContent(string $url): ?string
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'SEO Norge Bot/1.0',
                ])
                ->get($url);

            if (!$response->successful()) {
                return null;
            }

            $html = $response->body();

            // Extract text content from HTML
            $dom = new \DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

            // Remove script and style elements
            $xpath = new \DOMXPath($dom);
            foreach ($xpath->query('//script|//style|//noscript') as $node) {
                $node->parentNode->removeChild($node);
            }

            // Get text content
            $body = $dom->getElementsByTagName('body')->item(0);
            if ($body) {
                $text = $body->textContent;
                // Clean up whitespace
                $text = preg_replace('/\s+/', ' ', $text);
                return trim($text);
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
