<?php

namespace App\Services\Item;

use App\Enums\ItemCategory;
use App\Enums\ItemFitType;
use App\Enums\ItemPattern;
use App\Enums\ItemStretch;
use App\Enums\Season;
use App\Support\Color;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AnalyzePhotoService
{
    public function __construct(private readonly LocalPhotoAnalyzer $local) {}

    /**
     * @return array{suggestions: array<string, mixed>, source: string, note: string|null, fallback: bool}
     */
    public function analyze(UploadedFile $file, int $userId): array
    {
        $provider = strtolower((string) config('ai.provider', ''));
        $key = $this->keyFor($provider);

        if ($provider === '' || $key === null || $key === '') {
            $local = $this->local->analyze($file);

            return [
                'suggestions' => $this->normalise($local),
                'source' => 'local',
                'note' => $local['note'] ?? null,
                'fallback' => false,
            ];
        }

        if ($this->dailyLimitReached($userId)) {
            $local = $this->local->analyze($file);

            return [
                'suggestions' => $this->normalise($local),
                'source' => 'local',
                'note' => 'Daily AI limit reached. Showing local colour suggestions only.',
                'fallback' => true,
            ];
        }

        try {
            $payload = match ($provider) {
                'gemini' => $this->callGemini($file, $key),
                'openrouter' => $this->callOpenRouter($file, $key),
                default => throw new \RuntimeException("Unsupported AI provider [{$provider}]."),
            };

            $this->hitDailyLimit($userId);

            return [
                'suggestions' => $this->normalise($payload),
                'source' => $provider,
                'note' => 'Suggestions from the photo. Review before saving.',
                'fallback' => false,
            ];
        } catch (\Throwable $e) {
            Log::warning('Photo analysis AI failed; using local fallback.', [
                'provider' => $provider,
                'message' => $e->getMessage(),
            ]);

            $local = $this->local->analyze($file);

            return [
                'suggestions' => $this->normalise(array_merge($local, [
                    // Keep any partial AI fields out; local only on failure.
                ])),
                'source' => 'local',
                'note' => 'AI analysis failed ('.$this->safeReason($e).'). Showing local colour suggestions.',
                'fallback' => true,
            ];
        }
    }

    public function isConfigured(): bool
    {
        $provider = strtolower((string) config('ai.provider', ''));

        if ($provider === '') {
            return false;
        }

        $key = $this->keyFor($provider);

        return is_string($key) && $key !== '';
    }

    private function keyFor(string $provider): ?string
    {
        return config("ai.keys.{$provider}");
    }

    private function dailyLimitReached(int $userId): bool
    {
        $limit = (int) config('ai.daily_limit', 20);
        $count = (int) Cache::get($this->dailyKey($userId), 0);

        return $count >= $limit;
    }

    private function hitDailyLimit(int $userId): void
    {
        $key = $this->dailyKey($userId);
        $count = (int) Cache::get($key, 0);
        Cache::put($key, $count + 1, now()->endOfDay());
    }

    private function dailyKey(int $userId): string
    {
        return 'ai-photo-analyze:'.$userId.':'.now()->toDateString();
    }

    /**
     * @return array<string, mixed>
     */
    private function callGemini(UploadedFile $file, string $key): array
    {
        $model = (string) config('ai.model', 'gemini-3.8-flash');
        $url = sprintf((string) config('ai.gemini.endpoint'), $model);

        $mime = $file->getMimeType() ?: 'image/jpeg';
        $base64 = base64_encode((string) file_get_contents($file->getRealPath()));

        $payload = [
            'contents' => [[
                'parts' => [
                    ['text' => $this->prompt()],
                    [
                        'inline_data' => [
                            'mime_type' => $mime,
                            'data' => $base64,
                        ],
                    ],
                ],
            ]],
            'generationConfig' => [
                'temperature' => 0.2,
                'responseMimeType' => 'application/json',
            ],
        ];

        $response = null;
        $attempts = 0;

        // Free-tier Flash is often busy; retry a couple of times on 503.
        while ($attempts < 3) {
            $attempts++;
            $response = Http::timeout((int) config('ai.timeout', 20))
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $key,
                ])
                ->post($url, $payload);

            if ($response->status() !== 503) {
                break;
            }

            usleep(400000 * $attempts);
        }

        if ($response->status() === 429) {
            throw new \RuntimeException('rate_limited');
        }

        if ($response->status() === 503) {
            throw new \RuntimeException('high_demand');
        }

        if ($response->status() === 404) {
            $message = (string) data_get($response->json(), 'error.message', '');
            throw new \RuntimeException('model_unavailable:'.$message);
        }

        if (! $response->successful()) {
            $message = (string) data_get($response->json(), 'error.message', '');
            Log::warning('Gemini photo analysis HTTP error', [
                'status' => $response->status(),
                'message' => Str::limit($message, 200, ''),
            ]);
            throw new \RuntimeException('http_'.$response->status());
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text', '');

        return $this->decodeJson((string) $text);
    }

    /**
     * @return array<string, mixed>
     */
    private function callOpenRouter(UploadedFile $file, string $key): array
    {
        $model = (string) (config('ai.model') ?: config('ai.openrouter.default_model'));
        $mime = $file->getMimeType() ?: 'image/jpeg';
        $base64 = base64_encode((string) file_get_contents($file->getRealPath()));
        $dataUrl = 'data:'.$mime.';base64,'.$base64;

        $response = Http::timeout((int) config('ai.timeout', 20))
            ->withToken($key)
            ->withHeaders([
                'HTTP-Referer' => (string) config('app.url'),
                'X-Title' => (string) config('app.name', 'Dressr'),
            ])
            ->post((string) config('ai.openrouter.endpoint'), [
                'model' => $model,
                'messages' => [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $this->prompt()],
                        ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]],
                    ],
                ]],
                'temperature' => 0.2,
                'response_format' => ['type' => 'json_object'],
            ]);

        if ($response->status() === 429) {
            throw new \RuntimeException('rate_limited');
        }

        if (! $response->successful()) {
            throw new \RuntimeException('http_'.$response->status());
        }

        $text = (string) data_get($response->json(), 'choices.0.message.content', '');

        return $this->decodeJson($text);
    }

    private function prompt(): string
    {
        $categories = implode(', ', ItemCategory::values());
        $patterns = implode(', ', ItemPattern::values());
        $fitTypes = implode(', ', ItemFitType::values());
        $stretches = implode(', ', ItemStretch::values());
        $seasons = implode(', ', Season::values());
        $cutTags = implode(', ', config('stylist.cut_tags', []));

        return <<<PROMPT
You are a clothing cataloguer. Look at the garment photo and return ONLY valid JSON (no markdown) with these keys:
{
  "name": "short item name",
  "category": one of [{$categories}],
  "subcategory": "e.g. tshirt, jeans, sneakers",
  "primary_color_hex": "#rrggbb",
  "secondary_color_hex": "#rrggbb or null",
  "pattern": one of [{$patterns}],
  "material": "guess or null",
  "seasons": array subset of [{$seasons}],
  "formality": integer 1-5,
  "fit_type": one of [{$fitTypes}],
  "stretch": one of [{$stretches}],
  "cut_tags": array subset of [{$cutTags}], max 4,
  "size_label": "guess or null",
  "notes": "one short observation or null"
}
If unsure, pick the closest valid enum value. Never invent hex colours without a #.
PROMPT;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $text): array
    {
        $text = trim($text);
        $text = preg_replace('/^```(?:json)?\s*/i', '', $text) ?? $text;
        $text = preg_replace('/\s*```$/', '', $text) ?? $text;

        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            throw new \RuntimeException('invalid_json');
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    private function normalise(array $raw): array
    {
        $primary = $this->hexOrNull($raw['primary_color_hex'] ?? null) ?? '#1e3a8a';
        $secondary = $this->hexOrNull($raw['secondary_color_hex'] ?? null);

        $category = $this->enumValue($raw['category'] ?? null, ItemCategory::values(), 'top');
        $pattern = $this->enumValue($raw['pattern'] ?? null, ItemPattern::values(), 'solid');
        $fitType = $this->enumValue($raw['fit_type'] ?? null, ItemFitType::values(), 'regular');
        $stretch = $this->enumValue($raw['stretch'] ?? null, ItemStretch::values(), 'none');

        $seasons = collect($raw['seasons'] ?? [])
            ->map(fn ($s) => strtolower((string) $s))
            ->filter(fn ($s) => in_array($s, Season::values(), true))
            ->unique()
            ->values()
            ->all();

        if ($seasons === []) {
            $seasons = ['spring', 'summer', 'autumn', 'winter'];
        }

        $allowedTags = config('stylist.cut_tags', []);
        $cutTags = collect($raw['cut_tags'] ?? [])
            ->map(fn ($t) => strtolower((string) $t))
            ->filter(fn ($t) => in_array($t, $allowedTags, true))
            ->unique()
            ->take(4)
            ->values()
            ->all();

        $formality = (int) ($raw['formality'] ?? 3);
        $formality = max(1, min(5, $formality));

        $name = trim((string) ($raw['name'] ?? ''));
        if ($name === '') {
            $name = Str::title(str_replace('_', ' ', $category));
        }

        $subcategory = trim((string) ($raw['subcategory'] ?? ''));
        if ($subcategory === '') {
            $subcategory = $category;
        }

        return [
            'name' => Str::limit($name, 120, ''),
            'category' => $category,
            'subcategory' => Str::limit($subcategory, 60, ''),
            'primary_color_hex' => $primary,
            'secondary_color_hex' => $secondary,
            'color_family' => Color::family($primary),
            'pattern' => $pattern,
            'material' => $this->nullableString($raw['material'] ?? null, 100),
            'seasons' => $seasons,
            'formality' => $formality,
            'fit_type' => $fitType,
            'stretch' => $stretch,
            'cut_tags' => $cutTags,
            'size_label' => $this->nullableString($raw['size_label'] ?? null, 20),
            'notes' => $this->nullableString($raw['notes'] ?? null, 1000),
        ];
    }

    private function hexOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);
        if (! str_starts_with($value, '#')) {
            $value = '#'.$value;
        }

        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : null;
    }

    /**
     * @param  list<string>  $allowed
     */
    private function enumValue(mixed $value, array $allowed, string $default): string
    {
        $value = strtolower(trim((string) $value));

        return in_array($value, $allowed, true) ? $value : $default;
    }

    private function nullableString(mixed $value, int $max): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || strtolower($value) === 'null') {
            return null;
        }

        return Str::limit($value, $max, '');
    }

    private function safeReason(\Throwable $e): string
    {
        $message = $e->getMessage();

        return match (true) {
            str_contains($message, 'rate_limited') => 'rate limited',
            str_contains($message, 'high_demand') => 'Gemini is busy — try again in a minute',
            str_contains($message, 'model_unavailable') => 'AI model unavailable — set AI_MODEL=gemini-3.8-flash in .env',
            str_contains($message, 'invalid_json') => 'invalid AI response',
            str_contains($message, 'http_401'), str_contains($message, 'http_403') => 'invalid API key',
            str_contains($message, 'http_') => 'provider error',
            default => 'provider unavailable',
        };
    }
}
