<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kolom `meta` json — setara wp_postmeta versi ringan (SEO title, canonical,
 * noindex, layout override, dsb.) tanpa tabel key/value terpisah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->json('meta')->nullable()->after('body');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->json('meta')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('posts', fn (Blueprint $t) => $t->dropColumn('meta'));
        Schema::table('pages', fn (Blueprint $t) => $t->dropColumn('meta'));
    }
};
