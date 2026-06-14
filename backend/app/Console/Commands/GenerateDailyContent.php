<?php

namespace App\Console\Commands;

use App\Jobs\GenerateArticleJob;
use App\Models\Article;
use App\Models\Domain;
use Illuminate\Console\Command;

class GenerateDailyContent extends Command
{
    protected $signature = 'seo:generate-daily';
    protected $description = 'Generer dagens innhold for nettsteder med autopilot pa.';

    public function handle(): int
    {
        $domains = Domain::where('autopilot_enabled', true)->with('user')->get();
        $dispatched = 0;

        foreach ($domains as $domain) {
            $user = $domain->user;
            if (!$user || !$user->canPerformAiAnalysis()) {
                continue;
            }

            $limit = max(1, (int) ($domain->autopilot_daily_limit ?? 1));

            $drafts = $domain->articles()
                ->where('status', Article::STATUS_DRAFT)
                ->whereNull('body_markdown')
                ->orderBy('priority')
                ->limit($limit)
                ->get();

            foreach ($drafts as $article) {
                GenerateArticleJob::dispatch($article, $domain->autopilot_tone ?? 'professional');
                $dispatched++;
            }

            $domain->forceFill(['autopilot_last_run_at' => now()])->save();
        }

        $this->info("Startet generering av {$dispatched} artikler.");
        return self::SUCCESS;
    }
}
