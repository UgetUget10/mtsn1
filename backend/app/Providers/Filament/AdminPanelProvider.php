<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use App\Http\Middleware\Filament\UseRequestHostForAssetUrls;
use App\Filament\Widgets\MadrasahStats;
use App\Filament\Widgets\NeedsAttention;
use App\Filament\Widgets\OnboardingChecklist;
use App\Filament\Widgets\PendingComments;
use App\Filament\Widgets\PopularPosts;
use App\Filament\Widgets\QuickDraft;
use App\Filament\Widgets\RecentActivity;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Croustibat\FilamentJobsMonitor\FilamentJobsMonitorPlugin;
use Rmsramos\Activitylog\ActivitylogPlugin;
use ShuvroRoy\FilamentSpatieLaravelBackup\FilamentSpatieLaravelBackupPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Events\RouteMatched;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /**
     * Livewire mendaftarkan endpoint update/upload-file-nya SENDIRI (lewat
     * boot() provider-nya sendiri, urutannya relatif terhadap provider lain
     * tidak bisa diasumsikan) — di luar grup middleware panel Filament di
     * bawah (lihat panel()->middleware()). Field gambar di form Filament
     * (mis. cover berita) dirender ulang lewat endpoint itu setiap kali
     * komponen Livewire update (termasuk saat wire:navigate antar halaman
     * admin). Kalau endpoint itu tetap memakai APP_URL (domain publik),
     * gambar bisa gagal load ketika panel dibuka lewat IP LAN dan domain
     * publik itu tak ter-resolve dari jaringan pengakses.
     *
     * Daripada mengejar route Livewire lewat nama saat boot (rawan meleset
     * kalau urutan boot provider berubah), pasang lewat event
     * Route::matched yang selalu jalan tiap request nyata — apa pun urutan
     * boot-nya.
     */
    public function boot(): void
    {
        Route::matched(function (RouteMatched $event): void {
            $name = $event->route->getName();

            if ($name === 'default-livewire.update' || $name === 'livewire.upload-file') {
                UseRequestHostForAssetUrls::apply($event->request);
            }
        });
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            // "Profil Saya" self-service ala wp-admin/profile.php — setiap
            // user boleh sunting biodata/avatar/kata sandi miliknya sendiri,
            // terlepas dari izin resource Pengguna.
            ->profile(EditProfile::class)
            // Autentikasi Google/Microsoft Authenticator (TOTP), fitur bawaan
            // Filament 5 — bukan paket tambahan. WAJIB untuk super_admin (akun
            // yang bisa hapus permanen data & restore backup); opsional untuk
            // role lain supaya tidak mengganggu alur kontributor/editor biasa.
            ->multiFactorAuthentication(
                [AppAuthentication::make()],
                isRequired: fn () => auth()->user()?->hasRole('super_admin') ?? false,
            )
            ->colors([
                'primary' => Color::Amber,
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_START,
                fn (): Htmlable => new HtmlString(
                    // CSS cascade layer order HARUS dideklarasikan di sini,
                    // sebelum stylesheet plugin manapun dimuat. app.css milik
                    // Filament sendiri tidak pernah mengeluarkan pernyataan
                    // urutan gabungan ("@layer theme, base, components,
                    // utilities;") — urutan layer di seluruh dokumen
                    // ditentukan oleh kemunculan PERTAMA nama layer tsb di
                    // manapun. filament-jobs-monitor-styles.css (dimuat lebih
                    // dulu dari app.css) membungkus isinya dalam
                    // "@layer components{...}", sehingga "components"
                    // ter-daftar sebagai layer PALING RENDAH prioritasnya —
                    // lebih rendah dari "base" milik Filament sendiri. Akibatnya
                    // reset elemen polos di base (mis. button{border-radius:0})
                    // mengalahkan kelas komponen Filament sendiri (.fi-btn,
                    // .fi-input, dst) di seluruh panel, membuat tampilan
                    // berantakan/tidak bergaya meski semua CSS berhasil
                    // dimuat. Deklarasi kosong ini mengunci urutan yang benar
                    // lebih awal sebelum plugin manapun sempat mendaftarkan
                    // layer secara tidak sengaja.
                    '<style>@layer properties, theme, base, components, utilities;</style>',
                ),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): Htmlable => new HtmlString(
                    // Rich editor "Isi" (App\Filament\Resources\Posts\Schemas\
                    // PostForm) diberi kelas mtsn1-rich-editor-tall supaya area
                    // ketiknya lebih tinggi — bawaan Filament (~150px) terlalu
                    // pendek untuk artikel berita yang biasanya beberapa
                    // paragraf. Tidak ada API resmi untuk mengatur tinggi
                    // RichEditor, jadi ditarget lewat kelas pembungkus +
                    // selector ProseMirror standar (nama kelas TipTap yang
                    // stabil lintas versi Filament, bukan detail implementasi
                    // internal Filament sendiri).
                    '<style>
                        .mtsn1-rich-editor-tall .ProseMirror {
                            min-height: 24rem;
                        }
                    </style>',
                ),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            // Notifikasi in-panel (WP: "pending review", pesan kontak baru).
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            // Dashboard ala WordPress: "Sekilas" + "Draf kilat" + "Aktivitas".
            ->widgets([
                AccountWidget::class,
                OnboardingChecklist::class,
                MadrasahStats::class,
                NeedsAttention::class,
                QuickDraft::class,
                RecentActivity::class,
                PopularPosts::class,
                PendingComments::class,
            ])
            ->middleware([
                UseRequestHostForAssetUrls::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make(),
                ActivitylogPlugin::make(),
                FilamentSpatieLaravelBackupPlugin::make()
                    ->authorize(fn (): bool => auth()->user()?->hasRole('super_admin') ?? false),
                FilamentJobsMonitorPlugin::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
