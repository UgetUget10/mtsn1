<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sprint 1 — alur editorial ala WordPress:
 * - tabel `revisions` polymorphic (wp_post_revisions)
 * - soft delete (wp trash) untuk konten utama
 * - kolom `preview_token` untuk pratinjau draft (wp preview nonce)
 * - status diperluas: draft | pending | scheduled | published (wp future/pending)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revisions', function (Blueprint $table) {
            $table->id();
            $table->morphs('revisionable'); // revisionable_type + revisionable_id
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('data');            // snapshot atribut model saat itu
            $table->string('reason')->nullable(); // "autosave" | "manual" | null
            $table->timestamps();
            $table->index(['revisionable_type', 'revisionable_id', 'created_at']);
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->softDeletes();
            $table->uuid('preview_token')->nullable()->unique()->after('slug');
            // status lama: draft | published. Tambah: pending, scheduled.
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->softDeletes();
            $table->uuid('preview_token')->nullable()->unique()->after('slug');
        });

        foreach (['galleries', 'documents', 'agendas', 'achievements', 'extracurriculars'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('revisions');

        Schema::table('posts', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropUnique(['preview_token']);
            $table->dropColumn('preview_token');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropUnique(['preview_token']);
            $table->dropColumn('preview_token');
        });

        foreach (['galleries', 'documents', 'agendas', 'achievements', 'extracurriculars'] as $t) {
            Schema::table($t, function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
