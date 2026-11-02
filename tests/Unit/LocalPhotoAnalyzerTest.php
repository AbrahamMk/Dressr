<?php

namespace Tests\Unit;

use App\Services\Item\LocalPhotoAnalyzer;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class LocalPhotoAnalyzerTest extends TestCase
{
    public function test_samples_a_primary_hex_from_an_image(): void
    {
        $analyzer = new LocalPhotoAnalyzer;
        $file = UploadedFile::fake()->image('reddish.jpg', 120, 120);

        $result = $analyzer->analyze($file);

        $this->assertArrayHasKey('primary_color_hex', $result);
        $this->assertMatchesRegularExpression('/^#[0-9a-f]{6}$/', $result['primary_color_hex']);
        $this->assertSame('local', $result['source']);
    }
}
