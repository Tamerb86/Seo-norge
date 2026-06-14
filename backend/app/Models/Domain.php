<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Domain extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'domain',
        'name',
        'favicon_url',
        'is_verified',
        'publish_platform',
        'publish_config',
        'autopilot_enabled',
        'autopilot_auto_publish',
        'autopilot_daily_limit',
        'autopilot_tone',
        'autopilot_last_run_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_verified' => 'boolean',
        'publish_config' => 'encrypted:array',
        'autopilot_enabled' => 'boolean',
        'autopilot_auto_publish' => 'boolean',
        'autopilot_daily_limit' => 'integer',
        'autopilot_last_run_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the domain.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the keywords for the domain.
     */
    public function keywords(): HasMany
    {
        return $this->hasMany(Keyword::class);
    }

    /**
     * Get the competitors for the domain.
     */
    public function competitors(): HasMany
    {
        return $this->hasMany(Competitor::class);
    }

    /**
     * Content plans (topic-cluster plans) for the domain.
     */
    public function contentPlans(): HasMany
    {
        return $this->hasMany(ContentPlan::class);
    }

    /**
     * Clusters belonging to the domain.
     */
    public function clusters(): HasMany
    {
        return $this->hasMany(Cluster::class);
    }

    /**
     * Articles belonging to the domain.
     */
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * Whether this domain has a publishing target configured.
     */
    public function isPublishingConfigured(): bool
    {
        return !empty($this->publish_platform) && !empty($this->publish_config);
    }

    /**
     * Get keywords count attribute.
     */
    public function getKeywordsCountAttribute(): int
    {
        return $this->keywords()->count();
    }

    /**
     * Get the latest rankings overview.
     */
    public function getLatestRankings()
    {
        return $this->keywords()
            ->with(['latestRanking'])
            ->get()
            ->map(function ($keyword) {
                $latest = $keyword->latestRanking;
                $previous = $keyword->rankings()
                    ->where('checked_at', '<', $latest?->checked_at)
                    ->orderBy('checked_at', 'desc')
                    ->first();

                return [
                    'keyword' => $keyword,
                    'current_position' => $latest?->position,
                    'previous_position' => $previous?->position,
                    'change' => $this->calculateChange($latest?->position, $previous?->position),
                    'url' => $latest?->url,
                ];
            });
    }

    /**
     * Calculate position change.
     */
    private function calculateChange(?int $current, ?int $previous): int
    {
        if ($current === null || $previous === null) {
            return 0;
        }
        return $previous - $current; // Positive = improved, Negative = dropped
    }
}
