<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;

class TagController extends Controller
{
    /** Daftar tag yang punya minimal satu post publik — mirip wp_tag_cloud. */
    public function index()
    {
        return Tag::query()
            ->whereHas('posts', fn ($q) => $q->published())
            ->withCount(['posts as posts_count' => fn ($q) => $q->published()])
            ->orderByDesc('posts_count')
            ->get()
            ->map(fn (Tag $t) => [
                'name' => $t->name,
                'slug' => $t->slug,
                'description' => $t->description,
                'posts_count' => $t->posts_count,
            ]);
    }

    /** Header halaman arsip tag (/berita/tag/{slug}). */
    public function show(Tag $tag)
    {
        return [
            'name' => $tag->name,
            'slug' => $tag->slug,
            'description' => $tag->description,
        ];
    }
}
