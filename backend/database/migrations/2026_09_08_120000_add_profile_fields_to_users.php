<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Profil penulis ala WordPress (layar Users → Profile): slug publik,
 * "Biographical Info", jabatan, avatar, tautan sosial, dan sakelar tampil
 * di halaman arsip penulis publik (/penulis/{slug}).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->string('job_title')->nullable()->after('slug');
            $table->text('bio')->nullable()->after('job_title');
            $table->string('avatar')->nullable()->after('bio');
            $table->json('social')->nullable()->after('avatar'); // {website,instagram,twitter,linkedin,scholar}
            $table->boolean('show_publicly')->default(true)->after('social');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['slug', 'job_title', 'bio', 'avatar', 'social', 'show_publicly']);
        });
    }
};
