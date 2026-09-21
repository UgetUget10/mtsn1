<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WordPress: editor Menu bisa menambah Post, Kategori, dan Tag sebagai item
 * menu — bukan cuma Halaman & URL bebas. Item seperti itu menyimpan REFERENSI
 * ke kontennya, jadi URL-nya selalu ikut slug terbaru (tak patah saat slug
 * berubah, beda dari mengetik URL manual).
 *
 * `type` menu_items yang baru: `post`, `category`, `tag`.
 * Kolom acuan: `post_id`, `category_id`, `tag_id` (nullable, nullOnDelete —
 * konten tujuan dihapus → item jadi tautan mati & disaring MenuController).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->foreignId('post_id')->nullable()->after('page_id')->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->after('post_id')->constrained()->nullOnDelete();
            $table->foreignId('tag_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('post_id');
            $table->dropConstrainedForeignId('category_id');
            $table->dropConstrainedForeignId('tag_id');
        });
    }
};
