<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

/**
 * Arsip penulis ala WordPress (/author/{slug}). Hanya user yang menyalakan
 * "Profil publik" DAN sudah pernah menerbitkan berita yang bisa diakses.
 * Daftar berita per penulis diambil frontend lewat PostController@index
 * dengan `?author={slug}`.
 */
class AuthorController extends Controller
{
    /** Semua penulis publik (untuk sitemap / halaman "Redaksi"). */
    public function index()
    {
        return User::query()
            ->where('show_publicly', true)
            ->whereNotNull('slug')
            ->withCount(['posts as posts_count' => fn ($q) => $q->published()])
            ->having('posts_count', '>', 0)
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => $this->card($u));
    }

    public function show(User $user)
    {
        abort_unless($user->isPublicAuthor(), 404);

        $user->loadCount(['posts as posts_count' => fn ($q) => $q->published()]);

        return $this->card($user) + [
            'bio' => $user->bio,
            'social' => array_filter($user->social ?? []),
        ];
    }

    /** @return array<string, mixed> */
    private function card(User $u): array
    {
        return [
            'name' => $u->name,
            'slug' => $u->slug,
            'job_title' => $u->job_title,
            'avatar' => $u->avatarUrl(),
            'posts_count' => $u->posts_count ?? 0,
        ];
    }
}
