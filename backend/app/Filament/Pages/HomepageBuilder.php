<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Pengelola urutan & tampil/sembunyi section beranda Next.js. Sebagian
 * besar section beranda sudah data-driven dari resource lain (Post, Agenda,
 * Achievement, Gallery — masing-masing sudah punya Filament Resource
 * sendiri), jadi halaman ini TIDAK mengedit isi section (itu ada di
 * masing-masing resource), hanya urutan tampil dan tampil/sembunyikannya.
 *
 * Disimpan sebagai satu baris Setting (key: homepage_sections) berisi JSON
 * array section beserta status is_visible-nya, dikonsumsi frontend lewat
 * `settings.homepage_sections` (lihat frontend/src/app/page.tsx).
 */
class HomepageBuilder extends Page
{
    protected string $view = 'filament.pages.homepage-builder';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Susunan Beranda';

    protected static ?string $title = 'Susunan Beranda';

    protected static ?int $navigationSort = 96;

    public ?array $data = [];

    /**
     * Daftar section tetap yang bisa diatur — id HARUS sama dengan yang
     * dipakai frontend untuk mem-filter render section (lihat
     * frontend/src/lib/homepage-sections.ts).
     */
    private const DEFAULT_SECTIONS = [
        ['id' => 'akses_cepat', 'label' => 'Akses Cepat'],
        ['id' => 'keunggulan', 'label' => 'Keunggulan'],
        ['id' => 'berita', 'label' => 'Berita & Pengumuman'],
        ['id' => 'statistik', 'label' => 'Statistik Madrasah'],
        ['id' => 'testimoni', 'label' => 'Testimoni'],
        ['id' => 'program', 'label' => 'Program Unggulan'],
        ['id' => 'identitas', 'label' => 'Identitas Madrasah'],
        ['id' => 'sambutan', 'label' => 'Sambutan Kepala Madrasah'],
        ['id' => 'agenda_prestasi', 'label' => 'Agenda & Prestasi'],
        ['id' => 'sarpras', 'label' => 'Sarana & Prasarana'],
        ['id' => 'ekstrakurikuler', 'label' => 'Ekstrakurikuler'],
        ['id' => 'aplikasi', 'label' => 'Aplikasi Digital'],
        ['id' => 'galeri', 'label' => 'Galeri Kegiatan'],
        ['id' => 'kelembagaan', 'label' => 'Komitmen Kelembagaan'],
        ['id' => 'cta_faq', 'label' => 'CTA & Tanya Jawab'],
        ['id' => 'lokasi', 'label' => 'Lokasi & Kontak'],
    ];

    public function mount(): void
    {
        $stored = json_decode(Setting::get('homepage_sections', '[]'), true) ?: [];
        $byId = collect($stored)->keyBy('id');

        // Gabungkan urutan tersimpan dengan section baru yang belum pernah
        // disimpan (mis. setelah upgrade menambah section baru) — section
        // baru ditambahkan di akhir, default tampil.
        $sections = collect(self::DEFAULT_SECTIONS)->map(fn ($s) => $byId->get($s['id'], [
            ...$s,
            'is_visible' => true,
        ]));
        $ordered = $byId->count()
            ? $byId->values()->map(fn ($s) => ['id' => $s['id'], 'label' => $s['label'] ?? collect(self::DEFAULT_SECTIONS)->firstWhere('id', $s['id'])['label'] ?? $s['id'], 'is_visible' => $s['is_visible'] ?? true])
                ->merge($sections->whereNotIn('id', $byId->keys()))
            : $sections;

        $this->form->fill(['sections' => $ordered->values()->all()]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Urutan & Tampilan Section Beranda')
                    ->description('Seret untuk mengatur urutan tampil, matikan toggle untuk menyembunyikan section dari beranda. Isi konten tiap section (berita, agenda, dsb.) dikelola di menu masing-masing.')
                    ->schema([
                        Repeater::make('sections')
                            ->label('')
                            ->hiddenLabel()
                            ->reorderable()
                            ->reorderableWithButtons()
                            ->addable(false)
                            ->deletable(false)
                            ->itemLabel(fn (array $state) => $state['label'] ?? null)
                            ->schema([
                                Hidden::make('id'),
                                Hidden::make('label'),
                                Toggle::make('is_visible')
                                    ->label(fn ($get) => $get('label'))
                                    ->inline(false),
                            ])
                            ->columns(1),
                    ]),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label('Simpan')->submit('save'),
        ];
    }

    public function save(): void
    {
        $sections = $this->form->getState()['sections'] ?? [];

        Setting::updateOrCreate(
            ['key' => 'homepage_sections'],
            ['value' => json_encode($sections), 'group' => 'general'],
        );

        Notification::make()->title('Susunan beranda disimpan.')->success()->send();
    }
}
