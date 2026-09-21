<?php

namespace App\Filament\Support;

use Closure;
use Illuminate\Database\Eloquent\Collection;

/**
 * Ringkasan dampak sebelum hapus permanen (force delete) — supaya admin
 * tidak menghapus buta. Force delete tidak bisa dibatalkan (beda dari trash
 * biasa yang masih bisa dipulihkan), jadi modal konfirmasinya perlu
 * menyebutkan berapa banyak lampiran (media/relasi) yang ikut lenyap.
 */
class ForceDeleteImpact
{
    /**
     * Bangun deskripsi modal force-delete untuk kumpulan record terpilih.
     * `$extras` opsional: peta label => closure yang menghitung jumlah item
     * terkait per record (mis. ['komentar' => fn ($r) => $r->comments()->count()]).
     *
     * @param  array<string, Closure>  $extras
     */
    public static function describe(Collection $records, array $extras = []): string
    {
        $count = $records->count();
        $label = $count === 1 ? '1 data' : "{$count} data";

        $lines = ["Anda akan menghapus PERMANEN {$label} — tindakan ini TIDAK BISA dibatalkan."];

        foreach ($extras as $itemLabel => $counter) {
            $total = $records->sum($counter);
            if ($total > 0) {
                $lines[] = "{$total} {$itemLabel} ikut terhapus.";
            }
        }

        return implode(' ', $lines);
    }
}
