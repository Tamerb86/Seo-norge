<?php

namespace App\Jobs;

use App\Models\Article;
use App\Services\PublishService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PublishArticleJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $backoff = 120;

    public function __construct(public Article $article) {}

    public function handle(PublishService $publisher): void
    {
        $article = $this->article->fresh();
        if (!$article || $article->status === Article::STATUS_PUBLISHED) {
            return;
        }

        try {
            $result = $publisher->publish($article);
            $article->update([
                'status' => Article::STATUS_PUBLISHED,
                'published_at' => now(),
                'published_url' => $result['url'] ?? null,
            ]);
            Log::info('Article published by autopilot', ['article_id' => $article->id, 'url' => $result['url'] ?? null]);
        } catch (\Throwable $e) {
            $article->update([
                'status' => Article::STATUS_FAILED,
                'warnings' => array_values(array_filter([
                    ...(is_array($article->warnings) ? $article->warnings : []),
                    'Publisering feilet: ' . $e->getMessage(),
                ])),
            ]);
            Log::error('Autopilot publish failed', ['article_id' => $article->id, 'error' => $e->getMessage()]);
        }
    }
}
