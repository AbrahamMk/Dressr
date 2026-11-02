<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AnalyzeItemPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_analyze_requires_authentication(): void
    {
        $this->post('/wardrobe/analyze')->assertRedirect('/login');
    }

    public function test_analyze_requires_an_image(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/wardrobe/analyze', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);
    }

    public function test_local_fallback_when_no_ai_configured(): void
    {
        config([
            'ai.provider' => '',
            'ai.keys.gemini' => null,
        ]);

        $user = User::factory()->create();
        $photo = UploadedFile::fake()->image('shirt.jpg', 200, 200);

        $response = $this->actingAs($user)->postJson('/wardrobe/analyze', [
            'photo' => $photo,
        ]);

        $response->assertOk()
            ->assertJsonPath('source', 'local')
            ->assertJsonStructure([
                'suggestions' => [
                    'primary_color_hex',
                    'category',
                    'pattern',
                    'seasons',
                ],
                'note',
                'fallback',
            ]);

        $this->assertMatchesRegularExpression(
            '/^#[0-9a-f]{6}$/',
            $response->json('suggestions.primary_color_hex')
        );
    }

    public function test_gemini_suggestions_are_normalised(): void
    {
        config([
            'ai.provider' => 'gemini',
            'ai.model' => 'gemini-3.8-flash',
            'ai.keys.gemini' => 'test-key-not-real',
            'ai.daily_limit' => 50,
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'name' => 'Blue Oxford',
                                'category' => 'top',
                                'subcategory' => 'shirt',
                                'primary_color_hex' => '#1e3a8a',
                                'secondary_color_hex' => null,
                                'pattern' => 'solid',
                                'material' => 'cotton',
                                'seasons' => ['spring', 'summer'],
                                'formality' => 3,
                                'fit_type' => 'regular',
                                'stretch' => 'none',
                                'cut_tags' => ['fitted', 'structured_shoulder'],
                                'size_label' => 'M',
                                'notes' => 'Clean dress shirt',
                            ]),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $user = User::factory()->create();
        $photo = UploadedFile::fake()->image('oxford.jpg', 400, 400);

        $response = $this->actingAs($user)->postJson('/wardrobe/analyze', [
            'photo' => $photo,
        ]);

        $response->assertOk()
            ->assertJsonPath('source', 'gemini')
            ->assertJsonPath('fallback', false)
            ->assertJsonPath('suggestions.name', 'Blue Oxford')
            ->assertJsonPath('suggestions.category', 'top')
            ->assertJsonPath('suggestions.primary_color_hex', '#1e3a8a')
            ->assertJsonPath('suggestions.pattern', 'solid');

        $this->assertStringNotContainsString('test-key', $response->getContent());
    }

    public function test_malformed_ai_response_falls_back_locally(): void
    {
        config([
            'ai.provider' => 'gemini',
            'ai.keys.gemini' => 'test-key',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'not json']]],
                ]],
            ], 200),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/wardrobe/analyze', [
                'photo' => UploadedFile::fake()->image('bad.jpg', 100, 100),
            ])
            ->assertOk()
            ->assertJsonPath('source', 'local')
            ->assertJsonPath('fallback', true);
    }

    public function test_rate_limited_ai_falls_back(): void
    {
        config([
            'ai.provider' => 'gemini',
            'ai.keys.gemini' => 'test-key',
        ]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'quota'], 429),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/wardrobe/analyze', [
                'photo' => UploadedFile::fake()->image('quota.jpg', 100, 100),
            ])
            ->assertOk()
            ->assertJsonPath('fallback', true)
            ->assertJsonPath('source', 'local');
    }
}
