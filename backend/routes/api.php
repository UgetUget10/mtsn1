<?php

use App\Http\Controllers\Api\ApiDiscoveryController;
use App\Http\Controllers\Api\ArchiveController;
use App\Http\Controllers\Api\AuthorController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\MiscController;
use App\Http\Controllers\Api\NotFoundLogController;
use App\Http\Controllers\Api\PageController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\PreviewController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\WidgetAreaController;
use App\Http\Controllers\FeedController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/', [ApiDiscoveryController::class, 'index'])->name('api.discovery');

    Route::middleware('throttle:public-api')->group(function () {
        Route::get('posts', [PostController::class, 'index'])->name('posts.index');
        Route::get('posts/{post}', [PostController::class, 'show'])->name('posts.show');

        // Buka post terlindungi kata sandi (wp: post_password form).
        Route::post('posts/{post}/unlock', [PostController::class, 'unlock'])
            ->middleware('throttle:10,1')
            ->name('posts.unlock');

        // Komentar publik (wp: wp-comments). Daftar = throttle publik biasa;
        // kirim komentar dibatasi lebih ketat di bawah.
        Route::get('posts/{post}/comments', [CommentController::class, 'index'])->name('comments.index');

        Route::get('pages', [PageController::class, 'index']);
        Route::get('pages/{page}', [PageController::class, 'show'])->name('pages.show');

        Route::get('tags', [TagController::class, 'index']);
        Route::get('tags/{tag}', [TagController::class, 'show'])->name('tags.show');

        Route::get('categories', [CategoryController::class, 'index']);
        Route::get('categories/{category}', [CategoryController::class, 'show'])->name('categories.show');

        // Arsip penulis ala WordPress (/author/{slug}).
        Route::get('authors', [AuthorController::class, 'index']);
        Route::get('authors/{user}', [AuthorController::class, 'show'])->name('authors.show');

        // Arsip tanggal ala WordPress (wp_get_archives + /2026/09/).
        Route::get('archives', [ArchiveController::class, 'index']);
        Route::get('archives/{year}/{month}', [ArchiveController::class, 'show'])
            ->whereNumber(['year', 'month'])
            ->name('archives.show');

        // Pencarian menyeluruh lintas-tipe konten (wp: search).
        Route::get('search', SearchController::class);

        Route::get('menus/{key}', [MenuController::class, 'show']);
        Route::get('widget-areas/{key}', [WidgetAreaController::class, 'show']);

        Route::get('sliders', [MiscController::class, 'sliders']);
        Route::get('teachers', [MiscController::class, 'teachers']);
        Route::get('agendas', [MiscController::class, 'agendas']);
        Route::get('galleries', [MiscController::class, 'galleries']);
        Route::get('documents', [MiscController::class, 'documents']);
        Route::get('documents/{document}/download', [MiscController::class, 'downloadDocument'])
            ->name('documents.download');
        Route::get('achievements', [MiscController::class, 'achievements']);
        Route::get('testimonials', [MiscController::class, 'testimonials']);
        Route::get('extracurriculars', [MiscController::class, 'extracurriculars']);
        Route::get('settings', [MiscController::class, 'settings']);

        // Ala WordPress: cek redirect 301 untuk permalink lama (slug berubah).
        Route::get('resolve', [FeedController::class, 'resolve']);

        // Log 404 ala plugin Redirection — proxy.ts melapor tiap kali 404.
        // Dibatasi ketat: satu klien tak perlu melapor lebih dari ini.
        Route::post('log-404', [NotFoundLogController::class, 'store'])
            ->middleware('throttle:30,1')
            ->name('log-404');
    });

    // Pratinjau draft — dilindungi preview_token, bukan throttle publik.
    Route::middleware('throttle:30,1')->group(function () {
        Route::get('preview/posts/{idOrSlug}', [PreviewController::class, 'post']);
        Route::get('preview/pages/{idOrSlug}', [PreviewController::class, 'page']);
    });

    Route::post('contacts', [ContactController::class, 'store'])->middleware('throttle:6,1');

    // Kirim komentar: 5 per menit per IP (wp: comment flood protection).
    Route::post('posts/{post}/comments', [CommentController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('comments.store');

    // Berhenti langganan balasan komentar via token (tautan kaki email).
    Route::get('comments/unsubscribe/{token}', [CommentController::class, 'unsubscribe'])
        ->middleware('throttle:20,1')
        ->name('comments.unsubscribe');
});
