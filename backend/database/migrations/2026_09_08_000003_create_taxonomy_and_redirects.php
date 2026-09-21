<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 2 & 3:
 * - `tags` + pivot `post_tag` (wp: post_tag taxonomy)
 * - pivot `category_post` (post banyak kategori, wp mendukung ini)
 * - `redirects` (wp: perubahan slug otomatis bikin redirect 301)
 * - `blocks` reusable (wp: Synced Patterns / Reusable Blocks)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->json('name');            // translatable
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('post_tag', function (Blueprint $table) {
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['post_id', 'tag_id']);
        });

        Schema::create('category_post', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'post_id']);
        });

        Schema::create('redirects', function (Blueprint $table) {
            $table->id();
            $table->string('from_path')->unique();   // "/berita/judul-lama"
            $table->string('to_path');               // "/berita/judul-baru"
            $table->unsignedSmallInteger('status')->default(301);
            $table->string('source')->default('slug-change'); // slug-change | manual
            $table->unsignedBigInteger('hits')->default(0);
            $table->timestamp('last_hit_at')->nullable();
            $table->timestamps();
        });

        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->json('content');                 // array block terserialisasi
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocks');
        Schema::dropIfExists('redirects');
        Schema::dropIfExists('category_post');
        Schema::dropIfExists('post_tag');
        Schema::dropIfExists('tags');
    }
};
