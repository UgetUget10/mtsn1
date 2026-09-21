<?php

namespace Database\Seeders;

use App\Models\Achievement;
use App\Models\Agenda;
use App\Models\Category;
use App\Models\Extracurricular;
use App\Models\Page;
use App\Models\Post;
use App\Models\Setting;
use App\Models\Slider;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@mtsn1.sch.id'],
            ['name' => 'Administrator', 'password' => Hash::make('password')],
        );

        $this->call(RoleSeeder::class);
        $this->call(StaticPagesSeeder::class);
        $this->call(MenuSeeder::class);

        $settings = [
            'site_name' => 'MTsN 1 Kota Malang',
            'site_tagline' => 'Madrasah Tsanawiyah Negeri 1 Kota Malang',
            'school_name' => 'Madrasah Tsanawiyah Negeri 1 Kota Malang',
            'npsn' => '20584524',
            'nsm' => '121135730001',
            'address' => 'Jl. Bandung No. 7, Kota Malang, Jawa Timur',
            'phone' => '(0341) 551169',
            'whatsapp' => '6281234567890',
            'email' => 'info@mtsn1.sch.id',
            'service_hours' => 'Senin–Jumat, 07.00–15.30 WIB',
            'facebook' => 'https://facebook.com/',
            'instagram' => 'https://instagram.com/',
            'youtube' => 'https://youtube.com/',
            'twitter' => '',
            'ppdb_url' => 'https://ppdb.kemenag.go.id/',
            'ppdb_deadline' => now()->addMonths(3)->toDateString(),
            'lms_url' => '',
            'ptsp_url' => '',
            'rdm_url' => '',
            'announcement' => '',
            'announcement_url' => '',
            'principal_name' => 'Drs. H. Contoh Kepala, M.Pd',
            'principal_word' => 'Selamat datang di website resmi MTsN 1 Kota Malang. Semoga menjadi media informasi dan komunikasi yang bermanfaat bagi seluruh warga madrasah dan masyarakat.',
            'maps_embed' => 'https://www.google.com/maps?q=Kota+Malang&output=embed',
            'maps_url' => 'https://maps.google.com/?q=Kota+Malang',
            'stat_students' => '803',
            'stat_teachers' => '56',
            'stat_staff' => '24',
            'stat_classes' => '30',
            'stat_accreditation' => 'A',
            'stat_alumni' => '10.000+',
        ];
        foreach ($settings as $k => $v) {
            Setting::updateOrCreate(['key' => $k], ['value' => $v]);
        }

        $categories = collect(['Berita', 'Pengumuman', 'Prestasi', 'Kegiatan'])
            ->mapWithKeys(fn ($name) => [$name => Category::firstOrCreate(['slug' => str($name)->slug()], ['name' => $name, 'type' => 'post'])]);

        foreach (range(1, 12) as $i) {
            $cat = $categories->random();
            Post::updateOrCreate(
                ['slug' => "contoh-berita-{$i}"],
                [
                    'title' => "Contoh Berita Nomor {$i}",
                    'category_id' => $cat->id,
                    'user_id' => $admin->id,
                    'excerpt' => 'Ringkasan singkat berita contoh untuk mengisi tampilan daftar berita pada halaman depan website.',
                    'body' => '<p>Ini adalah isi berita contoh. Ganti konten ini melalui panel admin Filament.</p><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>',
                    'status' => 'published',
                    'is_featured' => $i <= 3,
                    'published_at' => now()->subDays($i),
                ],
            );
        }

        foreach ([
            ['Profil Madrasah', 'Sejarah singkat dan gambaran umum madrasah.'],
            ['Visi dan Misi', 'Visi, misi, dan tujuan madrasah.'],
            ['Struktur Organisasi', 'Bagan struktur organisasi madrasah.'],
            ['Sarana dan Prasarana', 'Fasilitas yang tersedia di madrasah.'],
        ] as [$title, $body]) {
            Page::updateOrCreate(['slug' => str($title)->slug()], [
                'title' => $title,
                'body' => "<p>{$body}</p>",
                'is_published' => true,
            ]);
        }

        Teacher::updateOrCreate(['name' => 'Drs. H. Contoh Kepala, M.Pd'], [
            'position' => 'Kepala Madrasah', 'group' => 'pimpinan', 'order' => 1, 'is_active' => true,
        ]);
        foreach (range(1, 8) as $i) {
            Teacher::updateOrCreate(['name' => "Guru Contoh {$i}"], [
                'position' => 'Guru', 'subject' => ['Matematika', 'IPA', 'Bahasa Indonesia', 'Bahasa Inggris', 'IPS', 'PAI', 'PJOK', 'Seni Budaya'][$i - 1],
                'group' => 'guru', 'order' => $i + 1, 'is_active' => true,
            ]);
        }

        foreach (range(1, 6) as $i) {
            Agenda::updateOrCreate(['slug' => "agenda-contoh-{$i}"], [
                'title' => "Agenda Kegiatan {$i}",
                'description' => 'Deskripsi kegiatan contoh.',
                'start_at' => now()->addDays($i * 3)->setTime(8, 0),
                'end_at' => now()->addDays($i * 3)->setTime(12, 0),
                'location' => 'Aula Madrasah',
            ]);
        }

        foreach (['Pramuka', 'Palang Merah Remaja', 'Futsal', 'Robotik', 'Tahfidz', 'Paduan Suara'] as $e) {
            Extracurricular::updateOrCreate(['slug' => str($e)->slug()], [
                'name' => $e, 'schedule' => 'Setiap Jumat, 14.00 - 16.00', 'description' => 'Ekstrakurikuler '.$e.'.',
            ]);
        }

        foreach (range(1, 6) as $i) {
            Achievement::updateOrCreate(['title' => "Juara {$i} Lomba Contoh"], [
                'student_name' => "Siswa Contoh {$i}",
                'level' => ['kota', 'provinsi', 'nasional'][$i % 3],
                'year' => now()->year,
                'description' => 'Prestasi membanggakan tingkat '.['kota', 'provinsi', 'nasional'][$i % 3].'.',
            ]);
        }

        Slider::updateOrCreate(['title' => 'Slide 1'], [
            'subtitle' => 'Selamat datang di MTsN 1 Kota Malang',
            'image' => null,
            'order' => 1,
            'is_active' => true,
        ]);
    }
}
