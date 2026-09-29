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
        Schema::create('global_templates', function (Blueprint $table) {
            $table->id();
            // 'header' / 'footer' — slot tunggal yang aktif tampil di situs publik.
            // unique(slot) di mana is_active=true ditegakkan di controller, bukan DB,
            // supaya boleh ada banyak draft/nonaktif per slot (riwayat desain).
            $table->string('slot');
            $table->string('name');
            $table->boolean('is_active')->default(false);
            $table->json('tree')->nullable();
            $table->json('tree_draft')->nullable();
            $table->timestamps();

            $table->index(['slot', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('global_templates');
    }
};
