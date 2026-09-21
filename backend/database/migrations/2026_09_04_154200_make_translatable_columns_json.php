<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ubah kolom yang jadi translatable (lihat App\Models\Post, Page, Category,
 * MenuItem — HasTranslations + $translatable) dari string biasa menjadi JSON
 * berformat spatie/laravel-translatable: {"id": "<nilai lama>"}.
 *
 * Kolom lain pada tabel yang sama TIDAK disentuh.
 */
return new class extends Migration
{
    private array $columns = [
        'posts' => ['title', 'excerpt', 'body'],
        'pages' => ['title', 'meta_description'],
        'categories' => ['name'],
        'menu_items' => ['label'],
    ];

    public function up(): void
    {
        foreach ($this->columns as $table => $cols) {
            // 1) Bungkus nilai lama jadi JSON {"id": "<nilai>"} SEBELUM kolom
            //    diubah tipenya, supaya tidak ada window di mana kolom sudah
            //    JSON tapi masih berisi string mentah (MySQL akan menolaknya).
            foreach ($cols as $col) {
                DB::table($table)->whereNotNull($col)->orderBy('id')->chunkById(200, function ($rows) use ($table, $col) {
                    foreach ($rows as $row) {
                        $value = $row->{$col};
                        // Idempotent: lewati baris yang sudah berbentuk JSON object
                        // (migrasi ini dijalankan ulang, atau baris kosong).
                        if ($value === null || $value === '') {
                            continue;
                        }
                        $decoded = json_decode($value, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            continue;
                        }

                        DB::table($table)->where('id', $row->id)->update([
                            $col => json_encode(['id' => $value]),
                        ]);
                    }
                });
            }

            // 2) Ubah tipe kolom ke JSON.
            Schema::table($table, function (Blueprint $blueprint) use ($cols) {
                foreach ($cols as $col) {
                    $blueprint->json($col)->nullable()->change();
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->columns as $table => $cols) {
            // Kembalikan ke string, ambil nilai locale 'id' saja (rollback lossy
            // untuk locale lain — dapat diterima karena ini rollback darurat).
            Schema::table($table, function (Blueprint $blueprint) use ($cols) {
                foreach ($cols as $col) {
                    $blueprint->text($col)->nullable()->change();
                }
            });

            foreach ($cols as $col) {
                DB::table($table)->whereNotNull($col)->orderBy('id')->chunkById(200, function ($rows) use ($table, $col) {
                    foreach ($rows as $row) {
                        $decoded = json_decode($row->{$col}, true);
                        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                            DB::table($table)->where('id', $row->id)->update([
                                $col => $decoded['id'] ?? array_values($decoded)[0] ?? null,
                            ]);
                        }
                    }
                });
            }
        }
    }
};
