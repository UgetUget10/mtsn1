<?php

/**
 * Konfigurasi alur editorial ala WordPress.
 * Nilai-nilai ini setara dengan konstanta wp-config.php:
 *   WP_POST_REVISIONS, EMPTY_TRASH_DAYS, AUTOSAVE_INTERVAL.
 */
return [
    // Berapa revisi terakhir yang disimpan per konten (0 = tak terbatas). WP: WP_POST_REVISIONS.
    'revisions_to_keep' => (int) env('EDITORIAL_REVISIONS_TO_KEEP', 20),

    // Umur maksimum item di Trash sebelum dibersihkan cron. WP: EMPTY_TRASH_DAYS.
    'empty_trash_days' => (int) env('EDITORIAL_EMPTY_TRASH_DAYS', 30),

    // Status post yang dianggap "tampil publik".
    'public_statuses' => ['published'],

    /*
     * Komentar pengunjung. Setara opsi Settings → Discussion di WordPress.
     */
    'comments' => [
        // Master switch. false = form komentar hilang & endpoint POST menolak.
        'enabled' => (bool) env('EDITORIAL_COMMENTS_ENABLED', true),

        // true  = komentar langsung tayang (wp: "Comment must be manually approved" OFF)
        // false = masuk antrean "Menunggu moderasi" dulu.
        'auto_approve' => (bool) env('EDITORIAL_COMMENTS_AUTO_APPROVE', false),

        // Tutup komentar otomatis untuk artikel lebih tua dari N hari (0 = tak pernah).
        'close_after_days' => (int) env('EDITORIAL_COMMENTS_CLOSE_AFTER_DAYS', 0),

        // Kedalaman balasan berulir maksimum (wp: thread_comments_depth).
        'max_depth' => (int) env('EDITORIAL_COMMENTS_MAX_DEPTH', 3),
    ],
];
