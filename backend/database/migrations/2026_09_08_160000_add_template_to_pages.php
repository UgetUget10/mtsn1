<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * WordPress "Page Attributes → Template". Setiap halaman bisa memilih
 * template tata letak dari daftar yang disediakan tema. Nilai `default`
 * setara "Default Template" WordPress.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->string('template')->default('default')->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table): void {
            $table->dropColumn('template');
        });
    }
};
