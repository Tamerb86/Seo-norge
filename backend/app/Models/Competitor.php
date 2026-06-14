<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Competitor extends Model
{
    use HasFactory, HasUuids;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'domain_id',
        'competitor_domain',
        'common_keywords_count',
        'domain_authority',
        'last_analyzed_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'common_keywords_count' => 'integer',
        'domain_authority' => 'integer',
        'last_analyzed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the domain that owns the competitor.
     */
    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }
}
