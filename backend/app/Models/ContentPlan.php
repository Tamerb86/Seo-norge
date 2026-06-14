<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ContentPlan extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'domain_id',
        'title',
        'business_description',
        'language',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class);
    }

    public function clusters(): HasMany
    {
        return $this->hasMany(Cluster::class)->orderBy('position');
    }
}
