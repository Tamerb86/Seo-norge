<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Keyword extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'domain_id',
        'keyword',
        'search_volume',
        'difficulty',
        'cpc',
        'search_intent',
        'language',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'search_volume' => 'integer',
        'difficulty' => 'integer',
        'cpc' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the domain that owns the keyword.
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    /**
     * Get all rankings for the keyword.
     */
    public function rankings(): HasMany
    {
        return $this->hasMany(Ranking::class)->orderBy('checked_at', 'desc');
    }

    /**
     * Get the latest ranking for the keyword.
     */
    public function latestRanking(): HasOne
    {
        return $this->hasOne(Ranking::class)->latestOfMany('checked_at');
    }

    /**
     * Get ranking history for a specific number of days.
     */
    public function getRankingHistory(int $days = 30)
    {
        return $this->rankings()
            ->where('checked_at', '>=', now()->subDays($days))
            ->orderBy('checked_at', 'asc')
            ->get();
    }

    /**
     * Get the average position over a period.
     */
    public function getAveragePosition(int $days = 30): ?float
    {
        return $this->rankings()
            ->where('checked_at', '>=', now()->subDays($days))
            ->whereNotNull('position')
            ->avg('position');
    }

    /**
     * Get the best position ever.
     */
    public function getBestPosition(): ?int
    {
        return $this->rankings()
            ->whereNotNull('position')
            ->min('position');
    }

    /**
     * Check if keyword is ranking in top 10.
     */
    public function isInTopTen(): bool
    {
        $latest = $this->latestRanking;
        return $latest && $latest->position && $latest->position <= 10;
    }

    /**
     * Check if keyword is ranking in top 3.
     */
    public function isInTopThree(): bool
    {
        $latest = $this->latestRanking;
        return $latest && $latest->position && $latest->position <= 3;
    }
}
