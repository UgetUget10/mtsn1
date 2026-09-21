<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menggantikan testimoni FIKTIF yang sebelumnya hardcode di frontend
     * (`frontend/src/app/[locale]/page.tsx`, array `testimonials` — nama
     * karangan "Ibu Nurhayati", "Raka Pratama", dst tayang di beranda tanpa
     * sumber nyata). Admin kini input testimoni ASLI lewat panel.
     */
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->text('quote');
            $table->string('name');
            // wp: "role" bebas isi — "Wali murid kelas VIII", "Alumni 2022",
            // bukan enum tetap, karena variasinya luas & berubah tiap tahun.
            $table->string('role')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
