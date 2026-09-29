<?php

namespace App\Support\Blocks;

use App\Models\ReusableBlock;

/**
 * Resolusi tree kanvas visual untuk konsumsi publik — tiap leaf lewat
 * BlockDataResolver (path media → URL, dst.) dan block `reusable` diekspansi
 * jadi isi ReusableBlock terkait. Diekstrak dari App\Http\Resources\PageResource
 * supaya App\Models\GlobalTemplate (header/footer) bisa memakai logika yang
 * sama tanpa duplikasi — keduanya sama-sama tree {schema, tree} dari
 * TreeNormalizer.
 */
class TreeResolver
{
    /**
     * @param  array{schema: int, tree: array<int, array<string, mixed>>}  $tree
     * @return array{schema: int, tree: array<int, array<string, mixed>>}
     */
    public static function resolve(array $tree): array
    {
        $tree['tree'] = collect($tree['tree'])
            ->map(fn (array $node) => self::resolveNode($node))
            ->all();

        return $tree;
    }

    /**
     * @param  array<string, mixed>  $node
     * @return array<string, mixed>
     */
    private static function resolveNode(array $node): array
    {
        if (in_array($node['type'] ?? null, BlockTypes::containers(), true)) {
            $node['children'] = collect($node['children'] ?? [])
                ->flatMap(fn (array $child) => in_array($child['type'] ?? null, BlockTypes::containers(), true)
                    ? [$child]
                    : self::expandReusable($child))
                ->map(fn (array $child) => self::resolveNode($child))
                ->all();

            return $node;
        }

        return self::resolveLeaf($node);
    }

    /**
     * @param  array<string, mixed>  $leaf
     * @return array<string, mixed>
     */
    private static function resolveLeaf(array $leaf): array
    {
        $leaf['data'] = BlockDataResolver::resolve($leaf['type'], $leaf['data'] ?? []);

        return $leaf;
    }

    /**
     * @param  array<string, mixed>  $block
     * @return array<int, array<string, mixed>>
     */
    public static function expandReusable(array $block): array
    {
        if (($block['type'] ?? null) !== BlockTypes::REUSABLE) {
            return [$block];
        }

        $slug = $block['data']['slug'] ?? null;
        if (! $slug) {
            return [];
        }

        $reusable = ReusableBlock::where('slug', $slug)->where('is_active', true)->first();
        if (! $reusable) {
            return [];
        }

        return collect($reusable->content ?? [])
            ->filter(fn ($b) => ($b['is_visible'] ?? true) === true)
            ->values()
            ->all();
    }
}
