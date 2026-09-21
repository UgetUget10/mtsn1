<?php

namespace Database\Seeders;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Transkripsi struktur navigasi hardcoded frontend/src/lib/nav.ts (buildNav())
 * ke tabel menus/menu_items, agar dikelola dari admin tanpa kehilangan
 * struktur/urutan yang sudah ada. Jalankan sekali sebagai seed awal — setelah
 * ini, editor mengubah menu lewat panel, bukan lewat seeder ini lagi.
 */
class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menu = Menu::updateOrCreate(['key' => 'header'], ['label' => 'Navigasi Utama']);

        // Bersihkan item lama agar seed ini idempotent (aman dijalankan ulang
        // selama belum ada penyuntingan manual dari admin). Hapus langsung by
        // menu_id (bukan lewat delete() bertingkat pada relasi self-referential)
        // supaya semua level terhapus tuntas dalam satu query.
        DB::table('menu_items')->where('menu_id', $menu->id)->delete();

        $order = 0;

        MenuItem::create([
            'menu_id' => $menu->id, 'label' => 'Beranda', 'type' => 'custom_url',
            'url' => '/', 'order' => $order++, 'is_active' => true,
        ]);

        $profil = MenuItem::create([
            'menu_id' => $menu->id, 'label' => 'Profil', 'type' => 'section',
            'icon' => 'M12 3l8 4v5c0 5-3.4 8.5-8 9-4.6-.5-8-4-8-9V7l8-4z',
            'order' => $order++, 'is_active' => true,
        ]);
        $this->children($menu->id, $profil->id, [
            ['Profil & Visi Misi', '/profil'],
            ['Sambutan Kepala Madrasah', '/profil/sambutan'],
            ['Pendidik & Tenaga Kependidikan', '/guru'],
            ["Program Ma'had (Asrama)", '/mahad'],
        ]);

        $akademik = MenuItem::create([
            'menu_id' => $menu->id, 'label' => 'Akademik', 'type' => 'section',
            'icon' => 'M12 3 2 8l10 5 10-5-10-5zM4 10v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6',
            'order' => $order++, 'is_active' => true,
        ]);
        $this->children($menu->id, $akademik->id, [
            ['Ikhtisar Akademik', '/akademik'],
            ['Kurikulum', '/akademik/kurikulum'],
            ['Proses Pembelajaran', '/akademik/pembelajaran'],
            ['Penilaian & Rapor', '/akademik/penilaian'],
            ['Bimbingan Konseling', '/akademik/bk'],
            ['Program Unggulan', '/akademik/program-unggulan'],
            ['Aplikasi Digital', '/akademik/aplikasi'],
            ['Ekstrakurikuler', '/ekstrakurikuler'],
        ]);

        $informasi = MenuItem::create([
            'menu_id' => $menu->id, 'label' => 'Informasi', 'type' => 'section',
            'icon' => 'M4 4h16v14H5.2L4 19.2zM8 9h8M8 13h5',
            'order' => $order++, 'is_active' => true,
        ]);
        $this->children($menu->id, $informasi->id, [
            ['Berita & Pengumuman', '/berita'],
            ['Agenda Kegiatan', '/agenda'],
            ['Galeri Kegiatan', '/galeri'],
            ['Prestasi Madrasah', '/prestasi'],
            ['Dokumen & Unduhan', '/dokumen'],
        ]);

        // Frontend nav.ts membagi Layanan menjadi 2 kolom mega-menu ("Pelayanan
        // Publik" & "Zona Integritas") — dimodelkan sebagai sub-section (level 2)
        // yang masing-masing menaungi leaf-nya sendiri (level 3).
        $layanan = MenuItem::create([
            'menu_id' => $menu->id, 'label' => 'Layanan', 'type' => 'section',
            'icon' => 'M3 21h18M6 21V7l6-4 6 4v14M10 21v-5h4v5',
            'order' => $order++, 'is_active' => true,
        ]);
        $pelayananPublik = MenuItem::create([
            'menu_id' => $menu->id, 'parent_id' => $layanan->id, 'label' => 'Pelayanan Publik',
            'type' => 'section', 'order' => 0, 'is_active' => true,
        ]);
        $this->children($menu->id, $pelayananPublik->id, [
            ['Ikhtisar Layanan', '/layanan'],
            ['Standar Layanan', '/layanan/standar'],
            ['SOP PTSP', '/layanan/sop'],
            ['Maklumat Pelayanan', '/layanan/maklumat'],
            ['Survei Kepuasan', '/layanan/survei'],
            ['Pengaduan', '/layanan/pengaduan'],
            ['E-Repository', '/dokumen'],
        ]);
        $zonaIntegritas = MenuItem::create([
            'menu_id' => $menu->id, 'parent_id' => $layanan->id, 'label' => 'Zona Integritas',
            'type' => 'section', 'order' => 1, 'is_active' => true,
        ]);
        $this->children($menu->id, $zonaIntegritas->id, [
            ['Ikhtisar Zona Integritas', '/area-zi'],
            ['Menuju WBK / WBBM', '/area-zi/wbk'],
            ['Pengendalian Gratifikasi', '/area-zi/gratifikasi'],
            ['Whistleblowing (WBS)', '/area-zi/wbs'],
            ['LHKPN & LHKASN', '/area-zi/lhkpn'],
        ]);

        $ppdb = MenuItem::create([
            'menu_id' => $menu->id, 'label' => 'PPDB', 'type' => 'section',
            'icon' => 'M12 2a5 5 0 0 1 5 5v3H7V7a5 5 0 0 1 5-5zM5 10h14v10a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2z',
            'feature_title' => 'Pendaftaran Peserta Didik Baru',
            'feature_text' => 'Ikuti informasi jalur, jadwal, dan tautan pendaftaran resmi.',
            'feature_cta' => 'Buka halaman PPDB',
            'order' => $order++, 'is_active' => true,
        ]);
        $this->children($menu->id, $ppdb->id, [
            ['Informasi PPDB', '/ppdb'],
            ['Alur Pendaftaran', '/ppdb/alur'],
            ['Berkas Persyaratan', '/ppdb/berkas'],
            ['Jadwal & Pengumuman', '/ppdb/jadwal'],
        ]);

        MenuItem::create([
            'menu_id' => $menu->id, 'label' => 'Kontak', 'type' => 'custom_url',
            'url' => '/kontak', 'order' => $order++, 'is_active' => true,
        ]);
    }

    private function children(int $menuId, int $parentId, array $items): void
    {
        foreach ($items as $i => [$label, $url]) {
            MenuItem::create([
                'menu_id' => $menuId, 'parent_id' => $parentId, 'label' => $label,
                'type' => 'custom_url', 'url' => $url, 'order' => $i, 'is_active' => true,
            ]);
        }
    }
}
