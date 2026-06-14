<?php

namespace App\Jobs;

use App\Models\Article;
use App\Services\AiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateArticleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 120;

    public function __construct(
        public Article $article,
        public string $tone = 'professional'
    ) {}

    public function handle(AiService $ai): void
    {
        $article = $this->article->fresh();
        if (!$article || $article->isGenerated()) {
            return;
        }

        $domain = $article->domain;
        $user = $domain?->user;
        if (!$user || !$user->canPerformAiAnalysis()) {
            Log::info('Autopilot generation skipped (quota/owner)', ['article_id' => $article->id]);
            return;
        }

        $language = $domain->contentPlans()->value('language') ?? 'nb';

        try {
            $result = $ai->generateContent($user, $article->keyword, 'article', $language, $this->tone);
        } catch (\Throwable $e) {
            $article->update(['status' => Article::STATUS_FAILED]);
            Log::error('Autopilot generation failed', ['article_id' => $article->id, 'error' => $e->getMessage()]);
            return;
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
            'status' => Article::STATUS_DRAFT,
        ]);

        // If the domain runs in full autopilot, hand off to publishing immediately.
        if ($domain->autopilot_auto_publish && $domain->isPublishingConfigured()) {
            $article->update(['status' => Article::STATUS_SCHEDULED, 'scheduled_for' => now()]);
            PublishArticleJob::dispatch($article->fresh());
        }

        Log::info('Autopilot generated article', ['article_id' => $article->id]);
    }
}
