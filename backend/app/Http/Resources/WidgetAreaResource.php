<?php

namespace App\Http\Resources;

use App\Support\Blocks\BlockDataResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Isi satu zona widget siap-render — daftar blok terurut (memakai bentuk
 * data yang SAMA dengan blocks halaman biasa, lihat App\Http\Resources\
 * PageResource) supaya frontend bisa memakai renderer blok yang sama untuk
 * keduanya, bukan komponen terpisah.
 */
class WidgetAreaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'key' => $this->key,
            'blocks' => $this->activeBlocks
                ->flatMap(fn ($reusable) => collect($reusable->content ?? [])
                    ->filter(fn ($b) => ($b['is_visible'] ?? true) === true))
                ->map(fn (array $block) => [
                    'type' => $block['type'],
                    'data' => BlockDataResolver::resolve($block['type'], $block['data'] ?? []),
                ])
                ->values()
                ->all(),
        ];
    }
}
