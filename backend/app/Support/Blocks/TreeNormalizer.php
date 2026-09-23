<?php

namespace App\Support\Blocks;

use Illuminate\Support\Str;

/**
 * Menormalkan konten kanvas visual (App\Filament\Pages\PageCanvasEditor) ke
 * bentuk tree `{schema:2, tree:[...]}`, apa pun bentuk aslinya:
 * - Sudah schema 2 → dikembalikan apa adanya.
 * - Array blok datar lama (bentuk `blocks`/`blocks_draft` sebelum kanvas ada,
 *   sama seperti yang dibaca Page::visibleBlocks()) → dibungkus otomatis jadi
 *   satu section berisi satu column, supaya konten lama tetap tampil dan bisa
 *   langsung disunting di kanvas tanpa migrasi data. Paralel dengan
 *   Page::visibleBlocks() yang membungkus `body` jadi block rich_text.
 * - Kosong/null → tree kosong.
 */
class TreeNormalizer
{
    /**
     * @param  array<int, array<string, mixed>>|array{schema:int, tree:array}|null  $content
     * @return array{schema: int, tree: array<int, array<string, mixed>>}
     */
    public static function normalize(?array $content): array
    {
        if (empty($content)) {
            return ['schema' => 2, 'tree' => []];
        }

        if (($content['schema'] ?? null) === 2 && isset($content['tree'])) {
            return ['schema' => 2, 'tree' => self::sanitizeStyles($content['tree'])];
        }

        // Bentuk lama: array blok datar `[{type, data}, ...]`.
        $leaves = collect($content)
            ->filter(fn ($block) => is_array($block) && isset($block['type']))
            ->map(fn (array $block) => [
                'id' => 'wid_'.Str::random(8),
                'type' => $block['type'],
                'data' => $block['data'] ?? [],
            ])
            ->values()
            ->all();

        if (empty($leaves)) {
            return ['schema' => 2, 'tree' => []];
        }

        return [
            'schema' => 2,
            'tree' => [[
                'id' => 'sec_'.Str::random(8),
                'type' => BlockTypes::SECTION,
                'children' => [[
                    'id' => 'col_'.Str::random(8),
                    'type' => BlockTypes::COLUMN,
                    'style' => ['base' => ['width' => 12]],
                    'children' => $leaves,
                ]],
            ]],
        ];
    }

    /**
     * Kebalikan dari normalize() untuk kolom `blocks` published (Phase 1):
     * tree section/column diratakan kembali jadi array blok datar
     * `[{type, data}, ...]`, urutan dipertahankan depth-first, `id`/`style`
     * dibuang (tidak dikenal bentuk lama). Dipakai App\Http\Controllers\
     * Admin\PageCanvasController::publish() — publik masih membaca `blocks`
     * datar sampai Phase 2 menambahkan render tree di frontend produksi.
     *
     * @param  array{schema:int, tree:array<int, array<string, mixed>>}  $tree
     * @return array<int, array{type: string, data: array<string, mixed>}>
     */
    public static function flatten(array $tree): array
    {
        return self::flattenNodes($tree['tree'] ?? []);
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<int, array{type: string, data: array<string, mixed>}>
     */
    private static function flattenNodes(array $nodes): array
    {
        $leaves = [];

        foreach ($nodes as $node) {
            if (in_array($node['type'] ?? null, BlockTypes::containers(), true)) {
                array_push($leaves, ...self::flattenNodes($node['children'] ?? []));

                continue;
            }

            $leaves[] = ['type' => $node['type'], 'data' => $node['data'] ?? []];
        }

        return $leaves;
    }

    /**
     * Bersihkan `style` di setiap node lewat StyleResolver, rekursif — jalan
     * setiap kali tree schema:2 dinormalisasi, jadi nilai tak tervalidasi
     * yang lolos dari client manapun (bug SPA, request manual, dst) tidak
     * pernah tersimpan/terbaca sebagai style aktif.
     *
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<int, array<string, mixed>>
     */
    private static function sanitizeStyles(array $nodes): array
    {
        foreach ($nodes as &$node) {
            if (isset($node['style'])) {
                $node['style'] = StyleResolver::sanitize($node['style']);
            }
            if (! empty($node['children'])) {
                $node['children'] = self::sanitizeStyles($node['children']);
            }
        }

        return $nodes;
    }
}
