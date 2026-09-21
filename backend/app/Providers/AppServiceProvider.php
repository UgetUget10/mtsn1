<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use App\Observers\PostEditorialObserver;
use App\Observers\SlugHistoryObserver;
use App\Policies\ActivityPolicy;
use App\Policies\MediaPolicy;
use App\Policies\QueueMonitorPolicy;
use App\Policies\RolePolicy;
use Croustibat\FilamentJobsMonitor\Models\QueueMonitor;
use Filament\Forms\Components\FileUpload;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Spatie\Activitylog\Models\Activity;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // RolePolicy hidup di luar jangkauan policy discovery Laravel karena
        // model Role milik paket spatie/laravel-permission — daftarkan manual.
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Media::class, MediaPolicy::class);
        Gate::policy(QueueMonitor::class, QueueMonitorPolicy::class);
        Gate::policy(Activity::class, ActivityPolicy::class);

        // Rekam redirect 301 otomatis saat slug konten berubah (wp: _wp_old_slug).
        Post::observe(SlugHistoryObserver::class);
        Page::observe(SlugHistoryObserver::class);
        Category::observe(SlugHistoryObserver::class);
        Tag::observe(SlugHistoryObserver::class);

        // Notifikasi lonceng panel saat berita diajukan untuk ditinjau (pending).
        Post::observe(PostEditorialObserver::class);

        // Batas laju untuk API publik read-only. Frontend Next.js melakukan
        // fetch server-side (ISR, revalidate 300–600 dtk) sehingga volume
        // per-IP rendah; 120/menit memberi ruang lega untuk itu sambil
        // menahan scraping/DoS dari klien lain.
        RateLimiter::for('public-api', fn (Request $request) => Limit::perMinute(120)->by($request->ip()));

        // Batas ukuran default untuk semua field FileUpload di panel Filament.
        // Field khusus (mis. Document) menimpanya dengan nilai/whitelist mime
        // sendiri. Mencegah pengisian disk lewat akun editor/kontributor.
        FileUpload::configureUsing(fn (FileUpload $upload) => $upload->maxSize(8 * 1024)); // 8 MB

        // Backup aplikasi (shuvroroy/filament-spatie-laravel-backup) — operasi
        // sensitif, dibatasi ke super_admin saja.
        Gate::define('create-backup', fn ($user) => $user->hasRole('super_admin'));
        Gate::define('download-backup', fn ($user) => $user->hasRole('super_admin'));
        Gate::define('delete-backup', fn ($user) => $user->hasRole('super_admin'));
    }
}
