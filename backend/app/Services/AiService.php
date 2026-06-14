<?php

namespace App\Services;

use App\Models\User;
use App\Models\AiAnalysis;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Log;

/**
 * AiService — "the content brain".
 *
 * Upgraded with the norsk-seo-autopilot logic:
 *  - Native Norwegian (bokmål) quality with guardrails (no særskriving, æøå intact, "du"-form)
 *  - Answer-first / GEO structure (gets cited by Google AI Overviews, ChatGPT, Gemini, Perplexity)
 *  - Topic-cluster planning (pillar + supporting articles)
 *  - E-E-A-T discipline (named author, real experience, zero fabricated stats)
 *  - Structured output incl. a ready-to-publish technical SEO block
 *
 * Model is configurable (services.openai.model). Defaults to a cheap, capable model
 * for cost control; you can pass a stronger model for pillar pages if desired.
 */
class AiService
{
    private string $model;

    public function __construct()
    {
        // Cheap + capable default; override via OPENAI_MODEL in .env
        $this->model = config('services.openai.model', 'gpt-4o-mini');
    }

    // =====================================================================
    // 1) CONTENT ANALYSIS
    // =====================================================================
    public function analyzeContent(User $user, string $content, string $keyword, ?string $url = null): array
    {
        $response = OpenAI::chat()->create([
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $this->getSystemPrompt('content_analysis')],
                ['role' => 'user',   'content' => $this->buildContentAnalysisPrompt($content, $keyword, $url)],
            ],
            'temperature' => 0.3,
            'max_tokens' => 2000,
        ]);

        $result = $this->parseJsonObject($response->choices[0]->message->content) ?: $this->getDefaultAnalysisResult();

        $this->log($user, AiAnalysis::TYPE_CONTENT_ANALYSIS, $result, $response->usage->totalTokens ?? 0, [
            'input_url' => $url,
            'input_content' => substr($content, 0, 5000),
            'input_keyword' => $keyword,
        ]);

        return $result;
    }

    // =====================================================================
    // 2) KEYWORD SUGGESTIONS
    // =====================================================================
    public function suggestKeywords(User $user, string $seedKeyword, string $language = 'nb', int $count = 20): array
    {
        $response = OpenAI::chat()->create([
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $this->getSystemPrompt('keyword_suggestion')],
                ['role' => 'user',   'content' => $this->buildKeywordSuggestionPrompt($seedKeyword, $language, $count)],
            ],
            'temperature' => 0.7,
            'max_tokens' => 1800,
        ]);

        $result = $this->parseJsonArray($response->choices[0]->message->content);

        $this->log($user, AiAnalysis::TYPE_KEYWORD_SUGGESTION, $result, $response->usage->totalTokens ?? 0, [
            'input_keyword' => $seedKeyword,
        ]);

        return $result;
    }

    // =====================================================================
    // 3) CONTENT GENERATION  (the core upgrade)
    // =====================================================================
    public function generateContent(
        User $user,
        string $keyword,
        string $contentType,
        string $language = 'nb',
        string $tone = 'professional'
    ): array {
        // Full articles get the rich, structured, GEO-optimized treatment.
        if (in_array($contentType, ['blog_post', 'article', 'pillar'])) {
            return $this->generateArticle($user, $keyword, $contentType, $language, $tone);
        }

        // Short assets (meta, title, product description) stay lightweight.
        $maxTokens = match ($contentType) {
            'product_description' => 600,
            'meta_description'    => 200,
            'title'               => 120,
            default               => 1000,
        };

        $response = OpenAI::chat()->create([
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $this->getSystemPrompt('content_generation')],
                ['role' => 'user',   'content' => $this->buildShortAssetPrompt($keyword, $contentType, $language, $tone)],
            ],
            'temperature' => 0.7,
            'max_tokens' => $maxTokens,
        ]);

        $content = trim($response->choices[0]->message->content);
        $result = [
            'content' => $content,
            'word_count' => str_word_count($content),
            'estimated_reading_time' => max(1, (int) ceil(str_word_count($content) / 200)),
            'warnings' => $this->validateNorwegian($content, $language),
        ];

        $this->log($user, AiAnalysis::TYPE_CONTENT_GENERATION, $result, $response->usage->totalTokens ?? 0, [
            'input_keyword' => $keyword,
        ]);

        return $result;
    }

    /**
     * Generate a full, publish-ready article with technical SEO block + quality warnings.
     */
    private function generateArticle(User $user, string $keyword, string $contentType, string $language, string $tone): array
    {
        $isPillar = $contentType === 'pillar';
        $response = OpenAI::chat()->create([
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $this->getSystemPrompt('article')],
                ['role' => 'user',   'content' => $this->buildArticlePrompt($keyword, $language, $tone, $isPillar)],
            ],
            'temperature' => 0.75,
            'max_tokens' => $isPillar ? 6000 : 3500,
        ]);

        $raw = $response->choices[0]->message->content;
        $data = $this->parseJsonObject($raw);

        // Fallback: if the model returned prose instead of JSON, wrap it.
        if (!$data || empty($data['body_markdown'])) {
            $body = trim($raw);
            $data = [
                'h1' => $keyword,
                'title_tag' => mb_substr($keyword, 0, 60),
                'meta_description' => '',
                'slug' => $this->slugify($keyword),
                'primary_keyword' => $keyword,
                'secondary_keywords' => [],
                'body_markdown' => $body,
                'faq' => [],
                'internal_link_suggestions' => [],
                'image_alt_suggestions' => [],
                'cluster' => null,
            ];
        }

        // Always derive/validate the slug ourselves (æ/ø/å safe).
        $data['slug'] = $this->slugify($data['slug'] ?? ($data['h1'] ?? $keyword));

        $body = $data['body_markdown'] ?? '';
        $result = [
            'content' => $body,                         // backward-compatible key
            'word_count' => str_word_count(strip_tags($body)),
            'estimated_reading_time' => max(1, (int) ceil(str_word_count(strip_tags($body)) / 200)),
            'seo' => [
                'h1' => $data['h1'] ?? $keyword,
                'title_tag' => $data['title_tag'] ?? '',
                'meta_description' => $data['meta_description'] ?? '',
                'slug' => $data['slug'],
                'primary_keyword' => $data['primary_keyword'] ?? $keyword,
                'secondary_keywords' => $data['secondary_keywords'] ?? [],
                'internal_link_suggestions' => $data['internal_link_suggestions'] ?? [],
                'image_alt_suggestions' => $data['image_alt_suggestions'] ?? [],
                'schema' => $isPillar ? ['Article', 'FAQPage', 'BreadcrumbList'] : ['Article', 'FAQPage'],
                'cluster' => $data['cluster'] ?? null,
            ],
            'faq' => $data['faq'] ?? [],
            'warnings' => $this->validateNorwegian($body, $language),
        ];

        $this->log($user, AiAnalysis::TYPE_CONTENT_GENERATION, $result, $response->usage->totalTokens ?? 0, [
            'input_keyword' => $keyword,
        ]);

        return $result;
    }

    // =====================================================================
    // 4) TOPIC-CLUSTER PLAN  (new — Phase 2 seed + serves the "content map" use case)
    // =====================================================================
    /**
     * Build a topic-cluster content map: several pillars, each with supporting
     * articles, every article tagged with keyword + intent + H1 + internal links,
     * prioritized low-competition / high buyer-intent first.
     */
    public function generateClusterPlan(
        User $user,
        string $businessDescription,
        string $language = 'nb',
        int $pillars = 4,
        int $articlesPerPillar = 10
    ): array {
        $response = OpenAI::chat()->create([
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $this->getSystemPrompt('cluster_plan')],
                ['role' => 'user',   'content' => $this->buildClusterPlanPrompt($businessDescription, $language, $pillars, $articlesPerPillar)],
            ],
            'temperature' => 0.6,
            'max_tokens' => 4000,
        ]);

        $result = $this->parseJsonObject($response->choices[0]->message->content) ?: ['clusters' => []];

        $this->log($user, AiAnalysis::TYPE_KEYWORD_SUGGESTION, $result, $response->usage->totalTokens ?? 0, [
            'input_keyword' => mb_substr($businessDescription, 0, 255),
        ]);

        return $result;
    }

    // =====================================================================
    // SYSTEM PROMPTS  (the brain — derived from norsk-seo-autopilot)
    // =====================================================================
    private function getSystemPrompt(string $type): string
    {
        $quality = <<<EOT
KVALITETSKRAV (gjelder alt norsk innhold):
- Skriv naturlig bokmål i "du"-form. Aldri arkaisk "De".
- Sammensatte ord skrives som ETT ord (bursdagspynt, ballongbue, festutstyr) — aldri særskriving.
- æ, ø og å skal alltid være korrekte i tekst og overskrifter.
- Norske forhold: priser i kr, norske høytider/sesonger (17. mai, jul, konfirmasjon, dåp), Posten/Bring. Aldri amerikanske referanser.
- Ingen fyll-åpninger ("I denne artikkelen skal vi…", "I dagens digitale verden…").
- Varier setningslengde. Vær konkret (tall, steg, mål). ALDRI oppdiktede statistikker, studier eller falske sitater.
EOT;

        $prompts = [
            'content_analysis' => <<<EOT
Du er en erfaren SEO-konsulent for det norske markedet. Analyser innhold og gi konkrete, prioriterte forbedringer.
$quality
Fokuser på: søkeintensjon, tittel/meta, struktur og lesbarhet, søkeordbruk (uten keyword stuffing), teknisk SEO (overskrifter, bilder/alt, interne lenker), og om innholdet svarer direkte på søket (viktig for AI-svar/GEO).
Svar i gyldig JSON.
EOT,

            'keyword_suggestion' => <<<EOT
Du er ekspert på norsk søkeordanalyse. Foreslå søkeord som ekte norske kjøpere bruker, og som et lite nettsted realistisk kan rangere på.
$quality
Prioriter long-tail og kommersiell/transaksjonell intensjon med lav konkurranse. Inkluder lokale varianter (byer, "nær meg") og spørsmålsbaserte søk.
Svar KUN med et JSON-array.
EOT,

            'content_generation' => <<<EOT
Du er en profesjonell norsk innholdsskribent og SEO-spesialist.
$quality
Skriv innhold som er optimalisert for søkeordet, naturlig for leseren, og som leder mot et tydelig neste steg for bedriften.
EOT,

            'article' => <<<EOT
Du er en profesjonell norsk innholdsskribent og SEO-spesialist som skriver innhold proven til å rangere på Google OG å bli sitert i AI-svar (Google AI Overviews, ChatGPT, Gemini, Perplexity).
$quality

STRUKTUR (svært viktig):
1. Start med et direkte svar på hovedspørsmålet i 2–4 setninger (omvendt pyramide → blir sitert av AI).
2. Bruk H2/H3 formulert som ekte spørsmål, med svaret rett under.
3. Inkluder minst én nummerert steg-liste ELLER en sammenligningstabell (formatene AI siterer mest).
4. Konkrete tall, priser i kr, norsk sesongkontekst.
5. Et naturlig neste steg som lenker til et relevant produkt/kategori.
6. En kort "Vanlige spørsmål" (FAQ) med 3–5 ekte spørsmål.
7. E-E-A-T: skriv med førstehånds erfaring ("slik gjør vi det…"), korrekt og verifiserbart.

Returner KUN gyldig JSON med feltene:
{
  "h1": "...",
  "title_tag": "<=60 tegn, søkeord først",
  "meta_description": "150-160 tegn",
  "slug": "kort-bindestrek-ascii",
  "primary_keyword": "...",
  "secondary_keywords": ["..."],
  "body_markdown": "hele artikkelen i markdown (uten H1, start med direktesvaret)",
  "faq": [{"q":"...","a":"..."}],
  "internal_link_suggestions": [{"anchor":"...","target":"..."}],
  "image_alt_suggestions": ["..."],
  "cluster": "hvilken pilar dette hører til"
}
EOT,

            'cluster_plan' => <<<EOT
Du er en norsk SEO-strateg. Bygg en topic cluster-plan: flere pilar-sider, hver med støtteartikler tett internlenket.
$quality
Regler:
- Prioriter artikler med lav konkurranse og høy kjøpsintensjon FØRST.
- Hver artikkel: primært søkeord, søkeintensjon (informational/commercial/transactional/local), forslag til H1, og hvilke interne sider/produkter den bør lenke til (inkl. sin pilar).
- Varier og hold det realistisk for et lite nettsted.

Returner KUN gyldig JSON:
{
  "clusters": [
    {
      "pillar_title": "...",
      "pillar_keyword": "...",
      "articles": [
        {"priority": 1, "keyword": "...", "intent": "commercial", "h1": "...", "internal_links": ["pilar", "/collections/..."]}
      ]
    }
  ]
}
EOT,
        ];

        return $prompts[$type] ?? $prompts['content_generation'];
    }

    // =====================================================================
    // USER PROMPT BUILDERS
    // =====================================================================
    private function buildContentAnalysisPrompt(string $content, string $keyword, ?string $url): string
    {
        $urlInfo = $url ? "URL: {$url}\n" : '';
        return <<<EOT
Analyser innholdet for SEO. {$urlInfo}Målsøkeord: {$keyword}

Innhold:
{$content}

Gi JSON med: overall_score (0-100), title_analysis{score,suggestions[]}, meta_description{score,suggestions[]},
content_analysis{word_count,keyword_density,readability_score,answers_query(bool),suggestions[]},
technical_seo{headings[],images_without_alt,internal_links,external_links}, overall_suggestions[] (prioritert).
EOT;
    }

    private function buildKeywordSuggestionPrompt(string $seedKeyword, string $language, int $count): string
    {
        $lang = $this->langName($language);
        return <<<EOT
Frøsøkeord: "{$seedKeyword}". Foreslå {$count} søkeord for det norske markedet på {$lang}.
For hvert: keyword, search_volume (tall), difficulty (0-100), cpc (NOK), intent (informational|navigational|transactional|commercial|local), trend (up|down|stable).
Bland korte, long-tail, spørsmålsbaserte og lokale varianter. Svar KUN med JSON-array.
EOT;
    }

    private function buildShortAssetPrompt(string $keyword, string $contentType, string $language, string $tone): string
    {
        $lang = $this->langName($language);
        $toneDesc = $this->toneName($tone);
        $instr = match ($contentType) {
            'product_description' => 'Skriv en overbevisende produktbeskrivelse på 150–300 ord som fremhever fordeler, og avslutt med en mild oppfordring.',
            'meta_description'    => 'Skriv en meta-beskrivelse på maks 155 tegn som inkluderer søkeordet og en nytte.',
            'title'               => 'Foreslå 5 SEO-optimaliserte titler på maks 60 tegn hver. Søkeordet først.',
            default               => 'Skriv SEO-optimalisert innhold.',
        };
        return "Søkeord: \"{$keyword}\"\nSpråk: {$lang}\nTone: {$toneDesc}\n\n{$instr}";
    }

    private function buildArticlePrompt(string $keyword, string $language, string $tone, bool $isPillar): string
    {
        $lang = $this->langName($language);
        $toneDesc = $this->toneName($tone);
        $len = $isPillar
            ? 'Dette er en PILAR-side: bred og definitiv, 2000–3500 ord, som dekker hele temaet og lenker til underartikler.'
            : 'Dette er en klynge-artikkel: fokusert, 1000–1600 ord, som svarer grundig på ett spørsmål.';
        return <<<EOT
Skriv en publiseringsklar artikkel for søkeordet: "{$keyword}".
Språk: {$lang}. Tone: {$toneDesc}.
{$len}
Følg strukturen og JSON-formatet fra systeminstruksjonen nøyaktig.
EOT;
    }

    private function buildClusterPlanPrompt(string $business, string $language, int $pillars, int $perPillar): string
    {
        $lang = $this->langName($language);
        return <<<EOT
Bedrift/nettsted: {$business}
Språk: {$lang}

Lag en topic cluster-plan med {$pillars} pilarer, hver med {$perPillar} støtteartikler.
Prioriter lav konkurranse + høy kjøpsintensjon først. Følg JSON-formatet fra systeminstruksjonen nøyaktig.
EOT;
    }

    // =====================================================================
    // NORWEGIAN QUALITY GUARDRAIL
    // =====================================================================
    /**
     * Lightweight checks that catch the most common "AI/translated Norwegian" tells.
     * Returns a list of human-readable warnings (empty = looks good).
     */
    public function validateNorwegian(string $text, string $language = 'nb'): array
    {
        $warnings = [];
        if (!in_array($language, ['nb', 'nn'])) {
            return $warnings;
        }

        // 1) Filler openings
        foreach (['I denne artikkelen', 'I dagens digitale', 'I en verden hvor', 'Når det gjelder'] as $filler) {
            if (mb_stripos($text, $filler) !== false) {
                $warnings[] = "Fyll-åpning oppdaget: \"{$filler}\" — bytt til et konkret svar/hook.";
                break;
            }
        }

        // 2) Missing Norwegian characters in a long text (strong tell of translated text)
        if (mb_strlen($text) > 400 && !preg_match('/[æøåÆØÅ]/u', $text)) {
            $warnings[] = 'Ingen æ/ø/å i en lang tekst — sannsynlig oversatt/unaturlig norsk.';
        }

        // 3) Archaic formal pronoun
        if (preg_match('/\bDe\b(?!\s*(som|er))/u', $text) && mb_substr_count($text, 'De ') > 2) {
            $warnings[] = 'Mulig bruk av arkaisk "De" — bruk "du".';
        }

        // 4) Common særskriving patterns (heuristic, not exhaustive)
        $particle = ['bursdag', 'ballong', 'fest', 'dåp', 'jule', 'barne', 'helium'];
        foreach ($particle as $p) {
            if (preg_match('/\b' . $p . '\s+(pynt|bue|utstyr|selskap|tank|ballonger)\b/iu', $text)) {
                $warnings[] = "Mulig særskriving nær \"{$p} …\" — sammensatte ord skal være ett ord.";
                break;
            }
        }

        return $warnings;
    }

    // =====================================================================
    // HELPERS
    // =====================================================================
    /** ASCII slug, Norwegian-letter safe (æ→ae, ø→o, å→a). */
    public function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = strtr($text, ['æ' => 'ae', 'ø' => 'o', 'å' => 'a', 'ä' => 'a', 'ö' => 'o', 'ü' => 'u']);
        $text = preg_replace('/[^a-z0-9]+/u', '-', $text);
        $text = trim($text, '-');
        return $text !== '' ? $text : 'artikkel';
    }

    private function langName(string $language): string
    {
        return match ($language) {
            'nb' => 'norsk bokmål',
            'nn' => 'norsk nynorsk',
            'en' => 'engelsk',
            default => 'norsk bokmål',
        };
    }

    private function toneName(string $tone): string
    {
        return match ($tone) {
            'professional' => 'profesjonell og autoritativ',
            'casual'       => 'uformell og vennlig',
            'friendly'     => 'varm og imøtekommende',
            default        => 'profesjonell',
        };
    }

    private function parseJsonObject(string $response): ?array
    {
        if (preg_match('/\{[\s\S]*\}/', $response, $m)) {
            $decoded = json_decode($m[0], true);
            return is_array($decoded) ? $decoded : null;
        }
        return null;
    }

    private function parseJsonArray(string $response): array
    {
        if (preg_match('/\[[\s\S]*\]/', $response, $m)) {
            $decoded = json_decode($m[0], true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    private function log(User $user, string $type, array $result, int $tokens, array $inputs = []): void
    {
        try {
            AiAnalysis::create(array_merge([
                'user_id' => $user->id,
                'type' => $type,
                'result' => $result,
                'tokens_used' => $tokens,
                'model_used' => $this->model,
            ], $inputs));
        } catch (\Throwable $e) {
            Log::warning('AiAnalysis logging failed', ['error' => $e->getMessage()]);
        }
    }

    private function getDefaultAnalysisResult(): array
    {
        return [
            'overall_score' => 0,
            'title_analysis' => ['score' => 0, 'suggestions' => []],
            'meta_description' => ['score' => 0, 'suggestions' => []],
            'content_analysis' => ['word_count' => 0, 'keyword_density' => 0, 'readability_score' => 0, 'answers_query' => false, 'suggestions' => []],
            'technical_seo' => ['headings' => [], 'images_without_alt' => 0, 'internal_links' => 0, 'external_links' => 0],
            'overall_suggestions' => [],
        ];
    }
}
