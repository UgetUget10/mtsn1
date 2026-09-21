<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // Konten terstruktur berulang (kartu, akordion, CTA, dst.) — lihat
            // App\Filament\Resources\Pages\Schemas\PageForm untuk daftar tipe block.
            // Kolom `body` (rich text bebas) tetap dipertahankan berdampingan:
            // halaman lama otomatis dibungkus jadi satu block `rich_text` (lihat
            // seeder/command migrasi), halaman baru bisa pakai keduanya sekaligus.
            $table->json('blocks')->nullable()->after('body');

            // Halaman singleton beranda ('__homepage__') dipakai HomepageBuilder
            // untuk menyimpan/mengelola urutan section beranda lewat mekanisme
            // block yang sama, tanpa perlu tabel terpisah.
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('blocks');
        });
    }
};
