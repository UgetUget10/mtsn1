<?php

namespace App\Support\Revisions;

use App\Models\Revision;
use Illuminate\Database\Eloquent\Model;

/**
 * Membandingkan sebuah revisi dengan isi konten saat ini — setara layar
 * "Compare revisions" WordPress (wp_text_diff), tapi disederhanakan: diff
 * per-kata dengan LCS, dirender sebagai HTML bertanda <ins>/<del>.
 */
class RevisionDiff
{
    /**
     * Bandingkan snapshot revisi dengan nilai model sekarang.
     *
     * @return array<int, array{field: string, label: string, changed: bool, html: string}>
     */
    public static function against(Revision $revision, Model $current): array
    {
        $rows = [];

        foreach (($revision->data ?? []) as $field => $oldRaw) {
            $newRaw = $current->getRawOriginal($field);

            $old = self::normalise($oldRaw);
            $new = self::normalise($newRaw);

            $rows[] = [
                'field' => $field,
                'label' => self::label($field),
                'changed' => $old !== $new,
                'html' => $old === $new
                    ? '<p class="text-sm text-gray-500 dark:text-gray-400">Tidak ada perubahan.</p>'
                    : self::wordDiff($old, $new),
            ];
        }

        // Tampilkan yang berubah lebih dulu — seperti WP yang menyembunyikan
        // field identik di balik "Show all fields".
        usort($rows, fn ($a, $b) => ($b['changed'] <=> $a['changed']));

        return $rows;
    }

    /**
     * Ubah nilai kolom mentah jadi teks polos yang enak dibandingkan.
     *
     * Kolom translatable disimpan sebagai JSON {"id": "...", "en": "..."} oleh
     * spatie/laravel-translatable; ambil semua locale agar perubahan di tab
     * bahasa mana pun tetap terlihat.
     */
    private static function normalise(mixed $raw): string
    {
        if ($raw === null) {
            return '';
        }

        $value = $raw;

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $value = $decoded;
            }
        }

        if (is_array($value)) {
            $parts = [];
            foreach ($value as $k => $v) {
                $inner = is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string) $v;
                // Beri label locale supaya jelas bagian mana yang berubah.
                $parts[] = is_string($k) ? "[{$k}] {$inner}" : $inner;
            }
            $value = implode("\n", $parts);
        }

        $text = strip_tags((string) $value);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/[ \t]+/u', ' ', $text) ?? '');
    }

    /** Label ramah untuk nama kolom. */
    private static function label(string $field): string
    {
        return match ($field) {
            'title' => 'Judul',
            'excerpt' => 'Ringkasan',
            'body' => 'Isi',
            'blocks' => 'Blok konten',
            'meta_description' => 'Meta description',
            'template' => 'Template',
            default => ucfirst(str_replace('_', ' ', $field)),
        };
    }

    /**
     * Diff per-kata memakai LCS, dirender jadi HTML.
     *
     * Kata yang dihapus dibungkus <del>, yang ditambahkan <ins>; sisanya
     * teks biasa — sama seperti tampilan diff WordPress.
     */
    public static function wordDiff(string $old, string $new): string
    {
        $a = self::tokenise($old);
        $b = self::tokenise($new);

        $lcs = self::lcsTable($a, $b);

        $out = '';
        $i = 0;
        $j = 0;
        $na = count($a);
        $nb = count($b);

        while ($i < $na && $j < $nb) {
            if ($a[$i] === $b[$j]) {
                $out .= e($a[$i]).' ';
                $i++;
                $j++;
            } elseif ($lcs[$i + 1][$j] >= $lcs[$i][$j + 1]) {
                $out .= '<del>'.e($a[$i]).'</del> ';
                $i++;
            } else {
                $out .= '<ins>'.e($b[$j]).'</ins> ';
                $j++;
            }
        }
        while ($i < $na) {
            $out .= '<del>'.e($a[$i++]).'</del> ';
        }
        while ($j < $nb) {
            $out .= '<ins>'.e($b[$j++]).'</ins> ';
        }

        return trim($out) === '' ? '<p class="text-sm text-gray-500">(kosong)</p>' : $out;
    }

    /** @return array<int, string> */
    private static function tokenise(string $text): array
    {
        if (trim($text) === '') {
            return [];
        }

        return preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * Tabel panjang LCS. Dibatasi supaya dokumen sangat panjang tidak membuat
     * matriks raksasa (O(n·m) memori) — di atas batas, potong dan beri tahu.
     *
     * @param  array<int, string>  $a
     * @param  array<int, string>  $b
     * @return array<int, array<int, int>>
     */
    private static function lcsTable(array &$a, array &$b): array
    {
        $limit = 1200; // ~1200 kata per sisi sudah jauh di atas artikel biasa
        if (count($a) > $limit) {
            $a = array_slice($a, 0, $limit);
        }
        if (count($b) > $limit) {
            $b = array_slice($b, 0, $limit);
        }

        $na = count($a);
        $nb = count($b);

        $table = array_fill(0, $na + 1, array_fill(0, $nb + 1, 0));

        for ($i = $na - 1; $i >= 0; $i--) {
            for ($j = $nb - 1; $j >= 0; $j--) {
                $table[$i][$j] = $a[$i] === $b[$j]
                    ? $table[$i + 1][$j + 1] + 1
                    : max($table[$i + 1][$j], $table[$i][$j + 1]);
            }
        }

        return $table;
    }
}
