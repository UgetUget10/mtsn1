<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GlobalTemplate;
use App\Support\Blocks\TreeResolver;

/**
 * Endpoint publik Theme Builder — dipakai frontend/src/app/[locale]/layout.tsx
 * untuk mengambil tree header/footer aktif, sejalan dengan getMenu()/getSettings().
 * Hanya baris `is_active=true` yang pernah dikembalikan; slot tanpa desain
 * aktif (belum pernah dipublish) mengembalikan tree kosong, bukan 404 — situs
 * publik tetap harus tampil (header/footer lama tetap dirender lewat fallback
 * di frontend) meski Theme Builder belum pernah dipakai.
 */
class GlobalTemplateController extends Controller
{
    public function show(string $slot)
    {
        $template = GlobalTemplate::where('slot', $slot)->where('is_active', true)->first();

        if (! $template) {
            return ['slot' => $slot, 'active' => false, 'tree' => ['schema' => 2, 'tree' => []]];
        }

        return [
            'slot' => $slot,
            'active' => true,
            'tree' => TreeResolver::resolve(['schema' => 2, 'tree' => $template->tree['tree'] ?? []]),
        ];
    }
}
