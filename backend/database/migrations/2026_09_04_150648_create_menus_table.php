<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // header | footer
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->string('label');
            // page: tautan ke Page (page_id); custom_url: tautan bebas (url);
            // section: label pengelompok tanpa tautan sendiri (dipakai mega-menu
            // berkolom, mis. "Pelayanan Publik" yang menaungi beberapa leaf).
            $table->string('type')->default('custom_url');
            $table->foreignId('page_id')->nullable()->constrained()->nullOnDelete();
            $table->string('url')->nullable();
            $table->string('icon')->nullable();
            // Kartu aksen opsional di dalam mega-panel (mis. CTA PPDB) — hanya
            // relevan untuk item level teratas (parent_id null).
            $table->string('feature_title')->nullable();
            $table->text('feature_text')->nullable();
            $table->string('feature_cta')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
    }
};
