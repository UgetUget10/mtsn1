<?php

namespace App\Support\Widgets;

/**
 * Zona widget yang benar-benar dirender frontend — setara daftar "Widget
 * Areas" yang tema WordPress daftarkan lewat register_sidebar(). Menambah
 * zona baru berarti DUA sisi harus disentuh: tambah key di sini (dipakai
 * WidgetAreaResource untuk seed & validasi), lalu render pemanggilannya di
 * komponen frontend terkait (lihat komentar tiap key).
 */
class WidgetAreaKeys
{
    public const SIDEBAR_BERITA = 'sidebar-berita';

    public const FOOTER = 'footer';

    /** @return array<string, array{label: string, description: string}> */
    public static function all(): array
    {
        return [
            self::SIDEBAR_BERITA => [
                'label' => 'Sidebar Halaman Berita',
                'description' => 'Tampil di kolom kanan setiap artikel berita, di bawah Daftar Isi. Frontend: app/[locale]/berita/[slug]/page.tsx.',
            ],
            self::FOOTER => [
                'label' => 'Kaki Situs (Footer)',
                'description' => 'Tampil di footer, di semua halaman. Frontend: components/site-footer.tsx.',
            ],
        ];
    }
}
