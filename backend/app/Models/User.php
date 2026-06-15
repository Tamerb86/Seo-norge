<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'email',
        'full_name',
        'company',
        'role',
        'onboarding_completed',
        'onboarding_completed_at',
        'goals',
        'seo_experience',
        'checklist_dismissed',
        'checklist_dismissed_at',
        'ai_analyses_count',
    ];
    // NOTE: `id`, `plan`, and `stripe_*` are deliberately NOT fillable.
    // - id: set explicitly from the Supabase token in SupabaseAuth middleware.
    // - plan / stripe_*: set server-side only (Stripe webhook) via explicit
    //   property assignment, never from request input.

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'onboarding_completed' => 'boolean',
        'onboarding_completed_at' => 'datetime',
        'checklist_dismissed' => 'boolean',
        'checklist_dismissed_at' => 'datetime',
        'goals' => 'array',
        'ai_analyses_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Indicates if the IDs are auto-incrementing.
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     */
    protected $keyType = 'string';

    /**
     * Get the domains for the user.
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    /**
     * Get the AI analyses for the user.
     */
    public function aiAnalyses(): HasMany
    {
        return $this->hasMany(AiAnalysis::class);
    }

    /**
     * Get plan limits based on user's subscription.
     */
    public function getPlanLimits(): array
    {
        $limits = [
            'free' => [
                'domains' => 1,
                'keywords' => 10,
                'ai_analyses' => 10,
            ],
            'starter' => [
                'domains' => 3,
                'keywords' => 100,
                'ai_analyses' => 100,
            ],
            'professional' => [
                'domains' => 10,
                'keywords' => 500,
                'ai_analyses' => 500,
            ],
            'agency' => [
                'domains' => PHP_INT_MAX,
                'keywords' => PHP_INT_MAX,
                'ai_analyses' => PHP_INT_MAX,
            ],
        ];

        return $limits[$this->plan] ?? $limits['free'];
    }

    /**
     * Check if user can add more domains.
     */
    public function canAddDomain(): bool
    {
        $limits = $this->getPlanLimits();
        return $this->domains()->count() < $limits['domains'];
    }

    /**
     * Check if user can add more keywords.
     */
    public function canAddKeyword(): bool
    {
        $limits = $this->getPlanLimits();
        $totalKeywords = $this->domains()->withCount('keywords')->get()->sum('keywords_count');
        return $totalKeywords < $limits['keywords'];
    }

    /**
     * Check if user can perform AI analysis.
     */
    public function canPerformAiAnalysis(): bool
    {
        $limits = $this->getPlanLimits();
        $thisMonth = now()->startOfMonth();
        $analysesThisMonth = $this->aiAnalyses()
            ->where('created_at', '>=', $thisMonth)
            ->count();
        return $analysesThisMonth < $limits['ai_analyses'];
    }
}
