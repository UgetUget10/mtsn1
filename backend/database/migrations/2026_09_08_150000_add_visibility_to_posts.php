<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Visibilitas post ala WordPress (Publish box → Visibility):
 * - Public   : `visibility = public`, `password` null (default) — sama seperti sekarang.
 * - Protected : `visibility = password`, `password` berisi hash — isi hanya tampil
 *               setelah pengunjung memasukkan kata sandi (wp: post_password).
 * - Private   : `visibility = private` — tidak muncul di daftar/feed publik sama sekali
 *               (di sini artinya hanya bisa dilihat lewat pratinjau/panel).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('visibility', 20)->default('public')->after('status');
            $table->string('password')->nullable()->after('visibility');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['visibility', 'password']);
        });
    }
};
