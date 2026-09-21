<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class CategoryHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('public-api');
    }

    private function publishedPost(Category $category): Post
    {
        return Post::create([
            'title' => 'Berita '.$category->slug,
            'body' => 'isi',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'user_id' => User::factory()->create()->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_parent_category_archive_includes_child_posts(): void
    {
        $parent = Category::create(['name' => 'Akademik', 'type' => 'post']);
        $child = Category::create(['name' => 'OSN', 'type' => 'post', 'parent_id' => $parent->id]);

        $this->publishedPost($parent);
        $this->publishedPost($child);

        $res = $this->getJson('/api/v1/posts?category='.$parent->slug);

        $res->assertOk()->assertJsonCount(2, 'data');
    }

    public function test_child_category_archive_excludes_parent_only_posts(): void
    {
        $parent = Category::create(['name' => 'Akademik', 'type' => 'post']);
        $child = Category::create(['name' => 'OSN', 'type' => 'post', 'parent_id' => $parent->id]);

        $this->publishedPost($parent);
        $this->publishedPost($child);

        $res = $this->getJson('/api/v1/posts?category='.$child->slug);

        $res->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_category_detail_exposes_ancestors_and_children(): void
    {
        $root = Category::create(['name' => 'Kesiswaan', 'type' => 'post']);
        $mid = Category::create(['name' => 'Ekstrakurikuler', 'type' => 'post', 'parent_id' => $root->id]);
        $leaf = Category::create(['name' => 'Pramuka', 'type' => 'post', 'parent_id' => $mid->id]);
        Category::create(['name' => 'Regu Inti', 'type' => 'post', 'parent_id' => $leaf->id]);

        $res = $this->getJson('/api/v1/categories/'.$leaf->slug);

        $res->assertOk()
            ->assertJsonPath('parent', $mid->slug)
            ->assertJsonPath('ancestors.0.slug', $root->slug)
            ->assertJsonPath('ancestors.1.slug', $mid->slug)
            ->assertJsonPath('children.0.slug', 'regu-inti');
    }

    public function test_categories_index_carries_parent_slug(): void
    {
        $parent = Category::create(['name' => 'Akademik', 'type' => 'post']);
        $child = Category::create(['name' => 'OSN', 'type' => 'post', 'parent_id' => $parent->id]);
        $this->publishedPost($child);

        $res = $this->getJson('/api/v1/categories');

        $res->assertOk();
        $child = collect($res->json())->firstWhere('slug', 'osn');
        $this->assertSame($parent->slug, $child['parent']);
    }
}
