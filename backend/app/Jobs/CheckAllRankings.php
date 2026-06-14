<?php

namespace App\Jobs;

use App\Models\Keyword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckAllRankings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting daily ranking check for all keywords');

        // Get all keywords that need checking
        $keywords = Keyword::with('domain.user')
            ->whereHas('domain.user', function ($query) {
                // Only check for users with active plans
                $query->whereIn('plan', ['free', 'starter', 'professional', 'agency']);
            })
            ->get();

        $count = 0;
        foreach ($keywords as $keyword) {
            // Dispatch individual ranking check with delay to avoid rate limiting
            CheckKeywordRanking::dispatch($keyword)
                ->delay(now()->addSeconds($count * 2)); // 2 seconds between each

            $count++;
        }

        Log::info('Dispatched ranking checks', ['count' => $count]);
    }
}
