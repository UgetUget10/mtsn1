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
        // Penempatan satu "widget" (=ReusableBlock, tabel `blocks`) ke satu
        // zona — setara drag-drop widget ke sidebar di wp-admin. Satu blok
        // yang sama boleh dipakai di beberapa zona sekaligus (persis Reusable
        // Block dipakai di banyak halaman).
        Schema::create('widget_area_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('widget_area_id')->constrained()->cascadeOnDelete();
            $table->foreignId('block_id')->constrained('blocks')->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['widget_area_id', 'block_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('widget_area_items');
    }
};
