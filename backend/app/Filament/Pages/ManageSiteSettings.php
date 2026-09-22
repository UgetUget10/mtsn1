<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ManageSiteSettings extends Page
{
    protected string $view = 'filament.pages.manage-site-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Pengaturan Situs';

    protected static ?string $title = 'Pengaturan Situs';

    protected static ?int $navigationSort = 99;

    public ?array $data = [];

    /**
     * Semua kunci di sini dikonsumsi oleh frontend Next.js
     * (lihat frontend/src/lib/api.ts + pemakaian `settings.*`).
     */
    protected array $keys = [
        // Identitas
        'site_name', 'site_tagline', 'school_name', 'npsn', 'nsm',
        'logo', 'favicon', 'og_image',
        'principal_name', 'principal_photo', 'principal_word',
        // Kontak
        'address', 'phone', 'whatsapp', 'email', 'service_hours',
        'maps_embed', 'maps_url',
        // PPDB & tautan portal
        'ppdb_url', 'ppdb_deadline', 'lms_url', 'ptsp_url', 'rdm_url',
        'sakip_url', 'survey_url', 'pmbm_url',
        // Pengumuman berjalan
        'announcement', 'announcement_url',
        // Media sosial
        'facebook', 'instagram', 'youtube', 'twitter',
        // Statistik beranda
        'stat_students', 'stat_teachers', 'stat_staff', 'stat_classes',
        'stat_accreditation', 'stat_alumni',
        // Predikat/sertifikasi (bar "Diakui & Bersertifikat" di beranda)
        'badges',
        // Settings → Reading (wp)
        'posts_per_page', 'rss_content_mode', 'posts_per_rss',
    ];

    public function mount(): void
    {
        $values = Setting::values();
        $filled = collect($this->keys)->mapWithKeys(fn ($k) => [$k => $values[$k] ?? null])->all();

        // `badges` disimpan sebagai JSON string (pola sama dengan
        // `homepage_sections` di HomepageBuilder) — TagsInput butuh array.
        $filled['badges'] = json_decode($filled['badges'] ?? '[]', true) ?: [];

        $this->form->fill($filled);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Identitas')->columns(2)->schema([
                    TextInput::make('site_name')->label('Nama madrasah'),
                    TextInput::make('site_tagline')->label('Tagline'),
                    TextInput::make('school_name')->label('Nomenklatur resmi')
                        ->placeholder('Madrasah Tsanawiyah Negeri 1 Kota Malang'),
                    TextInput::make('npsn')->label('NPSN'),
                    TextInput::make('nsm')->label('NSM'),
                    FileUpload::make('logo')->label('Logo')->image()->directory('settings'),
                    FileUpload::make('favicon')->label('Favicon')->image()->directory('settings'),
                    FileUpload::make('og_image')->label('Gambar share (OG image)')->image()->directory('settings'),
                    TextInput::make('principal_name')->label('Nama kepala madrasah'),
                    FileUpload::make('principal_photo')->label('Foto kepala madrasah')
                        ->helperText('Gunakan foto beresolusi tinggi (minimal 1000×1000px) karena ditampilkan besar di beranda.')
                        ->image()->imageEditor()->imageEditorAspectRatios(['4:5', '1:1', null])->directory('settings'),
                    Textarea::make('principal_word')->label('Sambutan kepala madrasah')->rows(4)->columnSpanFull(),
                ]),
                Section::make('Kontak')->columns(2)->schema([
                    Textarea::make('address')->label('Alamat')->rows(2)->columnSpanFull(),
                    TextInput::make('phone')->label('Telepon'),
                    TextInput::make('whatsapp')->label('WhatsApp')->placeholder('6281234567890'),
                    TextInput::make('email')->email(),
                    TextInput::make('service_hours')->label('Jam layanan')
                        ->placeholder('Senin–Jumat, 07.00–15.30 WIB'),
                    Textarea::make('maps_embed')->label('Google Maps embed URL (iframe src)')->rows(2)->columnSpanFull(),
                    TextInput::make('maps_url')->label('Google Maps tautan (buka di app)')->url(),
                ]),
                Section::make('PPDB & Portal')->columns(2)->schema([
                    TextInput::make('ppdb_url')->label('Link PPDB')->url(),
                    TextInput::make('ppdb_deadline')->label('Batas akhir PPDB')
                        ->placeholder('2026-07-05')
                        ->helperText('Format tanggal ISO (YYYY-MM-DD) untuk hitung mundur di beranda.'),
                    TextInput::make('lms_url')->label('Link LMS / e-learning')->url(),
                    TextInput::make('ptsp_url')->label('Link PTSP Online')->url(),
                    TextInput::make('rdm_url')->label('Link RDM / Rapor Digital')->url(),
                    // Ketiga tautan ini sudah lama ada di MiscController::PUBLIC_SETTING_KEYS
                    // dan dipakai frontend, tapi belum pernah punya field di sini —
                    // akibatnya nilainya selalu kosong di produksi.
                    TextInput::make('sakip_url')
                        ->label('Link SAKIP')
                        ->url()
                        ->helperText('Tampil sebagai tautan di bar atas situs.'),
                    TextInput::make('survey_url')
                        ->label('Link Survei Kepuasan')
                        ->url()
                        ->helperText('Dipakai halaman /layanan/survei. Bila kosong, tombol survei mengarah ke /kontak.'),
                    TextInput::make('pmbm_url')
                        ->label('Link PMBM')
                        ->url(),
                ]),
                Section::make('Pengumuman Berjalan')->columns(2)->schema([
                    TextInput::make('announcement')->label('Teks pengumuman')
                        ->helperText('Tampil di bilah atas seluruh halaman. Kosongkan untuk menyembunyikan.')
                        ->columnSpanFull(),
                    TextInput::make('announcement_url')->label('Tautan pengumuman')->url(),
                ]),
                Section::make('Media Sosial')->columns(2)->schema([
                    TextInput::make('facebook')->url(),
                    TextInput::make('instagram')->url(),
                    TextInput::make('youtube')->url(),
                    TextInput::make('twitter')->url(),
                ]),
                Section::make('Statistik Beranda')
                    ->description('Angka yang tampil di bagian hero halaman depan. Kosongkan untuk memakai nilai bawaan.')
                    ->columns(3)
                    ->schema([
                        TextInput::make('stat_students')->label('Peserta didik')->placeholder('803'),
                        TextInput::make('stat_teachers')->label('Guru')->placeholder('56'),
                        TextInput::make('stat_staff')->label('Tenaga kependidikan')->placeholder('24'),
                        TextInput::make('stat_classes')->label('Rombel')->placeholder('30'),
                        TextInput::make('stat_accreditation')->label('Akreditasi')->placeholder('A'),
                        TextInput::make('stat_alumni')->label('Alumni')->placeholder('10.000+'),
                    ]),
                Section::make('Predikat & Sertifikasi')
                    ->description('Bar "Diakui & Bersertifikat" di beranda — hanya tampilkan predikat yang benar-benar dimiliki madrasah saat ini.')
                    ->schema([
                        TagsInput::make('badges')
                            ->label('Predikat')
                            ->placeholder('Ketik lalu Enter')
                            ->helperText('Contoh: Terakreditasi A, Madrasah Ramah Anak, Berma\'had.')
                            ->splitKeys(['Tab', 'Enter'])
                            ->columnSpanFull(),
                    ]),

                // Setara Settings → Reading di WordPress. Kunci ini SUDAH lama
                // dikirim MiscController::PUBLIC_SETTING_KEYS tapi tak pernah
                // punya field di panel — nilainya mustahil diubah editor.
                Section::make('Membaca')
                    ->description('Jumlah berita per halaman di arsip publik, dan bentuk isi feed RSS.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('posts_per_page')
                            ->label('Berita per halaman')
                            ->helperText('Dipakai di /berita dan arsip kategori, tag, serta bulan. Kosongkan untuk memakai 12.')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(50)
                            ->placeholder('12'),
                        Select::make('rss_content_mode')
                            ->label('Isi feed RSS')
                            ->helperText('wp: Settings → Reading. "Ringkasan" = perilaku sekarang. "Teks lengkap" = seluruh isi artikel.')
                            ->options(['ringkasan' => 'Ringkasan', 'lengkap' => 'Teks lengkap'])
                            ->default('ringkasan')
                            ->selectablePlaceholder(false)
                            ->native(false),
                        TextInput::make('posts_per_rss')
                            ->label('Jumlah item di feed RSS')
                            ->helperText('wp: "Syndication feeds show the most recent". Kosongkan untuk memakai 20.')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(50)
                            ->placeholder('20'),
                    ]),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label('Simpan')->submit('save'),
        ];
    }

    /** Field berkas yang punya koleksi media library sendiri per baris Setting. */
    protected array $mediaKeys = ['logo', 'favicon', 'og_image', 'principal_photo'];

    public function save(): void
    {
        foreach ($this->form->getState() as $key => $value) {
            // `badges` datang dari TagsInput sebagai array — encode ke JSON
            // string sebelum disimpan (kolom `value` cuma longText biasa).
            if ($key === 'badges' && is_array($value)) {
                $value = json_encode(array_values($value));
            }

            $setting = Setting::updateOrCreate(['key' => $key], ['value' => $value, 'group' => 'general']);

            if (in_array($key, $this->mediaKeys, true) && $value && is_string($value)) {
                $this->syncSettingMedia($setting, $value);
            }
        }

        Notification::make()->title('Pengaturan disimpan.')->success()->send();
    }

    private function syncSettingMedia(Setting $setting, string $path): void
    {
        if (! Storage::disk('public')->exists($path)) {
            return;
        }

        $existing = $setting->getFirstMedia('file');
        if ($existing && $existing->getCustomProperty('source_path') === $path) {
            return;
        }

        try {
            // toMediaCollection() pada koleksi singleFile menghapus media lama
            // SESUDAH yang baru terpasang. Bila folder konversi media lama sudah
            // yatim (file induk hilang tapi subfolder conversions/ tersisa),
            // pembersihan itu melempar RuntimeException "Lstat failed" — media
            // baru sudah tersimpan tapi transaksi form gagal. Buang media lama
            // lebih dulu, abaikan kegagalan hapus berkas fisiknya.
            if ($existing) {
                try {
                    $existing->delete();
                } catch (\Throwable $e) {
                    // Baris DB-nya yang penting; sisa berkas di disk tidak fatal.
                    report($e);
                    $existing->forceDelete();
                }
            }

            $setting->addMediaFromDisk($path, 'public')
                // Tanpa ini, addMediaFromDisk() MENGHAPUS file sumber di
                // storage/app/public/settings/... setelah dipindah ke
                // koleksi media (storage/app/public/{id}/...). Kolom
                // `settings.value` (dipakai FileUpload di form ini untuk
                // preview) tetap menyimpan path sumber tsb — begitu file
                // itu terhapus, preview logo/foto di form langsung patah
                // walau data "ada" di database.
                ->preservingOriginal()
                ->withCustomProperties(['source_path' => $path])
                ->usingFileName(Str::random(8).'-'.basename($path))
                ->toMediaCollection('file');

            // addMediaFromDisk() tidak menyentuh ulang baris Setting, jadi event
            // `saved` (yang memicu revalidasi frontend di
            // TriggersFrontendRevalidation) sudah lewat sebelum media baru
            // terpasang — frontend akan revalidate dengan URL foto LAMA.
            // touch() memaksa `saved` menembak lagi sesudah media siap.
            $setting->touch();
        } catch (\Throwable $e) {
            // JANGAN telan diam-diam: sebelumnya kegagalan di sini membuat panel
            // tetap menampilkan "Pengaturan disimpan" padahal foto tidak berganti.
            report($e);

            Notification::make()
                ->title('Berkas gagal dipasang.')
                ->body('Perubahan teks tersimpan, tetapi berkas untuk "'.$setting->key.'" gagal diproses. Coba unggah ulang.')
                ->danger()
                ->persistent()
                ->send();
        }
    }
}
