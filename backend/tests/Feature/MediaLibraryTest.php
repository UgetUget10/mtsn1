<?php

namespace Tests\Feature;

use App\Models\Slider;
use App\Models\Teacher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaLibraryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-api');
        Storage::fake('public');
    }

    /** PNG 2×2 valid — cukup untuk konversi GD di test. */
    private function pngBytes(): string
    {
        $img = imagecreatetruecolor(2, 2);
        ob_start();
        imagepng($img);
        $bytes = ob_get_clean();
        imagedestroy($img);

        return $bytes;
    }

    public function test_sliders_api_prefers_media_library_url_over_legacy_path(): void
    {
        $slider = Slider::create(['title' => 'S', 'order' => 0, 'is_active' => true, 'image' => 'sliders/old.jpg']);
        Storage::disk('public')->put('sliders/old.jpg', 'legacy-bytes');

        $slider->addMediaFromString($this->pngBytes())
            ->usingFileName('new.jpg')
            ->toMediaCollection('image');

        $url = $this->getJson('/api/v1/sliders')->json('0.image');

        // URL media library berpola /storage/{id}/... (opsional /conversions/);
        // yang penting: BUKAN path legacy sliders/old.jpg.
        $this->assertMatchesRegularExpression('#/storage/\d+/#', $url, 'API harus memakai URL media library.');
        $this->assertStringNotContainsString('sliders/old.jpg', $url);
    }

    public function test_sliders_api_falls_back_to_legacy_path_when_no_media(): void
    {
        Slider::create(['title' => 'S', 'order' => 0, 'is_active' => true, 'image' => 'sliders/legacy.jpg']);
        Storage::disk('public')->put('sliders/legacy.jpg', 'x');

        $url = $this->getJson('/api/v1/sliders')->json('0.image');

        $this->assertStringContainsString('sliders/legacy.jpg', $url);
    }

    public function test_sliders_api_returns_null_image_when_legacy_file_missing(): void
    {
        Slider::create(['title' => 'S', 'order' => 0, 'is_active' => true, 'image' => 'sliders/gone.jpg']);

        $this->assertNull($this->getJson('/api/v1/sliders')->json('0.image'));
    }

    public function test_teacher_api_returns_media_library_url_when_present(): void
    {
        $teacher = Teacher::create([
            'name' => 'Bu Guru', 'group' => 'guru', 'is_active' => true, 'order' => 0,
        ]);

        $teacher->addMediaFromString($this->pngBytes())
            ->usingFileName('foto.jpg')
            ->toMediaCollection('photo');

        $url = $this->getJson('/api/v1/teachers')->json('0.photo');

        $this->assertNotNull($url);
        $this->assertStringContainsString('/storage/', $url);
    }

    public function test_media_migrate_legacy_command_runs_in_dry_run_without_writing(): void
    {
        Slider::create(['title' => 'S', 'order' => 0, 'is_active' => true, 'image' => 'sliders/x.jpg']);
        Storage::disk('public')->put('sliders/x.jpg', 'x');

        $this->artisan('media:migrate-legacy --dry-run')->assertSuccessful();

        $this->assertSame(0, Slider::first()->getMedia('image')->count(), 'Dry-run tidak boleh menulis entri media.');
    }
}
