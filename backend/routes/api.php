<?php

use App\Http\Controllers\Api\DomainController;
use App\Http\Controllers\Api\KeywordController;
use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Api\ContentPlanController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\PublishSettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| SEO Norge API Routes
| All routes are prefixed with /api
|
*/

// Health check (public)
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String(),
    ]);
});

// Protected routes (require authentication via Supabase JWT)
Route::middleware('supabase')->group(function () {
    
    // User routes
    Route::prefix('user')->group(function () {
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::get('/usage', [UserController::class, 'usage']);
        Route::get('/dashboard', [UserController::class, 'dashboard']);
    });

    // Domain routes
    Route::apiResource('domains', DomainController::class);
    Route::get('/domains/{domain}/rankings/latest', [DomainController::class, 'rankings']);

    // Keyword routes (nested under domains)
    Route::prefix('domains/{domain}')->group(function () {
        Route::get('/keywords', [KeywordController::class, 'index']);
        Route::post('/keywords', [KeywordController::class, 'store']);
        Route::post('/keywords/bulk', [KeywordController::class, 'bulkStore']);
        Route::get('/keywords/{keyword}', [KeywordController::class, 'show']);
        Route::delete('/keywords/{keyword}', [KeywordController::class, 'destroy']);
        Route::get('/keywords/{keyword}/rankings', [KeywordController::class, 'rankings']);
        Route::post('/keywords/{keyword}/refresh', [KeywordController::class, 'refreshRanking']);

        // Content plans (topic clusters)
        Route::get('/content-plans', [ContentPlanController::class, 'index']);
        Route::post('/content-plans', [ContentPlanController::class, 'store']);
        Route::get('/content-plans/{contentPlan}', [ContentPlanController::class, 'show']);
        Route::delete('/content-plans/{contentPlan}', [ContentPlanController::class, 'destroy']);

        // Articles
        Route::get('/articles', [ArticleController::class, 'index']);
        Route::get('/articles/{article}', [ArticleController::class, 'show']);
        Route::post('/articles/{article}/generate', [ArticleController::class, 'generate']);
        Route::put('/articles/{article}', [ArticleController::class, 'update']);
        Route::post('/articles/{article}/schedule', [ArticleController::class, 'schedule']);
        Route::post('/articles/{article}/publish', [ArticleController::class, 'publish']);
        Route::delete('/articles/{article}', [ArticleController::class, 'destroy']);

        // Publishing target (Shopify / WordPress)
        Route::get('/publish-settings', [PublishSettingsController::class, 'show']);
        Route::put('/publish-settings', [PublishSettingsController::class, 'update']);
        Route::post('/publish-settings/test', [PublishSettingsController::class, 'test']);

        // Autopilot
        Route::get('/autopilot', [PublishSettingsController::class, 'showAutopilot']);
        Route::put('/autopilot', [PublishSettingsController::class, 'updateAutopilot']);
    });

    // AI routes
    Route::prefix('ai')->group(function () {
        Route::post('/analyze-content', [AiController::class, 'analyzeContent']);
        Route::post('/suggest-keywords', [AiController::class, 'suggestKeywords']);
        Route::post('/generate-content', [AiController::class, 'generateContent']);
        Route::post('/cluster-plan', [AiController::class, 'clusterPlan']);
        Route::post('/validate-norwegian', [AiController::class, 'validateNorwegian']);
    });

    // Onboarding routes
    Route::prefix('onboarding')->group(function () {
        Route::get('/progress', [OnboardingController::class, 'getProgress']);
        Route::post('/complete', [OnboardingController::class, 'complete']);
        Route::get('/tips', [OnboardingController::class, 'getTips']);
    });
    
    // User onboarding routes
    Route::post('/user/goals', [OnboardingController::class, 'saveGoals']);
    Route::post('/user/dismiss-checklist', [OnboardingController::class, 'dismissChecklist']);
    Route::get('/user/onboarding-progress', [OnboardingController::class, 'getProgress']);

    // Billing routes
    Route::prefix('billing')->group(function () {
        Route::get('/plans', [BillingController::class, 'plans']);
        Route::post('/subscribe', [BillingController::class, 'subscribe']);
        Route::post('/portal', [BillingController::class, 'portal']);
        Route::get('/invoices', [BillingController::class, 'invoices']);
    });
});

// Webhook routes (no auth, verified by signature)
Route::prefix('webhooks')->group(function () {
    Route::post('/stripe', [BillingController::class, 'handleStripeWebhook']);
});
