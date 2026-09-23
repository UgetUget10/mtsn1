<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // Draf kanvas visual (App\Filament\Pages\PageCanvasEditor) — terpisah
            // dari `blocks` yang published, supaya autosave saat menyunting tidak
            // langsung mengubah halaman publik. "Publish" menyalin ini ke `blocks`.
            $table->json('blocks_draft')->nullable()->after('blocks');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('blocks_draft');
        });
    }
};
