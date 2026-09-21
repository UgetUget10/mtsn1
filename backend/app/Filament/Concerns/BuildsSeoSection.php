<?php

namespace App\Filament\Concerns;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\View;

/**
 * Metabox SEO bersama untuk Post & Page — setara kotak Yoast/RankMath di
 * WordPress: judul & deskripsi khusus mesin pencari, URL kanonik, toggle
 * noindex, gambar share (og:image), plus pratinjau cuplikan live.
 *
 * Semua nilai disimpan di kolom `meta` (json) masing-masing model dan dibaca
 * kembali oleh App\Http\Resources\{Post,Page}Resource::seo().
 */
trait BuildsSeoSection
{
    /**
     * @param  string  $urlBase       Awalan URL publik, mis. ".../berita/"
     * @param  string  $titlePath     Path state judul (untuk fallback pratinjau)
     * @param  string  $fallbackDesc  Path state ringkasan/meta_description
     */
    protected static function seoSection(
        string $urlBase,
        string $titlePath,
        string $fallbackDesc,
    ): Section {
        return Section::make('SEO')
            ->description('Bagaimana konten ini tampil di hasil pencarian dan saat dibagikan.')
            ->collapsed()
            ->schema([
                View::make('filament.seo-snippet')
                    ->viewData([
                        'urlBase' => $urlBase,
                        'titlePath' => $titlePath,
                        'slugPath' => 'data.slug',
                        'seoTitlePath' => 'data.meta.seo_title',
                        'seoDescPath' => 'data.meta.seo_description',
                        'fallbackDescPath' => $fallbackDesc,
                    ])
                    ->columnSpanFull(),

                TextInput::make('meta.seo_title')
                    ->label('Judul SEO')
                    ->helperText('Kosongkan untuk memakai judul konten. Ideal di bawah 60 karakter.')
                    ->maxLength(70)
                    ->live(onBlur: true),

                TextInput::make('meta.canonical')
                    ->label('URL kanonik')
                    ->url()
                    ->helperText('Isi hanya bila konten ini salinan dari URL lain.')
                    ->placeholder('Kosongkan jika ini konten asli'),

                Textarea::make('meta.seo_description')
                    ->label('Deskripsi meta')
                    ->helperText('Kosongkan untuk memakai ringkasan. Ideal 70–160 karakter.')
                    ->rows(2)
                    ->maxLength(160)
                    ->live(onBlur: true)
                    ->columnSpanFull(),

                FileUpload::make('meta.og_image')
                    ->label('Gambar share (og:image)')
                    ->helperText('Dipakai saat ditautkan di WhatsApp/Facebook. Kosongkan untuk memakai gambar sampul. Rasio ideal 1200×630.')
                    ->image()
                    ->imageEditor()
                    ->directory('seo')
                    ->maxSize(2048),

                Toggle::make('meta.noindex')
                    ->label('Sembunyikan dari mesin pencari (noindex)')
                    ->helperText('Halaman tetap bisa dibuka lewat tautan langsung.'),
            ])
            ->columns(2);
    }
}
