<?php

namespace App\Jobs;

use App\Models\Keyword;
use App\Services\RankTrackerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckKeywordRanking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Keyword $keyword
    ) {}

    /**
     * Execute the job.
     */
    public function handle(RankTrackerService $rankTracker): void
    {
        try {
            $ranking = $rankTracker->checkRanking($this->keyword);

            if ($ranking) {
                Log::info('Ranking checked successfully', [
                    'keyword_id' => $this->keyword->id,
                    'keyword' => $this->keyword->keyword,
                    'position' => $ranking->position,
                ]);

                // Check for significant changes and notify if needed
                $this->checkForSignificantChanges($ranking);
            }
        } catch (\Exception $e) {
            Log::error('Failed to check ranking', [
                'keyword_id' => $this->keyword->id,
                'keyword' => $this->keyword->keyword,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Check for significant ranking changes.
     */
    private function checkForSignificantChanges($ranking): void
    {
        $change = $ranking->change;

        // Notify on significant changes (more than 5 positions)
        if (abs($change) >= 5) {
            // Dispatch notification job
            // NotifyRankingChange::dispatch($this->keyword, $ranking, $change);
            
            Log::info('Significant ranking change detected', [
                'keyword_id' => $this->keyword->id,
                'keyword' => $this->keyword->keyword,
                'change' => $change,
                'new_position' => $ranking->position,
            ]);
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Ranking check job failed permanently', [
            'keyword_id' => $this->keyword->id,
            'keyword' => $this->keyword->keyword,
            'error' => $exception->getMessage(),
        ]);
    }
}
