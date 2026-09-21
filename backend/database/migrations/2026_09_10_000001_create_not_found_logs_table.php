<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Log 404 ala plugin Redirection (tab "404s" / "Logs"):
 * setiap URL yang frontend jawab 404 dicatat di sini — satu baris per path,
 * dengan penghitung `hits` dan `last_seen_at`. Editor melihatnya di panel
 * (SEO & Tautan → Log 404) dan bisa satu klik "Buatkan pengalihan".
 *
 * `resolved_at` diisi saat editor sudah membuat redirect untuk path itu;
 * `ignored_at` diisi bila editor sengaja mengabaikannya (mis. sampah bot).
 * Baris ber-status keduanya tak lagi dihitung di badge navigasi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('not_found_logs', function (Blueprint $table) {
            $table->id();
            $table->string('path');                 // "/berita/judul-yang-salah"
            $table->unsignedBigInteger('hits')->default(1);
            $table->string('referrer')->nullable(); // dari mana pengunjung datang
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('ignored_at')->nullable();
            $table->timestamps();

            $table->unique('path');
            $table->index(['resolved_at', 'ignored_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('not_found_logs');
    }
};
