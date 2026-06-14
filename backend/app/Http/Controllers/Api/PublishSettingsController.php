<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Services\PublishService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PublishSettingsController extends Controller
{
    public function __construct(
        private PublishService $publishService
    ) {}

    private function ownsDomain(Request $request, Domain $domain): bool
    {
        return $domain->user_id === $request->user()->id;
    }

    /**
     * Show publishing target for a domain (credentials are redacted).
     */
    public function show(Request $request, Domain $domain): JsonResponse
    {
        if (!$this->ownsDomain($request, $domain)) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        return response()->json([
            'data' => [
                'platform' => $domain->publish_platform,
                'configured' => $domain->isPublishingConfigured(),
                'config' => $this->redact($domain->publish_platform, $domain->publish_config ?? []),
            ],
        ]);
    }

    /**
     * Save publishing target + credentials (stored encrypted via the model cast).
     */
    public function update(Request $request, Domain $domain): JsonResponse
    {
        if (!$this->ownsDomain($request, $domain)) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        $validated = $request->validate([
            'platform' => ['required', 'in:shopify,wordpress'],
            'config' => ['required', 'array'],
            // Shopify
            'config.shop_domain' => ['required_if:platform,shopify', 'string'],
            'config.access_token' => ['required_if:platform,shopify', 'string'],
            'config.blog_id' => ['nullable', 'string'],
            // WordPress
            'config.base_url' => ['required_if:platform,wordpress', 'url'],
            'config.username' => ['required_if:platform,wordpress', 'string'],
            'config.app_password' => ['required_if:platform,wordpress', 'string'],
        ]);

        $domain->update([
            'publish_platform' => $validated['platform'],
            'publish_config' => $validated['config'],
        ]);

        return response()->json([
            'data' => [
                'platform' => $domain->publish_platform,
                'configured' => $domain->isPublishingConfigured(),
                'connection_ok' => $this->publishService->testConnection($domain),
            ],
            'message' => 'Publiseringsinnstillinger lagret.',
        ]);
    }

    /**
     * Test the stored credentials.
     */
    public function test(Request $request, Domain $domain): JsonResponse
    {
        if (!$this->ownsDomain($request, $domain)) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        if (!$domain->isPublishingConfigured()) {
            return response()->json(['message' => 'Ingen konfigurasjon lagret.'], 422);
        }

        return response()->json([
            'data' => ['connection_ok' => $this->publishService->testConnection($domain)],
        ]);
    }

    /**
     * Show autopilot settings for a domain.
     */
    public function showAutopilot(Request $request, Domain $domain): JsonResponse
    {
        if (!$this->ownsDomain($request, $domain)) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        return response()->json(['data' => [
            'autopilot_enabled' => (bool) $domain->autopilot_enabled,
            'autopilot_auto_publish' => (bool) $domain->autopilot_auto_publish,
            'autopilot_daily_limit' => (int) $domain->autopilot_daily_limit,
            'autopilot_tone' => $domain->autopilot_tone,
            'autopilot_last_run_at' => $domain->autopilot_last_run_at,
            'publishing_configured' => $domain->isPublishingConfigured(),
        ]]);
    }

    /**
     * Update autopilot settings (turn the engine on/off, cadence, tone).
     */
    public function updateAutopilot(Request $request, Domain $domain): JsonResponse
    {
        if (!$this->ownsDomain($request, $domain)) {
            return response()->json(['message' => 'Ikke funnet.'], 404);
        }

        $validated = $request->validate([
            'autopilot_enabled' => ['required', 'boolean'],
            'autopilot_auto_publish' => ['nullable', 'boolean'],
            'autopilot_daily_limit' => ['nullable', 'integer', 'min:1', 'max:10'],
            'autopilot_tone' => ['nullable', 'in:professional,casual,friendly'],
        ]);

        // Guard: cannot auto-publish without a configured target.
        if (!empty($validated['autopilot_auto_publish']) && !$domain->isPublishingConfigured()) {
            return response()->json([
                'message' => 'Konfigurer en publiseringsmal for du slar pa automatisk publisering.',
            ], 422);
        }

        $domain->update(array_filter([
            'autopilot_enabled' => $validated['autopilot_enabled'],
            'autopilot_auto_publish' => $validated['autopilot_auto_publish'] ?? false,
            'autopilot_daily_limit' => $validated['autopilot_daily_limit'] ?? $domain->autopilot_daily_limit,
            'autopilot_tone' => $validated['autopilot_tone'] ?? $domain->autopilot_tone,
        ], fn ($v) => $v !== null));

        return response()->json([
            'data' => [
                'autopilot_enabled' => (bool) $domain->autopilot_enabled,
                'autopilot_auto_publish' => (bool) $domain->autopilot_auto_publish,
                'autopilot_daily_limit' => (int) $domain->autopilot_daily_limit,
                'autopilot_tone' => $domain->autopilot_tone,
            ],
            'message' => 'Autopilot oppdatert.',
        ]);
    }

    /**
     * Never return secrets to the client — show only non-sensitive hints.
     */
    private function redact(?string $platform, array $config): array
    {
        if ($platform === 'shopify') {
            return [
                'shop_domain' => $config['shop_domain'] ?? null,
                'blog_id' => $config['blog_id'] ?? null,
                'access_token' => !empty($config['access_token']) ? '••••••' : null,
            ];
        }
        if ($platform === 'wordpress') {
            return [
                'base_url' => $config['base_url'] ?? null,
                'username' => $config['username'] ?? null,
                'app_password' => !empty($config['app_password']) ? '••••••' : null,
            ];
        }
        return [];
    }
}
