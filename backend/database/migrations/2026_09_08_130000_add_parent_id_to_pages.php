<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Halaman hierarkis ala WordPress (Page Attributes → Parent). Sebuah halaman
 * bisa punya induk sehingga admin menampilkan pohon dan frontend bisa membuat
 * breadcrumb + sub-navigasi. Permalink tetap datar (/profil/{slug}) seperti
 * pilihan "post name" di WP — hierarki dipakai untuk relasi & navigasi, bukan URL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('slug')
                ->constrained('pages')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
