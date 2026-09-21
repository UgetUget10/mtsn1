<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\MenuItem;

class MenuController extends Controller
{
    public function show(string $key)
    {
        $menu = Menu::where('key', $key)->firstOrFail();

        // Pohon menu maksimal 3 level (section > sub-section > leaf) — eager-load
        // eksplisit dua tingkat ke bawah agar tidak N+1, cukup untuk kedalaman ini.
        // Relasi acuan konten yang perlu di-eager-load di tiap tingkat pohon
        // (page/post/category/tag → slug untuk membangun URL).
        $refs = ['page:id,slug', 'post:id,slug', 'category:id,slug', 'tag:id,slug'];

        $menu->load([
            'items' => fn ($q) => $q->where('is_active', true)->with([
                ...$refs,
                'children' => fn ($c) => $c->where('is_active', true)->with([
                    ...$refs,
                    'children' => fn ($cc) => $cc->where('is_active', true)->with($refs),
                ]),
            ]),
        ]);

        return [
            'key' => $menu->key,
            'label' => $menu->label,
            // Buang item bertautan mati (mis. halaman tujuannya sudah dihapus)
            // supaya frontend tak pernah menerima href null.
            'items' => $menu->items
                ->filter(fn (MenuItem $item) => $item->isRenderable())
                ->map(fn (MenuItem $item) => $this->transform($item))
                ->values(),
        ];
    }

    private function transform(MenuItem $item): array
    {
        return [
            'label' => $item->label,
            'type' => $item->type,
            'href' => $item->resolvedUrl(),
            'icon' => $item->icon,
            'feature' => $item->feature_title ? [
                'title' => $item->feature_title,
                'text' => $item->feature_text,
                'cta' => $item->feature_cta,
            ] : null,
            // Filter juga di setiap tingkat anak — submenu bertautan mati sama
            // merusaknya dengan item tingkat atas.
            'children' => $item->children
                ->filter(fn (MenuItem $c) => $c->isRenderable())
                ->map(fn (MenuItem $c) => $this->transform($c))
                ->values(),
        ];
    }
}
