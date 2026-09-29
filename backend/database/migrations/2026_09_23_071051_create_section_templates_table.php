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
        Schema::create('section_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            // Satu node section utuh (id, style, children kolom/widget) —
            // "salin sekali lalu berkembang sendiri", BEDA dari `blocks`
            // (ReusableBlock, tabel blocks) yang sinkron: template ini tidak
            // pernah otomatis berubah di halaman yang sudah memakainya.
            $table->json('section')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_templates');
    }
};
