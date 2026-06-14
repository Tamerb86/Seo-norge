<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Article extends Model
{
    use HasFactory, HasUuids;

    const STATUS_DRAFT = 'draft';
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_PUBLISHED = 'published';
    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'domain_id',
        'cluster_id',
        'keyword',
        'intent',
        'priority',
        'h1',
        'title_tag',
        'meta_description',
        'slug',
        'secondary_keywords',
        'body_markdown',
        'faq',
        'internal_links',
        'image_alts',
        'schema_types',
        'warnings',
        'word_count',
        'status',
        'scheduled_for',
        'published_at',
        'published_url',
    ];

    protected $casts = [
        'secondary_keywords' => 'array',
        'faq' => 'array',
        'internal_links' => 'array',
        'image_alts' => 'array',
        'schema_types' => 'array',
        'warnings' => 'array',
        'priority' => 'integer',
        'word_count' => 'integer',
        'scheduled_for' => 'datetime',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function cluster(): BelongsTo
    {
        return $this->belongsTo(Cluster::class);
    }

    public function isGenerated(): bool
    {
        return !empty($this->body_markdown);
    }
}
