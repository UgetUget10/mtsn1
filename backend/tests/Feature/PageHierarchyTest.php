<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PageHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-api');
    }

    private function page(string $title, ?Page $parent = null, bool $published = true): Page
    {
        return Page::create([
            'title' => $title,
            'is_published' => $published,
            'parent_id' => $parent?->id,
        ]);
    }

    public function test_page_show_exposes_parent_ancestors_and_published_children(): void
    {
        $root = $this->page('Kurikulum');
        $mid = $this->page('Kurikulum Merdeka', $root);
        $this->page('Kelas 7', $mid);
        $this->page('Draft Sub', $mid, published: false);

        $res = $this->getJson('/api/v1/pages/'.$mid->slug);

        $res->assertOk()
            ->assertJsonPath('data.parent', $root->slug)
            ->assertJsonPath('data.ancestors.0.slug', $root->slug)
            ->assertJsonCount(1, 'data.children') // draft child disembunyikan
            ->assertJsonPath('data.children.0.slug', 'kelas-7');
    }

    public function test_pages_index_carries_parent_slug(): void
    {
        $root = $this->page('Kurikulum');
        $child = $this->page('Kurikulum Merdeka', $root);

        $res = $this->getJson('/api/v1/pages');

        $res->assertOk();
        $rows = collect($res->json());
        $this->assertNull($rows->firstWhere('slug', $root->slug)['parent']);
        $this->assertSame($root->slug, $rows->firstWhere('slug', $child->slug)['parent']);
    }

    public function test_deleting_parent_nulls_child_parent_id(): void
    {
        $root = $this->page('Kurikulum');
        $child = $this->page('Kurikulum Merdeka', $root);

        $root->forceDelete();

        $this->assertNull($child->fresh()->parent_id);
    }
}
