<?php

namespace App\Filament\Concerns;

/**
 * Keterangan & kredit gambar sampul disimpan sebagai custom property pada
 * entri media library (`caption` / `credit`), BUKAN kolom di tabel posts —
 * supaya metadata ikut menempel pada berkasnya dan bisa dikelola juga lewat
 * Pustaka Media.
 *
 * Trait ini menjembatani form Filament: mengisi field saat form dibuka, lalu
 * menulis kembali ke media sesudah record tersimpan. Field `cover_caption` /
 * `cover_credit` harus di-`dehydrated(false)`-kan? Tidak — nilainya dibuang
 * dari $data sebelum save (lihat stripCoverMeta) agar Eloquent tidak mencoba
 * menyimpannya sebagai kolom yang tidak ada.
 */
trait SyncsCoverMeta
{
    /** Nama koleksi media yang memuat gambar sampul. */
    protected function coverCollection(): string
    {
        return 'cover';
    }

    /** Sisipkan nilai tersimpan ke dalam data form saat form dibuka. */
    protected function fillCoverMeta(array $data): array
    {
        $media = $this->getRecord()?->getFirstMedia($this->coverCollection());

        $data['cover_caption'] = $media?->getCustomProperty('caption');
        $data['cover_credit'] = $media?->getCustomProperty('credit');

        return $data;
    }

    /**
     * Buang field virtual dari payload sebelum Eloquent menyimpan — keduanya
     * bukan kolom tabel. Nilainya disimpan sementara untuk dipakai afterSave().
     */
    protected function stripCoverMeta(array $data): array
    {
        $this->pendingCoverCaption = $data['cover_caption'] ?? null;
        $this->pendingCoverCredit = $data['cover_credit'] ?? null;

        unset($data['cover_caption'], $data['cover_credit']);

        return $data;
    }

    protected ?string $pendingCoverCaption = null;

    protected ?string $pendingCoverCredit = null;

    /**
     * Tulis ke custom properties media. Dipanggil di afterSave()/afterCreate(),
     * yaitu SESUDAH berkas sampul selesai dipindahkan ke media library.
     */
    protected function syncCoverMeta(): void
    {
        $media = $this->getRecord()?->refresh()?->getFirstMedia($this->coverCollection());

        if (! $media) {
            return;
        }

        $caption = filled($this->pendingCoverCaption) ? $this->pendingCoverCaption : null;
        $credit = filled($this->pendingCoverCredit) ? $this->pendingCoverCredit : null;

        // Nilai kosong dibuang dari custom_properties supaya tidak menyisakan
        // kunci bernilai null (konsisten dengan EditMedia di Pustaka Media).
        $props = $media->custom_properties ?? [];

        if ($caption === null) {
            unset($props['caption']);
        } else {
            $props['caption'] = $caption;
        }

        if ($credit === null) {
            unset($props['credit']);
        } else {
            $props['credit'] = $credit;
        }

        $media->custom_properties = $props;
        $media->save();
    }
}
