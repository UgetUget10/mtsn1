<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiLocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_index_resolves_translatable_title_to_locale_string(): void
    {
        // Regresi: HasTranslations hanya meng-override getAttribute(), BUKAN
        // toArray()/jsonSerialize() — endpoint yang mengembalikan model
        // langsung (atau lewat get([...]) tanpa map eksplisit) akan
        // membocorkan array translasi mentah alih-alih string locale aktif.
        $page = Page::create([
            'title' => 'Judul Indonesia',
            'slug' => 'judul-indonesia',
            'is_published' => true,
        ]);
        $page->setTranslation('title', 'en', 'English Title');
        $page->save();

        $default = $this->getJson('/api/v1/pages')->json();
        $found = collect($default)->firstWhere('slug', 'judul-indonesia');
        $this->assertIsString($found['title']);
        $this->assertSame('Judul Indonesia', $found['title']);

        $english = $this->getJson('/api/v1/pages?locale=en')->json();
        $foundEn = collect($english)->firstWhere('slug', 'judul-indonesia');
        $this->assertIsString($foundEn['title']);
        $this->assertSame('English Title', $foundEn['title']);
    }

    public function test_post_show_endpoint_respects_locale_query_param(): void
    {
        $post = Post::create([
            'title' => 'Judul Berita',
            'slug' => 'judul-berita',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
        $post->setTranslation('title', 'en', 'News Title');
        $post->save();

        $this->getJson('/api/v1/posts/judul-berita')
            ->assertJsonPath('data.title', 'Judul Berita');

        $this->getJson('/api/v1/posts/judul-berita?locale=en')
            ->assertJsonPath('data.title', 'News Title');
    }

    public function test_untranslated_locale_falls_back_to_default_content_locale(): void
    {
        $page = Page::create([
            'title' => 'Hanya Bahasa Indonesia',
            'slug' => 'hanya-id',
            'is_published' => true,
        ]);

        $english = $this->getJson('/api/v1/pages?locale=en')->json();
        $found = collect($english)->firstWhere('slug', 'hanya-id');

        // Fallback locale (APP_FALLBACK_LOCALE=id) harus membuat halaman yang
        // belum diterjemahkan tetap tampil dengan teks Indonesia, bukan kosong.
        $this->assertSame('Hanya Bahasa Indonesia', $found['title']);
    }
}
