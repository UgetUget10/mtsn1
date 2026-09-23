<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Http\Resources\PostResource;
use App\Models\Page;
use App\Models\Post;
use Illuminate\Http\Request;

/**
 * Pratinjau konten yang BELUM publish — setara `preview=true` + nonce di
 * WordPress. Diakses hanya bila `token` cocok dengan `preview_token` baris
 * tersebut, jadi tidak perlu login tapi tetap tidak bisa ditebak.
 *
 * Frontend Next.js memanggil ini dari route Draft Mode-nya.
 */
class PreviewController extends Controller
{
    public function post(Request $request, string $idOrSlug)
    {
        $post = Post::withTrashed()
            ->where(fn ($q) => $q->where('id', $idOrSlug)->orWhere('slug', $idOrSlug))
            ->firstOrFail();

        abort_unless(
            hash_equals((string) $post->preview_token, (string) $request->query('token')),
            403,
        );

        $post->load(['category', 'author', 'categories', 'tags']);

        // Pratinjau selalu menampilkan isi penuh, termasuk post terproteksi/privat.
        $request->attributes->set('post_unlocked', true);

        return (new PostResource($post))
            ->additional(['preview' => true, 'status' => $post->status]);
    }

    public function page(Request $request, string $idOrSlug)
    {
        $page = Page::withTrashed()
            ->where(fn ($q) => $q->where('id', $idOrSlug)->orWhere('slug', $idOrSlug))
            ->firstOrFail();

        abort_unless(
            hash_equals((string) $page->preview_token, (string) $request->query('token')),
            403,
        );

        $resource = new PageResource($page);
        // Sertakan `tree` (struktur section/kolom kanvas visual, lihat
        // App\Filament\Pages\PageCanvasEditor) hanya di jalur pratinjau —
        // endpoint publik biasa tidak butuh field ini.
        $resource->withTree = true;

        return $resource->additional(['preview' => true, 'is_published' => $page->is_published]);
    }
}
