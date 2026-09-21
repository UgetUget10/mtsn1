<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PageTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-api');
    }

    public function test_unknown_template_falls_back_to_default_in_api(): void
    {
        $page = Page::create([
            'title' => 'Sejarah',
            'slug' => 'sejarah',
            'is_published' => true,
            'template' => 'tema-tak-dikenal',
        ]);

        $this->getJson("/api/v1/pages/{$page->slug}")
            ->assertOk()
            ->assertJsonPath('data.template', 'default');
    }

    public function test_valid_template_is_passed_through_to_api(): void
    {
        $page = Page::create([
            'title' => 'Visi Misi',
            'slug' => 'visi-misi',
            'is_published' => true,
            'template' => 'sidebar-nav',
        ]);

        $this->getJson("/api/v1/pages/{$page->slug}")
            ->assertOk()
            ->assertJsonPath('data.template', 'sidebar-nav');
    }

    public function test_template_change_is_captured_in_revision_snapshot(): void
    {
        $page = Page::create(['title' => 'Kontak', 'template' => 'default']);
        $page->update(['template' => 'landing', 'title' => 'Kontak Kami']);

        $this->assertSame(
            'default',
            $page->revisions()->latest('id')->first()->data['template'] ?? null,
        );
    }
}
