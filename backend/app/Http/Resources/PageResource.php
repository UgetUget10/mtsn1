<?php

namespace App\Http\Resources;

use App\Support\Blocks\BlockDataResolver;
use App\Support\Blocks\TreeResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    /**
     * true di jalur preview kanvas (App\Http\Controllers\Api\PreviewController)
     * supaya `tree` ikut dikirim — endpoint publik biasa tidak butuh field ini.
     */
    public bool $withTree = false;

    public function toArray(Request $request): array
    {
        return [
            'title' => $this->title,
            'slug' => $this->slug,
            // Wp: Page Attributes → Template. Frontend memilih tata letak dari nilai ini.
            'template' => $this->resolvedTemplate(),
            'meta_description' => $this->meta_description,
            'updated_at' => $this->updated_at?->toIso8601String(),
            'seo' => $this->seo(),
            // Hierarki ala WordPress (Page Attributes → Parent).
            'parent' => $this->parent?->slug,
            'ancestors' => $this->ancestors()
                ->map(fn ($a) => ['title' => $a->title, 'slug' => $a->slug])
                ->values(),
            'children' => $this->children
                ->where('is_published', true)
                ->map(fn ($c) => ['title' => $c->title, 'slug' => $c->slug])
                ->values(),
            'blocks' => collect($this->visibleBlocks())
                ->flatMap(fn (array $block) => TreeResolver::expandReusable($block))
                ->map(fn (array $block) => [
                    'type' => $block['type'],
                    'data' => BlockDataResolver::resolve($block['type'], $block['data'] ?? []),
                ])
                ->all(),
            'tree' => $this->when($this->withTree, fn () => TreeResolver::resolve($this->visibleTree())),
        ];
    }

    private function seo(): array
    {
        $meta = $this->meta ?? [];

        return [
            'title' => $meta['seo_title'] ?? $this->title,
            'description' => $meta['seo_description'] ?? $this->meta_description,
            'canonical' => $meta['canonical'] ?? null,
            'noindex' => (bool) ($meta['noindex'] ?? false),
            // Path relatif dari FileUpload → URL absolut.
            'og_image' => BlockDataResolver::fileUrl($meta['og_image'] ?? null),
        ];
    }
}
