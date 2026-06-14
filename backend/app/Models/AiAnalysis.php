<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class AiAnalysis extends Model
{
    use HasFactory, HasUuids;

    /**
     * The table associated with the model.
     */
    protected $table = 'ai_analyses';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'type',
        'input_url',
        'input_content',
        'input_keyword',
        'result',
        'tokens_used',
        'model_used',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'result' => 'array',
        'tokens_used' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Analysis types.
     */
    const TYPE_CONTENT_ANALYSIS = 'content_analysis';
    const TYPE_KEYWORD_SUGGESTION = 'keyword_suggestion';
    const TYPE_CONTENT_GENERATION = 'content_generation';
    const TYPE_COMPETITOR_ANALYSIS = 'competitor_analysis';

    /**
     * Get the user that owns the analysis.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for content analyses.
     */
    public function scopeContentAnalyses($query)
    {
        return $query->where('type', self::TYPE_CONTENT_ANALYSIS);
    }

    /**
     * Scope for keyword suggestions.
     */
    public function scopeKeywordSuggestions($query)
    {
        return $query->where('type', self::TYPE_KEYWORD_SUGGESTION);
    }

    /**
     * Scope for this month.
     */
    public function scopeThisMonth($query)
    {
        return $query->where('created_at', '>=', now()->startOfMonth());
    }
}
