<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\Article;
use App\Services\AiService;
use App\Services\PublishService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ArticleController extends Controller
{
    public function __construct(
        private AiService $aiService,
        private PublishService $publishService
    ) {}

    private function ownsDomain(Request $request, Domain $domain): bool
    {
        return $domain->user_id === $request->user()->id;
    }

    private function articleInDomain(Domain $domain, Article $article): bool
    {
        return $article->domain_id === $domain->id;
    }

    /**
     * List articles for a domain (optionally filtered by status).
     */
    public function index(Request $request, Domain $domain): JsonResponse
    {
        if (!$this->ownsDomain($request, $domain)) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        $query = $domain->articles()->with('cluster:id,pillar_title');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $articles = $query->orderBy('priority')->orderBy('created_at', 'desc')->get();

        return response()->json(['data' => $articles]);
    }

    /**
     * Show one article (full body).
     */
    public function show(Request $request, Domain $domain, Article $article): JsonResponse
    {
        if (!$this->ownsDomain($request, $domain) || !$this->articleInDomain($domain, $article)) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        return response()->json(['data' => $article->load('cluster:id,pillar_title')]);
    }

    /**
     * Generate (or regenerate) the article body via AI and save it.
     */
    public function generate(Request $request, Domain $domain, Article $article): JsonResponse
    {
        if (!$this->ownsDomain($request, $domain) || !$this->articleInDomain($domain, $article)) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        if (!$request->user()->canPerformAiAnalysis()) {
            return response()->json(['message' => 'Du har brukt opp AI-analysene for denne maneden.'], 403);
        }

        $validated = $request->validate([
            'content_type' => ['nullable', 'in:article,blog_post,pillar'],
            'tone' => ['nullable', 'in:professional,casual,friendly'],
        ]);

        $language = $domain->contentPlans()->value('language') ?? 'nb';

        try {
            $result = $this->aiService->generateContent(
                $request->user(),
                $article->keyword,
                $validated['content_type'] ?? 'article',
                $language,
                $validated['tone'] ?? 'professional'
            );
        } catch (\Exception $e) {
            $article->update(['status' => Article::STATUS_FAILED]);
            return response()->json(['message' => 'En feil oppstod under genereringen.'], 500);
        }

        $seo = $result['seo'] ?? [];
        $article->update([
            'h1' => $seo['h1'] ?? $article->h1,
            'title_tag' => $seo['title_tag'] ?? null,
            'meta_description' => $seo['meta_description'] ?? null,
            'slug' => $seo['slug'] ?? $article->slug,
            'secondary_keywords' => $seo['secondary_keywords'] ?? null,
            'body_markdown' => $result['content'] ?? null,
            'faq' => $result['faq'] ?? null,
            'internal_links' => $seo['internal_link_suggestions'] ?? $article->internal_links,
            'image_alts' => $seo['image_alt_suggestions'] ?? null,
            'schema_types' => $seo['schema'] ?? null,
            'warnings' => $result['warnings'] ?? null,
            'word_count' => $result['word_count'] ?? 0,
            'status' => $article->status === Article::STATUS_PUBLISHED ? Article::STATUS_PUBLISHED : Article::STATUS_DRAFT,
        ]);

        return response()->json([
            'data' => $article->fresh(),
            'message' => 'Artikkel generert.',
        ]);
    }

    /**
     * Update editable fields of an article.
     */
    public function update(Request $request, Domain $domain, Article $article): JsonResponse
    {
        if (!$this->ownsDomain($request, $domain) || !$this->articleInDomain($domain, $article)) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        $validated = $request->validate([
            'h1' => ['nullable', 'string', 'max:255'],
            'title_tag' => ['nullable', 'string', 'max:70'],
            'meta_description' => ['nullable', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:255'],
            'body_markdown' => ['nullable', 'string'],
            'keyword' => ['nullable', 'string', 'max:255'],
            'intent' => ['nullable', 'string', 'max:30'],
        ]);

        if (isset($validated['slug'])) {
            $validated['slug'] = $this->aiService->slugify($validated['slug']);
        }
        if (isset($validated['body_markdown'])) {
            $validated['word_count'] = str_word_count(strip_tags($validated['body_markdown']));
            $validated['warnings'] = $this->aiService->validateNorwegian($validated['body_markdown'], $domain->contentPlans()->value('language') ?? 'nb');
        }

        $article->update($validated);

        return response()->json([
            'data' => $article->fresh(),
            'message' => 'Artikkel oppdatert.',
        ]);
    }

    /**
     * Schedule an article for automatic publishing.
     */
    public function schedule(Request $request, Domain $domain, Article $article): JsonResponse
    {
        if (!$this->ownsDomain($request, $domain) || !$this->articleInDomain($domain, $article)) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        $validated = $request->validate([
            'scheduled_for' => ['required', 'date', 'after_or_equal:now'],
        ]);

        if (!$article->isGenerated()) {
            return response()->json(['message' => 'Generer artikkelen for du planlegger publisering.'], 422);
        }

        $article->update([
            'scheduled_for' => $validated['scheduled_for'],
            'status' => Article::STATUS_SCHEDULED,
        ]);

        return response()->json([
            'data' => $article->fresh(),
            'message' => 'Artikkel planlagt.',
        ]);
    }

    /**
     * Publish the article now to the domain's configured platform.
     */
    public function publish(Request $request, Domain $domain, Article $article): JsonResponse
    {
        if (!$this->ownsDomain($request, $domain) || !$this->articleInDomain($domain, $article)) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        if (!$domain->isPublishingConfigured()) {
            return response()->json(['message' => 'Konfigurer en publiseringsmal (Shopify/WordPress) forst.'], 422);
        }

        try {
            $result = $this->publishService->publish($article);
        } catch (\Throwable $e) {
            $article->update([
                'status' => Article::STATUS_FAILED,
                'warnings' => array_values(array_filter([
                    ...(is_array($article->warnings) ? $article->warnings : []),
                    'Publisering feilet: ' . $e->getMessage(),
                ])),
            ]);
            return response()->json(['message' => $e->getMessage()], 502);
        }

        $article->update([
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => now(),
            'published_url' => $result['url'] ?? null,
        ]);

        return response()->json([
            'data' => $article->fresh(),
            'message' => 'Artikkel publisert.',
        ]);
    }

    /**
     * Delete an article.
     */
    public function destroy(Request $request, Domain $domain, Article $article): JsonResponse
    {
        if (!$this->ownsDomain($request, $domain) || !$this->articleInDomain($domain, $article)) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        $article->delete();

        return response()->json(['message' => 'Artikkel slettet.']);
    }
}
