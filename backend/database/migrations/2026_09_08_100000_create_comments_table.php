<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Komentar pengunjung ala WordPress (wp_comments).
 *
 * - Polimorfik (`commentable_*`) supaya bisa dipakai Post sekarang & Page nanti.
 * - `parent_id` → balasan berulir (threaded replies, wp: threaded comments).
 * - `status`: pending | approved | spam | trash — sama seperti antrean moderasi WP
 *   (comment_approved 0 / 1 / 'spam' / 'trash').
 * - Data penulis tamu disimpan inline (nama/email/url/ip/user agent) seperti WP;
 *   `user_id` diisi bila komentar dibuat staf dari panel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->morphs('commentable');
            $table->foreignId('parent_id')->nullable()->constrained('comments')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('author_name', 100);
            $table->string('author_email', 150)->nullable();
            $table->string('author_url', 200)->nullable();
            $table->ipAddress('author_ip')->nullable();
            $table->string('user_agent', 255)->nullable();

            $table->text('body');
            $table->string('status', 20)->default('pending'); // pending|approved|spam|trash
            $table->timestamps();

            $table->index(['commentable_type', 'commentable_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
