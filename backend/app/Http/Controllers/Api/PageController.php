<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\Page;

class PageController extends Controller
{
    public function index()
    {
        // Peringatan penting: HasTranslations HANYA meng-override getAttribute()
        // (akses properti seperti $page->title), BUKAN toArray()/jsonSerialize().
        // Mengembalikan model langsung (atau lewat json_encode) akan membocorkan
        // seluruh array {"id":..,"en":..} mentah, bukan string sesuai locale
        // aktif. Selalu map eksplisit seperti di bawah, jangan `return $models;`.
        return Page::where('is_published', true)
            ->where('slug', '!=', Page::HOMEPAGE_SLUG)
            ->with('parent:id,slug')
            ->orderBy('order')
            ->get(['id', 'title', 'slug', 'order', 'parent_id'])
            ->map(fn (Page $p) => [
                'title' => $p->title,
                'slug' => $p->slug,
                'order' => $p->order,
                'parent' => $p->parent?->slug,
            ]);
    }

    public function show(Page $page)
    {
        abort_unless($page->is_published, 404);

        $page->load(['parent:id,slug,title', 'children:id,slug,title,parent_id,is_published,order']);

        return new PageResource($page);
    }
}
