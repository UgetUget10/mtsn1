<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;

/**
 * Arsip kategori ala WordPress — daftar kategori & detail satu kategori
 * (untuk header halaman /berita/kategori/{slug} di frontend). Berita per
 * kategori tetap diambil lewat PostController@index dengan `?category=`.
 *
 * Kategori bersifat hierarkis (wp: parent category): setiap entri membawa
 * `parent` (slug induk atau null) dan detail membawa `ancestors` untuk
 * breadcrumb serta `children` untuk sub-kategori.
 */
class CategoryController extends Controller
{
    /** Kategori yang punya minimal satu berita publik. */
    public function index()
    {
        return Category::query()
            ->where('type', 'post')
            ->where(fn ($q) => $q
                ->whereHas('posts', fn ($p) => $p->published())
                ->orWhereHas('taggedPosts', fn ($p) => $p->published()))
            ->withCount(['posts as posts_count' => fn ($q) => $q->published()])
            ->with('parent:id,slug')
            ->orderBy('name')
            ->get()
            ->map(fn (Category $c) => [
                'name' => $c->name,
                'slug' => $c->slug,
                'description' => $c->description,
                'posts_count' => $c->posts_count,
                'parent' => $c->parent?->slug,
            ]);
    }

    public function show(Category $category)
    {
        $category->load('children:id,slug,parent_id');

        return [
            'name' => $category->name,
            'slug' => $category->slug,
            'description' => $category->description,
            'parent' => $category->parent?->slug,
            'ancestors' => $category->ancestors()->map(fn (Category $a) => [
                'name' => $a->name,
                'slug' => $a->slug,
            ])->values(),
            'children' => $category->children->map(fn (Category $c) => [
                'name' => $c->name,
                'slug' => $c->slug,
            ])->values(),
        ];
    }
}
