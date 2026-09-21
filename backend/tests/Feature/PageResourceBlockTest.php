<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\Gallery;
use App\Models\GalleryItem;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PageResourceBlockTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-api');
    }

    public function test_file_list_block_resolves_document_ids_to_summaries(): void
    {
        $doc = Document::create(['title' => 'Panduan PPDB', 'file' => 'documents/panduan.pdf']);

        $page = Page::create([
            'title' => 'Unduhan',
            'slug' => 'unduhan',
            'is_published' => true,
            'blocks' => [[
                'type' => 'file_list',
                'data' => ['heading' => 'Berkas', 'files' => [
                    ['document_id' => $doc->id],
                    ['document_id' => 99999], // tidak ada — harus terbuang
                ]],
            ]],
        ]);

        $response = $this->getJson("/api/v1/pages/{$page->slug}");

        $response->assertOk()
            ->assertJsonPath('data.blocks.0.type', 'file_list')
            ->assertJsonCount(1, 'data.blocks.0.data.files')
            ->assertJsonPath('data.blocks.0.data.files.0.title', 'Panduan PPDB');
    }

    public function test_gallery_block_resolves_gallery_id_with_items(): void
    {
        $gallery = Gallery::create(['title' => 'Wisuda', 'slug' => 'wisuda']);
        GalleryItem::create(['gallery_id' => $gallery->id, 'type' => 'image', 'path' => 'galleries/1.jpg', 'caption' => 'Foto 1', 'order' => 0]);
        GalleryItem::create(['gallery_id' => $gallery->id, 'type' => 'video', 'video_url' => 'https://youtu.be/abc', 'caption' => 'Video', 'order' => 1]);

        $page = Page::create([
            'title' => 'Galeri Wisuda',
            'slug' => 'galeri-wisuda',
            'is_published' => true,
            'blocks' => [[
                'type' => 'gallery_block',
                'data' => ['heading' => null, 'gallery_id' => $gallery->id],
            ]],
        ]);

        $response = $this->getJson("/api/v1/pages/{$page->slug}");

        $response->assertOk()
            ->assertJsonPath('data.blocks.0.data.gallery.title', 'Wisuda')
            ->assertJsonPath('data.blocks.0.data.gallery.slug', 'wisuda')
            ->assertJsonCount(2, 'data.blocks.0.data.gallery.items')
            ->assertJsonPath('data.blocks.0.data.gallery.items.1.url', 'https://youtu.be/abc');
    }

    public function test_invisible_block_is_omitted_from_api(): void
    {
        $page = Page::create([
            'title' => 'Campur',
            'slug' => 'campur',
            'is_published' => true,
            'blocks' => [
                ['type' => 'rich_text', 'is_visible' => true, 'data' => ['heading' => null, 'body' => 'Tampil']],
                ['type' => 'rich_text', 'is_visible' => false, 'data' => ['heading' => null, 'body' => 'Sembunyi']],
            ],
        ]);

        $this->getJson("/api/v1/pages/{$page->slug}")
            ->assertOk()
            ->assertJsonCount(1, 'data.blocks')
            ->assertJsonPath('data.blocks.0.data.body', 'Tampil');
    }

    public function test_unpublished_page_returns_404(): void
    {
        $page = Page::create(['title' => 'Draf', 'slug' => 'draf', 'is_published' => false]);

        $this->getJson("/api/v1/pages/{$page->slug}")->assertNotFound();
    }

    public function test_legacy_body_is_wrapped_as_rich_text_block_in_api(): void
    {
        $page = Page::create([
            'title' => 'Lama',
            'slug' => 'lama',
            'body' => '<p>Konten warisan.</p>',
            'is_published' => true,
        ]);

        $this->getJson("/api/v1/pages/{$page->slug}")
            ->assertOk()
            ->assertJsonPath('data.blocks.0.type', 'rich_text')
            ->assertJsonPath('data.blocks.0.data.body', '<p>Konten warisan.</p>');
    }
}
