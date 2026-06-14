<?php

namespace App\Console\Commands;

use App\Jobs\PublishArticleJob;
use App\Models\Article;
use Illuminate\Console\Command;

class PublishScheduledArticles extends Command
{
    protected $signature = 'seo:publish-scheduled';
    protected $description = 'Publiser artikler som er planlagt og forfalt.';

    public function handle(): int
    {
        $due = Article::where('status', Article::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', now())
            ->get();

        foreach ($due as $article) {
            PublishArticleJob::dispatch($article);
        }

        $this->info("Sendte {$due->count()} artikler til publisering.");
        return self::SUCCESS;
    }
}
