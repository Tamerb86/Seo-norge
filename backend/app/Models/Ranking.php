<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Ranking extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'keyword_id',
        'position',
        'url',
        'title',
        'description',
        'featured_snippet',
        'checked_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'position' => 'integer',
        'featured_snippet' => 'boolean',
        'checked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = true;

    /**
     * Get the keyword that owns the ranking.
     */
    public function keyword(): BelongsTo
    {
        return $this->belongsTo(Keyword::class);
    }

    /**
     * Scope for rankings within a date range.
     */
    public function scopeWithinDays($query, int $days)
    {
        return $query->where('checked_at', '>=', now()->subDays($days));
    }

    /**
     * Scope for top 10 rankings.
     */
    public function scopeTopTen($query)
    {
        return $query->whereNotNull('position')->where('position', '<=', 10);
    }

    /**
     * Scope for top 3 rankings.
     */
    public function scopeTopThree($query)
    {
        return $query->whereNotNull('position')->where('position', '<=', 3);
    }

    /**
     * Check if this is a new ranking (first time tracked).
     */
    public function isNew(): bool
    {
        return $this->keyword->rankings()->count() === 1;
    }

    /**
     * Get the change from previous ranking.
     */
    public function getChangeAttribute(): int
    {
        $previous = $this->keyword->rankings()
            ->where('checked_at', '<', $this->checked_at)
            ->orderBy('checked_at', 'desc')
            ->first();

        if (!$previous || $previous->position === null || $this->position === null) {
            return 0;
        }

        return $previous->position - $this->position;
    }
}
