<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Isi awal untuk halaman-halaman yang sebelumnya statis (hardcoded) di
 * frontend Next.js dan sekarang dikelola lewat Page + blocks (lihat
 * App\Support\Blocks\BlockTypes dan konversi di frontend/src/app/**).
 *
 * Slug di sini HARUS sama persis dengan slug yang dipakai `getPage()` di
 * masing-masing route Next.js — lihat komentar per halaman.
 */
class StaticPagesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->pages() as $slug => $page) {
            Page::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $page['title'],
                    'is_published' => true,
                    'order' => 0,
                    'blocks' => $page['blocks'],
                ],
            );
        }
    }

    private function cardGrid(?string $heading, int $columns, array $cards): array
    {
        return [
            'type' => 'card_grid',
            'data' => [
                'heading' => $heading,
                'columns' => $columns,
                'cards' => array_map(fn ($c) => [
                    'title' => $c['title'],
                    'description' => $c['description'] ?? null,
                    'icon' => $c['icon'] ?? null,
                    'image' => $c['image'] ?? null,
                    'href' => $c['href'] ?? null,
                ], $cards),
            ],
        ];
    }

    private function richText(?string $heading, string $body): array
    {
        return ['type' => 'rich_text', 'data' => ['heading' => $heading, 'body' => $body]];
    }

    private function hubGrid(bool $numbered, array $items): array
    {
        return [
            'type' => 'hub_grid',
            'data' => [
                'numbered' => $numbered,
                'items' => array_map(fn ($i) => [
                    'title' => $i['title'],
                    'desc' => $i['desc'] ?? null,
                    'href' => $i['href'],
                    'icon' => $i['icon'] ?? null,
                ], $items),
            ],
        ];
    }

    private function steps(?string $heading, array $items): array
    {
        return [
            'type' => 'steps',
            'data' => [
                'heading' => $heading,
                'items' => array_map(fn ($i) => [
                    'title' => $i['title'],
                    'description' => $i['description'] ?? null,
                ], $items),
            ],
        ];
    }

    private function checklist(?string $heading, array $texts): array
    {
        return [
            'type' => 'checklist',
            'data' => [
                'heading' => $heading,
                'items' => array_map(fn ($t) => ['text' => $t], $texts),
            ],
        ];
    }

    private function iconList(?string $heading, array $texts): array
    {
        return [
            'type' => 'icon_list',
            'data' => [
                'heading' => $heading,
                'items' => array_map(fn ($t) => ['text' => $t], $texts),
            ],
        ];
    }

    private function quote(string $text, ?string $attribution = null): array
    {
        return ['type' => 'quote', 'data' => ['text' => $text, 'attribution' => $attribution]];
    }

    private function table(?string $heading, array $columns, array $rows): array
    {
        return [
            'type' => 'table',
            'data' => [
                'heading' => $heading,
                'columns' => array_map(fn ($c) => ['label' => $c], $columns),
                'rows' => array_map(fn ($r) => [
                    'cells' => array_map(fn ($v) => ['value' => $v], $r),
                ], $rows),
            ],
        ];
    }

    private function linkCards(?string $heading, array $items): array
    {
        return [
            'type' => 'link_cards',
            'data' => [
                'heading' => $heading,
                'items' => array_map(fn ($i) => [
                    'title' => $i['title'],
                    'description' => $i['description'] ?? null,
                    'href' => $i['href'],
                    'external' => $i['external'] ?? false,
                ], $items),
            ],
        ];
    }

    /**
     * @return array<string, array{title: string, blocks: array}>
     */
    private function pages(): array
    {
        return [
            // ===== Akademik =====
            'akademik-index' => [
                'title' => 'Layanan Akademik',
                'blocks' => [
                    $this->hubGrid(true, [
                        ['title' => 'Kurikulum', 'desc' => 'Kurikulum Merdeka, muatan keagamaan, dan kelas program.', 'href' => '/akademik/kurikulum', 'icon' => 'M12 3 2 8l10 5 10-5-10-5zM4 10v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6'],
                        ['title' => 'Proses Pembelajaran', 'desc' => 'Kegiatan belajar, kalender akademik, dan pengembangan diri.', 'href' => '/akademik/pembelajaran', 'icon' => 'M8 2v4M16 2v4M3 10h18M5 6h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z'],
                        ['title' => 'Penilaian & Rapor', 'desc' => 'Asesmen formatif, sumatif, dan Rapor Digital (RDM).', 'href' => '/akademik/penilaian', 'icon' => 'M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zM9 13h6M9 17h6'],
                        ['title' => 'Bimbingan Konseling', 'desc' => 'Pendampingan pribadi, sosial, belajar, dan karier.', 'href' => '/akademik/bk', 'icon' => 'M20 21a8 8 0 0 0-16 0M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z'],
                        ['title' => 'Program Unggulan', 'desc' => 'Tahfidz, Olimpiade, Bilingual, dan CBI (akselerasi).', 'href' => '/akademik/program-unggulan', 'icon' => 'M12 3l8 4v5c0 5-3.4 8.5-8 9-4.6-.5-8-4-8-9V7l8-4z'],
                        ['title' => 'Aplikasi Digital', 'desc' => 'Perpustakaan digital, RDM, absensi, e-learning, SIPAMAD.', 'href' => '/akademik/aplikasi', 'icon' => 'M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11'],
                    ]),
                ],
            ],
            'akademik-aplikasi' => [
                'title' => 'Aplikasi Digital Madrasah',
                'blocks' => [
                    $this->cardGrid(null, 4, [
                        ['title' => 'Perpustakaan Digital', 'icon' => 'M4 5a2 2 0 0 1 2-2h9v18H6a2 2 0 0 1-2-2zM15 3h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-3'],
                        ['title' => 'Rapor Digital (RDM)', 'icon' => 'M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zM9 13h6M9 17h6'],
                        ['title' => 'Absensi Digital', 'icon' => 'M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11'],
                        ['title' => 'E-Learning', 'icon' => 'M2 3h20v14H2zM8 21h8M12 17v4'],
                        ['title' => 'SIPAMAD', 'icon' => 'M12 2l3 7h7l-5.5 4 2 7L12 17l-6.5 5 2-7L2 9h7z'],
                    ]),
                ],
            ],
            'akademik-bk' => [
                'title' => 'Bimbingan & Konseling',
                'blocks' => [
                    $this->checklist(null, [
                        'Bimbingan pribadi & sosial',
                        'Bimbingan belajar',
                        'Bimbingan karier & studi lanjut',
                        'Konseling individu / kelompok',
                        'Layanan alih tangan kasus (referal)',
                    ]),
                ],
            ],
            'akademik-kurikulum' => [
                'title' => 'Kurikulum',
                'blocks' => [
                    $this->cardGrid(null, 2, [
                        ['title' => 'Kurikulum Merdeka', 'description' => 'Diterapkan bertahap dengan penguatan profil pelajar Rahmatan lil ‘Alamin.'],
                        ['title' => 'Muatan Keagamaan', 'description' => "Al-Qur'an Hadis, Akidah Akhlak, Fikih, SKI, dan Bahasa Arab."],
                        ['title' => 'Kelas Program', 'description' => 'Tahfidz, Olimpiade/Sains, Bilingual, dan CBI (akselerasi).'],
                        ['title' => 'Ekstrakurikuler', 'description' => 'Wajib (Pramuka) dan pilihan sesuai minat–bakat.'],
                    ]),
                ],
            ],
            'akademik-pembelajaran' => [
                'title' => 'Proses & Kegiatan Pembelajaran',
                'blocks' => [
                    $this->richText(null, '<p>Kegiatan belajar mengajar berlangsung pada hari kerja dengan pendekatan pembelajaran berdiferensiasi. Kalender akademik, jadwal kegiatan, dan pengumuman disampaikan melalui kanal resmi madrasah.</p>'),
                    $this->cardGrid(null, 4, [
                        ['title' => 'Kalender & Agenda', 'description' => 'Jadwal kegiatan, kalender akademik, dan pengumuman belajar mengajar.', 'href' => '/agenda', 'icon' => 'M8 2v4M16 2v4M3 10h18M5 6h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z'],
                        ['title' => 'Kesiswaan & Ekstrakurikuler', 'description' => 'Pembinaan karakter, OSIS, dan wadah pengembangan minat–bakat.', 'href' => '/ekstrakurikuler', 'icon' => 'M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z'],
                        ['title' => 'Prestasi Akademik', 'description' => 'Capaian siswa pada olimpiade, kompetisi sains, dan lomba.', 'href' => '/prestasi', 'icon' => 'M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0zM7 6H4v2a3 3 0 0 0 3 3M17 6h3v2a3 3 0 0 1-3 3'],
                        ['title' => 'Berita Akademik', 'description' => 'Kabar kegiatan pembelajaran, penilaian, dan pengumuman resmi.', 'href' => '/berita', 'icon' => 'M4 4h16v16H4zM8 8h8M8 12h8M8 16h5'],
                    ]),
                ],
            ],
            'akademik-penilaian' => [
                'title' => 'Penilaian & Rapor',
                'blocks' => [
                    $this->richText(null, '<p>Penilaian dilakukan secara menyeluruh melalui asesmen formatif dan sumatif. Hasil belajar dilaporkan setiap tengah dan akhir semester melalui rapor digital (RDM) serta pertemuan dengan orang tua.</p>'),
                    $this->cardGrid(null, 3, [
                        ['title' => 'Asesmen Formatif', 'description' => 'Umpan balik berkelanjutan selama proses belajar.'],
                        ['title' => 'Asesmen Sumatif', 'description' => 'Penilaian capaian pada akhir lingkup materi / semester.'],
                        ['title' => 'Rapor Digital (RDM)', 'description' => 'Laporan hasil belajar yang dapat diakses orang tua.'],
                    ]),
                ],
            ],
            'akademik-program-unggulan' => [
                'title' => 'Program Unggulan',
                'blocks' => [
                    $this->cardGrid(null, 4, [
                        ['title' => 'Kelas Tahfidz', 'description' => "Tahfidz — Program hafalan Al-Qur'an bertingkat dengan pembimbing khusus dan target hafalan terukur."],
                        ['title' => 'Kelas Olimpiade', 'description' => 'Sains — Pembinaan intensif menuju kompetisi sains dan matematika tingkat nasional serta internasional.'],
                        ['title' => 'Kelas Bilingual', 'description' => 'Bahasa — Penguatan Bahasa Arab dan Inggris sebagai pengantar sebagian mata pelajaran.'],
                        ['title' => 'Kelas CBI', 'description' => 'Akselerasi — Cerdas Berbakat Istimewa — program percepatan penyelesaian studi dalam dua tahun.'],
                    ]),
                ],
            ],

            // ===== Layanan =====
            'layanan-index' => [
                'title' => 'Layanan Publik',
                'blocks' => [
                    $this->hubGrid(true, [
                        ['title' => 'Standar Layanan', 'desc' => 'Jenis layanan, persyaratan, waktu, dan biaya.', 'href' => '/layanan/standar', 'icon' => 'M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11'],
                        ['title' => 'SOP PTSP', 'desc' => 'Alur Pelayanan Terpadu Satu Pintu.', 'href' => '/layanan/sop', 'icon' => 'M3 21h18M6 21V7l6-4 6 4v14M10 21v-5h4v5'],
                        ['title' => 'Maklumat Pelayanan', 'desc' => 'Janji layanan madrasah kepada masyarakat.', 'href' => '/layanan/maklumat', 'icon' => 'M4 4h16v12H5.2L4 17.2zM8 9h8M8 12h5'],
                        ['title' => 'Survei Kepuasan', 'desc' => 'SKM / SPKP dan Survei Persepsi Anti Korupsi.', 'href' => '/layanan/survei', 'icon' => 'M3 3v18h18M7 15l3-3 3 3 5-6'],
                        ['title' => 'Pengaduan Masyarakat', 'desc' => 'Kanal pengaduan resmi & SP4N-LAPOR.', 'href' => '/layanan/pengaduan', 'icon' => 'M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z'],
                        ['title' => 'E-Repository', 'desc' => 'Dokumen, formulir, dan panduan yang dapat diunduh.', 'href' => '/dokumen', 'icon' => 'M4 5a2 2 0 0 1 2-2h9v18H6a2 2 0 0 1-2-2zM15 3h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-3'],
                    ]),
                ],
            ],
            'layanan-standar' => [
                'title' => 'Standar Layanan',
                'blocks' => [
                    $this->table(null, ['Jenis Layanan', 'Persyaratan', 'Waktu', 'Biaya'], [
                        ['Legalisir & Surat Keterangan', 'KTP/KK pemohon, surat permohonan', '1–2 hari kerja', 'Gratis'],
                        ['Surat Keterangan Aktif Siswa', 'Kartu pelajar, permohonan wali', '1 hari kerja', 'Gratis'],
                        ['Mutasi Masuk / Keluar', 'Rapor, surat pindah, KK', '3–5 hari kerja', 'Gratis'],
                        ['Peminjaman Sarana', 'Surat permohonan lembaga', '2 hari kerja', 'Sesuai ketentuan'],
                        ['Permohonan Informasi Publik', 'Formulir permohonan (PPID)', '10 hari kerja', 'Gratis'],
                    ]),
                ],
            ],
            'layanan-sop' => [
                'title' => 'SOP Pelayanan Terpadu Satu Pintu',
                'blocks' => [
                    $this->steps(null, [
                        ['title' => 'Ambil Nomor Antrean', 'description' => 'Datang ke loket PTSP atau ajukan permohonan daring.'],
                        ['title' => 'Verifikasi Berkas', 'description' => 'Petugas memeriksa kelengkapan dan keabsahan dokumen.'],
                        ['title' => 'Proses Layanan', 'description' => 'Permohonan diproses sesuai jenis dan standar waktu layanan.'],
                        ['title' => 'Penyerahan Hasil', 'description' => 'Hasil layanan diserahkan langsung atau dikirim daring.'],
                    ]),
                ],
            ],
            'layanan-maklumat' => [
                'title' => 'Maklumat Pelayanan',
                'blocks' => [
                    $this->quote('Dengan ini kami menyatakan sanggup menyelenggarakan pelayanan sesuai standar pelayanan yang telah ditetapkan, dan apabila tidak menepati janji ini, kami siap menerima sanksi sesuai peraturan perundang-undangan yang berlaku.'),
                ],
            ],
            'layanan-pengaduan' => [
                'title' => 'Pengaduan Masyarakat',
                'blocks' => [
                    $this->linkCards(null, [
                        ['title' => 'Formulir Pengaduan', 'description' => 'Kirim pengaduan langsung ke madrasah.', 'href' => '/kontak', 'external' => false],
                        ['title' => 'SP4N-LAPOR!', 'description' => 'Kanal pengaduan nasional pemerintah.', 'href' => 'https://www.lapor.go.id/', 'external' => true],
                        ['title' => 'Whistleblowing System', 'description' => 'Laporan dugaan pelanggaran secara rahasia.', 'href' => '/area-zi/wbs', 'external' => false],
                    ]),
                ],
            ],

            // ===== Area Zona Integritas =====
            'area-zi-index' => [
                'title' => 'Area Zona Integritas',
                'blocks' => [
                    $this->hubGrid(true, [
                        ['title' => 'Menuju WBK / WBBM', 'desc' => 'Enam area perubahan & maklumat integritas.', 'href' => '/area-zi/wbk', 'icon' => 'M12 3l8 4v5c0 5-3.4 8.5-8 9-4.6-.5-8-4-8-9V7l8-4zM9 12l2 2 4-4'],
                        ['title' => 'Pengendalian Gratifikasi', 'desc' => 'Pelaporan penerimaan gratifikasi kepada UPG.', 'href' => '/area-zi/gratifikasi', 'icon' => 'M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6'],
                        ['title' => 'Whistleblowing (WBS)', 'desc' => 'Kanal pelaporan dugaan pelanggaran secara rahasia.', 'href' => '/area-zi/wbs', 'icon' => 'M3 11l19-9-9 19-2-8-8-2z'],
                        ['title' => 'LHKPN & LHKASN', 'desc' => 'Kepatuhan pelaporan harta kekayaan pejabat & ASN.', 'href' => '/area-zi/lhkpn', 'icon' => 'M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zM9 13h6M9 17h6'],
                    ]),
                ],
            ],
            'area-zi-wbk' => [
                'title' => 'Menuju WBK & WBBM',
                'blocks' => [
                    $this->richText(null, '<p>Seluruh pendidik dan tenaga kependidikan menandatangani pakta integritas serta menerapkan enam area perubahan sebagai upaya nyata mencegah korupsi dan meningkatkan mutu layanan.</p>'),
                    $this->cardGrid(null, 3, [
                        ['title' => 'Manajemen Perubahan', 'description' => 'Membangun komitmen dan budaya kerja berintegritas di seluruh warga madrasah.'],
                        ['title' => 'Penataan Tatalaksana', 'description' => 'Menyederhanakan prosedur dan mendorong pemanfaatan sistem elektronik.'],
                        ['title' => 'Penataan Sistem Manajemen SDM', 'description' => 'Pengelolaan pegawai yang objektif, transparan, dan berbasis kinerja.'],
                        ['title' => 'Penguatan Akuntabilitas', 'description' => 'Perencanaan dan pelaporan kinerja yang terukur serta dapat dipertanggungjawabkan.'],
                        ['title' => 'Penguatan Pengawasan', 'description' => 'Pengendalian gratifikasi, benturan kepentingan, dan whistleblowing system.'],
                        ['title' => 'Peningkatan Kualitas Pelayanan Publik', 'description' => 'Standar pelayanan yang jelas, ramah, dan responsif terhadap kebutuhan masyarakat.'],
                    ]),
                    $this->quote('Kami segenap keluarga besar MTsN 1 Kota Malang menyatakan siap membangun Zona Integritas, menolak segala bentuk korupsi, kolusi, nepotisme, dan gratifikasi, serta memberikan pelayanan terbaik tanpa diskriminasi kepada seluruh masyarakat.'),
                ],
            ],
            'area-zi-gratifikasi' => [
                'title' => 'Pengendalian Gratifikasi',
                'blocks' => [
                    $this->richText(null, '<p>Unit Pengendalian Gratifikasi (UPG) madrasah menerima dan menindaklanjuti laporan gratifikasi dari pegawai. Pelaporan dapat disampaikan paling lambat 7 (tujuh) hari kerja sejak penerimaan, melalui UPG madrasah atau langsung ke aplikasi GOL milik KPK.</p>'),
                    $this->iconList(null, [
                        'Tolak gratifikasi yang berhubungan dengan jabatan sejak awal.',
                        'Bila tidak dapat ditolak, laporkan ke UPG madrasah.',
                        'UPG meneruskan laporan ke KPK untuk penetapan status kepemilikan.',
                    ]),
                ],
            ],
            'area-zi-wbs' => [
                'title' => 'Whistleblowing System (WBS)',
                'blocks' => [
                    $this->iconList('Yang dapat dilaporkan', [
                        'Korupsi, kolusi, dan nepotisme',
                        'Penyalahgunaan wewenang / jabatan',
                        'Benturan kepentingan',
                        'Pelanggaran kode etik & disiplin pegawai',
                        'Pungutan liar dan gratifikasi',
                    ]),
                ],
            ],
            'area-zi-lhkpn' => [
                'title' => 'LHKPN & LHKASN',
                'blocks' => [
                    $this->cardGrid(null, 2, [
                        ['title' => 'LHKPN', 'description' => 'Laporan Harta Kekayaan Penyelenggara Negara — bagi Kepala Madrasah dan pejabat wajib lapor, disampaikan ke KPK.', 'href' => 'https://elhkpn.kpk.go.id/'],
                        ['title' => 'LHKASN', 'description' => 'Laporan Harta Kekayaan Aparatur Sipil Negara — bagi ASN selain wajib LHKPN, disampaikan melalui Inspektorat Jenderal Kemenag.', 'href' => 'https://siharka.menpan.go.id/'],
                    ]),
                ],
            ],

            // ===== PPDB (hanya bagian statis; badge/tombol/deadline tetap di kode) =====
            'ppdb-alur' => [
                'title' => 'Alur Pendaftaran',
                'blocks' => [
                    $this->steps(null, [
                        ['title' => 'Buat akun & isi formulir', 'description' => 'Daftar pada portal PPDB dan lengkapi data diri calon peserta didik.'],
                        ['title' => 'Unggah berkas', 'description' => 'Siapkan hasil pindai dokumen sesuai ketentuan.'],
                        ['title' => 'Verifikasi', 'description' => 'Panitia memverifikasi berkas dan data pendaftaran.'],
                        ['title' => 'Pengumuman', 'description' => 'Hasil seleksi diumumkan melalui portal dan kanal resmi madrasah.'],
                    ]),
                ],
            ],
            'ppdb-berkas' => [
                'title' => 'Berkas Persyaratan',
                'blocks' => [
                    $this->checklist(null, [
                        'Kartu Keluarga',
                        'Akta Kelahiran',
                        'Rapor SD/MI',
                        'Pas foto terbaru',
                        'Sertifikat prestasi (bila ada)',
                        'Surat keterangan lulus / ijazah',
                    ]),
                ],
            ],
        ];
    }
}
