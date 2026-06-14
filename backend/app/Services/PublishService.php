<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Domain;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * PublishService — pushes a generated article to the domain's configured platform.
 *
 * Supported targets (per-domain, stored encrypted in domains.publish_config):
 *  - Shopify  : ['shop_domain' => 'xxx.myshopify.com', 'access_token' => 'shpat_...', 'blog_id' => optional]
 *  - WordPress: ['base_url' => 'https://site.no', 'username' => '...', 'app_password' => 'xxxx xxxx ...']
 *
 * Returns ['url' => string|null, 'external_id' => string|null] on success; throws RuntimeException on failure.
 */
class PublishService
{
    private const SHOPIFY_API_VERSION = '2024-01';
    private const TIMEOUT = 30;

    public function publish(Article $article): array
    {
        $domain = $article->domain;

        if (!$domain || !$domain->isPublishingConfigured()) {
            throw new RuntimeException('Ingen publiseringsmal er konfigurert for dette nettstedet.');
        }

        if (!$article->isGenerated()) {
            throw new RuntimeException('Artikkelen ma genereres for den kan publiseres.');
        }

        return match ($domain->publish_platform) {
            'shopify'   => $this->publishToShopify($domain, $article),
            'wordpress' => $this->publishToWordPress($domain, $article),
            default     => throw new RuntimeException('Ukjent publiseringsplattform: ' . $domain->publish_platform),
        };
    }

    /**
     * Verify credentials without publishing (used by the settings endpoint).
     */
    public function testConnection(Domain $domain): bool
    {
        try {
            $cfg = $domain->publish_config ?? [];
            if ($domain->publish_platform === 'shopify') {
                $base = $this->shopifyBase($cfg);
                $resp = Http::withHeaders(['X-Shopify-Access-Token' => $cfg['access_token'] ?? ''])
                    ->timeout(self::TIMEOUT)
                    ->get("{$base}/shop.json");
                return $resp->successful();
            }
            if ($domain->publish_platform === 'wordpress') {
                $base = rtrim($cfg['base_url'] ?? '', '/');
                $resp = Http::withBasicAuth($cfg['username'] ?? '', $cfg['app_password'] ?? '')
                    ->timeout(self::TIMEOUT)
                    ->get("{$base}/wp-json/wp/v2/users/me");
                return $resp->successful();
            }
        } catch (\Throwable $e) {
            Log::warning('Publish testConnection failed', ['error' => $e->getMessage()]);
        }
        return false;
    }

    // ---------------------------------------------------------------------
    // Shopify
    // ---------------------------------------------------------------------
    private function publishToShopify(Domain $domain, Article $article): array
    {
        $cfg = $domain->publish_config ?? [];
        $token = $cfg['access_token'] ?? null;
        if (empty($cfg['shop_domain']) || empty($token)) {
            throw new RuntimeException('Shopify-konfigurasjonen mangler shop_domain eller access_token.');
        }

        $base = $this->shopifyBase($cfg);
        $blogId = $cfg['blog_id'] ?? $this->resolveShopifyBlogId($base, $token);

        $payload = ['article' => array_filter([
            'title' => $article->title_tag ?: ($article->h1 ?: $article->keyword),
            'author' => 'Redaksjonen',
            'body_html' => $this->buildBodyHtml($article),
            'handle' => $article->slug,
            'tags' => is_array($article->secondary_keywords) ? implode(', ', $article->secondary_keywords) : null,
            'published' => true,
            'metafields' => $this->shopifySeoMetafields($article),
        ], fn ($v) => $v !== null)];

        $resp = Http::withHeaders(['X-Shopify-Access-Token' => $token])
            ->timeout(self::TIMEOUT)
            ->post("{$base}/blogs/{$blogId}/articles.json", $payload);

        if (!$resp->successful()) {
            throw new RuntimeException('Shopify avviste publiseringen: ' . $resp->status() . ' ' . $resp->body());
        }

        $created = $resp->json('article') ?? [];
        $handle = $created['handle'] ?? $article->slug;
        $url = "https://{$cfg['shop_domain']}/blogs/news/{$handle}";

        return ['url' => $url, 'external_id' => isset($created['id']) ? (string) $created['id'] : null];
    }

    private function shopifyBase(array $cfg): string
    {
        $shop = $cfg['shop_domain'] ?? '';
        return "https://{$shop}/admin/api/" . self::SHOPIFY_API_VERSION;
    }

    private function resolveShopifyBlogId(string $base, string $token): string
    {
        $resp = Http::withHeaders(['X-Shopify-Access-Token' => $token])
            ->timeout(self::TIMEOUT)
            ->get("{$base}/blogs.json");

        if (!$resp->successful()) {
            throw new RuntimeException('Kunne ikke hente Shopify-blogger: ' . $resp->status());
        }

        $blogs = $resp->json('blogs') ?? [];
        if (empty($blogs)) {
            throw new RuntimeException('Ingen blogg funnet i Shopify-butikken. Opprett en blogg forst.');
        }

        return (string) $blogs[0]['id'];
    }

    private function shopifySeoMetafields(Article $article): array
    {
        $fields = [];
        if (!empty($article->title_tag)) {
            $fields[] = ['namespace' => 'global', 'key' => 'title_tag', 'value' => $article->title_tag, 'type' => 'single_line_text_field'];
        }
        if (!empty($article->meta_description)) {
            $fields[] = ['namespace' => 'global', 'key' => 'description_tag', 'value' => $article->meta_description, 'type' => 'single_line_text_field'];
        }
        return $fields;
    }

    // ---------------------------------------------------------------------
    // WordPress
    // ---------------------------------------------------------------------
    private function publishToWordPress(Domain $domain, Article $article): array
    {
        $cfg = $domain->publish_config ?? [];
        if (empty($cfg['base_url']) || empty($cfg['username']) || empty($cfg['app_password'])) {
            throw new RuntimeException('WordPress-konfigurasjonen mangler base_url, username eller app_password.');
        }

        $base = rtrim($cfg['base_url'], '/');
        $payload = [
            'title' => $article->h1 ?: $article->keyword,
            'slug' => $article->slug,
            'content' => $this->buildBodyHtml($article),
            'excerpt' => $article->meta_description ?? '',
            'status' => 'publish',
        ];

        $resp = Http::withBasicAuth($cfg['username'], $cfg['app_password'])
            ->timeout(self::TIMEOUT)
            ->post("{$base}/wp-json/wp/v2/posts", $payload);

        if (!$resp->successful()) {
            throw new RuntimeException('WordPress avviste publiseringen: ' . $resp->status() . ' ' . $resp->body());
        }

        $created = $resp->json() ?? [];
        return [
            'url' => $created['link'] ?? ($base . '/?p=' . ($created['id'] ?? '')),
            'external_id' => isset($created['id']) ? (string) $created['id'] : null,
        ];
    }

    // ---------------------------------------------------------------------
    // Shared
    // ---------------------------------------------------------------------
    /**
     * Convert the article (markdown body + FAQ) into clean publish-ready HTML.
     */
    private function buildBodyHtml(Article $article): string
    {
        $html = Str::markdown($article->body_markdown ?? '');

        $faq = $article->faq ?? [];
        if (!empty($faq) && is_array($faq)) {
            $html .= "\n<h2>Vanlige sporsmal</h2>\n";
            foreach ($faq as $item) {
                $q = trim($item['q'] ?? '');
                $a = trim($item['a'] ?? '');
                if ($q === '' && $a === '') {
                    continue;
                }
                $html .= '<h3>' . e($q) . "</h3>\n<p>" . e($a) . "</p>\n";
            }
        }

        return $html;
    }
}
