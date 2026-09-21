<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use App\Support\Content\Duplicator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DuplicateContentTest extends TestCase
{
    use RefreshDatabase;

    private function mkPost(array $attrs = []): Post
    {
        return Post::create(array_merge([
            'title' => 'Berita Asli',
            'excerpt' => 'Ringkasan.',
            'body' => '<p>Isi lengkap.</p>',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'user_id' => User::factory()->create()->id,
        ], $attrs));
    }

    public function test_copy_is_a_draft_with_marked_title_and_new_slug(): void
    {
        $post = $this->mkPost();
        $copy = Duplicator::post($post);

        $this->assertNotSame($post->id, $copy->id);
        $this->assertSame('Berita Asli (salinan)', $copy->title);
        $this->assertSame(Post::STATUS_DRAFT, $copy->status);
        $this->assertNotSame($post->slug, $copy->slug);
        $this->assertNotEmpty($copy->slug);
    }

    public function test_copy_does_not_inherit_publish_state_or_stats(): void
    {
        $post = $this->mkPost(['is_featured' => true]);
        $post->forceFill(['views' => 500])->saveQuietly();

        $copy = Duplicator::post($post->fresh());

        $this->assertNull($copy->published_at);
        $this->assertSame(0, (int) $copy->views);
        $this->assertFalse((bool) $copy->is_featured);
    }

    public function test_copy_never_inherits_the_password(): void
    {
        $post = $this->mkPost([
            'visibility' => Post::VISIBILITY_PASSWORD,
            'password' => 'rahasia123',
        ]);

        $copy = Duplicator::post($post->fresh());

        $this->assertNull($copy->password);
        $this->assertSame(Post::VISIBILITY_PUBLIC, $copy->visibility);
    }

    public function test_copy_gets_its_own_preview_token(): void
    {
        $post = $this->mkPost();
        $copy = Duplicator::post($post);

        $this->assertNotEmpty($copy->preview_token);
        $this->assertNotSame($post->preview_token, $copy->preview_token);
    }

    public function test_copy_keeps_taxonomy(): void
    {
        $post = $this->mkPost();
        $cat = Category::create(['name' => 'Kabar', 'slug' => 'kabar', 'type' => 'post']);
        $tag = Tag::create(['name' => 'Prestasi', 'slug' => 'prestasi']);
        $post->categories()->sync([$cat->id]);
        $post->tags()->sync([$tag->id]);

        $copy = Duplicator::post($post->fresh()->load(['categories', 'tags']));

        $this->assertSame([$cat->id], $copy->categories()->pluck('categories.id')->all());
        $this->assertSame([$tag->id], $copy->tags()->pluck('tags.id')->all());
    }

    public function test_copy_does_not_inherit_revisions(): void
    {
        $post = $this->mkPost();
        $post->update(['title' => 'Judul Diubah']); // bikin 1 revisi

        $copy = Duplicator::post($post->fresh());

        $this->assertSame(1, $post->revisions()->count());
        $this->assertSame(0, $copy->revisions()->count());
    }

    public function test_translatable_title_is_marked_in_every_locale(): void
    {
        $post = $this->mkPost(['title' => ['id' => 'Halo', 'en' => 'Hello']]);

        $copy = Duplicator::post($post->fresh());

        $this->assertSame('Halo (salinan)', $copy->getTranslation('title', 'id'));
        $this->assertSame('Hello (salinan)', $copy->getTranslation('title', 'en'));
    }

    public function test_page_copy_is_unpublished_and_keeps_blocks(): void
    {
        $blocks = [['type' => 'rich_text', 'data' => ['body' => '<p>Blok.</p>']]];
        $page = Page::create([
            'title' => 'Halaman Asli',
            'slug' => 'halaman-asli',
            'is_published' => true,
            'template' => 'full-width',
            'blocks' => $blocks,
        ]);

        $copy = Duplicator::page($page);

        $this->assertSame('Halaman Asli (salinan)', $copy->title);
        $this->assertFalse((bool) $copy->is_published);
        $this->assertNotSame($page->slug, $copy->slug);
        $this->assertSame($blocks, $copy->blocks);
        $this->assertSame('full-width', $copy->template);
    }

    public function test_duplicated_draft_is_not_exposed_publicly(): void
    {
        $post = $this->mkPost();
        $copy = Duplicator::post($post);

        // Draft tak boleh bocor ke jalur publik.
        $this->getJson("/api/v1/posts/{$copy->slug}")->assertNotFound();
    }
}
