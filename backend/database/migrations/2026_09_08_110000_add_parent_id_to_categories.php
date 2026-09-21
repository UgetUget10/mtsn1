<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kategori hierarkis ala WordPress (wp_term_taxonomy.parent). Sebuah kategori
 * bisa punya induk sehingga admin menampilkan pohon dan frontend bisa membuat
 * arsip bertingkat / breadcrumb kategori.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('parent_id')
                ->nullable()
                ->after('type')
                ->constrained('categories')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
