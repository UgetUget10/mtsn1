<?php

namespace App\Filament\Concerns;

use Illuminate\Support\Facades\Auth;

/**
 * Autosave berkala ala WordPress (wp_autosave, tiap ~1 menit) — mencegah draf
 * panjang hilang kalau tab/browser crash sebelum sempat klik "Simpan".
 *
 * BEDA dari App\Models\Concerns\HasRevisions (snapshot saat model BENAR-BENAR
 * disimpan): trait ini menulis Revision langsung dari state form yang belum
 * disimpan, TANPA menyentuh baris record di DB — status/slug/published_at
 * tidak berubah, dan draf yang sedang diketik tidak bisa "menerbitkan diri
 * sendiri" secara tidak sengaja lewat polling.
 *
 * Dipakai di Page Edit* (mis. EditPost, EditPage): tambahkan
 * `use HasAutosave;` lalu definisikan `autosaveFields(): array` (subset kolom
 * form yang mau di-autosave — biasanya title/excerpt/body/blocks).
 */
trait HasAutosave
{
    protected ?string $lastAutosaveHash = null;

    /** Interval polling (detik) — cukup jarang agar tidak membebani server. */
    protected function autosaveIntervalSeconds(): int
    {
        return 90;
    }

    /** @return array<int, string> Kunci dot-notation pada $this->data yang mau diautosave. */
    abstract protected function autosaveFields(): array;

    /**
     * Dipanggil lewat wire:poll dari Blade view halaman edit. Tidak melakukan
     * apa pun bila tidak ada perubahan sejak autosave terakhir (dibandingkan
     * lewat hash) — supaya tidak menumpuk revisi kosong berulang.
     */
    public function autosave(): void
    {
        $record = $this->getRecord();
        if (! $record || ! method_exists($record, 'revisions')) {
            return;
        }

        $snapshot = [];
        foreach ($this->autosaveFields() as $field) {
            $snapshot[$field] = data_get($this->data, $field);
        }

        $hash = md5(json_encode($snapshot));
        if ($hash === $this->lastAutosaveHash) {
            return; // tidak ada perubahan sejak autosave terakhir
        }

        $record->revisions()->create([
            'user_id' => Auth::id(),
            'data' => $snapshot,
            'reason' => 'autosave',
        ]);

        if (method_exists($record, 'pruneRevisions')) {
            $record->pruneRevisions();
        }

        $this->lastAutosaveHash = $hash;
    }

    public function getFooter(): ?\Illuminate\Contracts\View\View
    {
        return view('filament.concerns.autosave-poll', ['seconds' => $this->autosaveIntervalSeconds()]);
    }
}
