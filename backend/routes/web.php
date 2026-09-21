<?php

use App\Http\Controllers\Admin\PostImportTemplateController;
use App\Http\Controllers\FeedController;
use App\Http\Middleware\SetApiLocale;
use Filament\Http\Middleware\Authenticate as FilamentAuthenticate;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

// Tombol "Unduh Template" di modal Import Berita (App\Filament\Resources\
// Posts\Actions\ImportPostsAction) — di luar routing Filament sendiri, jadi
// diproteksi manual. PENTING: pakai Filament\Http\Middleware\Authenticate
// (BUKAN middleware 'auth' bawaan Laravel) — 'auth' generik mencoba redirect
// ke route bernama "login" yang tidak ada di proyek ini (Filament punya
// login sendiri di /admin/login), sehingga pengunjung tak login mendapat
// error 500 "Route [login] not defined" alih-alih diarahkan ke halaman
// login. Middleware Filament tahu redirect ke Filament::getLoginUrl().
Route::middleware(FilamentAuthenticate::class)->prefix('admin/posts')->name('admin.posts.')->group(function () {
    Route::get('import-template.csv', [PostImportTemplateController::class, 'csv'])->name('import-template.csv');
    Route::get('import-template.xlsx', [PostImportTemplateController::class, 'xlsx'])->name('import-template.xlsx');
});

// Endpoint kompatibilitas ala WordPress. SetApiLocale supaya `?locale=en`
// juga menerjemahkan judul di RSS / sitemap / iCal (accessor translatable
// mengikuti App::getLocale()).
Route::middleware(SetApiLocale::class)->group(function () {
    Route::get('/sitemap.xml', [FeedController::class, 'sitemap'])->name('sitemap');
    Route::get('/feed', [FeedController::class, 'feed'])->name('feed');
    Route::get('/agenda.ics', [FeedController::class, 'agendaCalendar'])->name('agenda.ics');
});
