<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deskripsi tag ala WordPress (wp_term_taxonomy.description) — tampil di
 * header halaman arsip tag (/berita/tag/{slug}).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->text('description')->nullable()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
