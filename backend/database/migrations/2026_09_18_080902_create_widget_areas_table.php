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
        // "Widget Areas" ala WordPress (Appearance > Widgets): zona tetap di
        // frontend (sidebar berita, footer, dst.) yang isinya bisa diisi admin
        // tanpa deploy kode. `key` dipakai frontend untuk minta isi zona
        // tertentu — daftar zona yang tersedia didefinisikan di kode (lihat
        // App\Support\Widgets\WidgetAreaKeys), baris di sini hanya menyimpan
        // apa yang DITEMPATKAN admin di zona itu.
        Schema::create('widget_areas', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('widget_areas');
    }
};
