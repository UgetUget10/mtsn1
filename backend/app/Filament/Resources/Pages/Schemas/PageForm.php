<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Filament\Concerns\BuildsSeoSection;
use App\Filament\Concerns\HasTranslatableTabs;
use App\Models\Document;
use App\Models\Gallery;
use App\Models\Page;
use App\Models\ReusableBlock;
use App\Support\Blocks\BlockTypes;
use Closure;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PageForm
{
    use BuildsSeoSection;
    use HasTranslatableTabs;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Halaman')
                    ->columns(2)
                    ->schema([
                        static::translatableTabs('page_translatable_tabs', fn (string $locale) => [
                            TextInput::make("title.{$locale}")
                                ->label('Judul')
                                ->required($locale === array_key_first(config('translatable.locales', ['id' => null]))),
                            Textarea::make("meta_description.{$locale}")
                                ->label('Meta description (SEO)')
                                ->rows(2)
                                ->maxLength(160)
                                ->helperText('Ringkasan singkat untuk mesin pencari (maks. 160 karakter).'),
                        ])->columnSpanFull(),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->helperText('Kosongkan untuk otomatis dari judul.')
                            ->unique(ignoreRecord: true),
                        Select::make('parent_id')
                            ->label('Halaman induk')
                            ->helperText('Wp: Page Attributes → Parent. Kosongkan untuk halaman tingkat atas.')
                            ->searchable()
                            ->preload()
                            ->options(function (?Page $record) {
                                return Page::query()
                                    ->where('slug', '!=', Page::HOMEPAGE_SLUG)
                                    ->when($record, fn ($q) => $q
                                        ->whereKeyNot($record->getKey())
                                        ->whereNotIn('id', $record->children()->pluck('id')))
                                    ->orderBy('title')
                                    ->pluck('title', 'id');
                            }),
                        Select::make('template')
                            ->label('Template')
                            ->helperText('Wp: Page Attributes → Template. Menentukan tata letak halaman di frontend.')
                            ->options(Page::templateOptions())
                            ->default('default')
                            ->selectablePlaceholder(false)
                            ->native(false),
                        Toggle::make('is_published')
                            ->label('Terbitkan')
                            ->default(true),
                        TextInput::make('order')
                            ->label('Urutan menu')
                            ->required()
                            ->numeric()
                            ->default(0),
                    ]),

                Section::make('Konten Blok')
                    ->description('Susun isi halaman dari blok-blok berikut. Konten lama di kolom "Isi (lama)" di bawah tetap tampil otomatis sebagai blok teks jika daftar blok kosong.')
                    ->schema([
                        self::builder(),
                    ]),

                Section::make('Isi (lama)')
                    ->description('Rich text bebas — cara lama sebelum ada blok. Masih didukung, tapi disarankan pakai Konten Blok di atas untuk halaman baru.')
                    ->collapsed()
                    ->schema([
                        RichEditor::make('body')
                            ->label('Isi halaman')
                            ->columnSpanFull(),
                    ]),

                // Sebelumnya PageResource::seo() membaca meta.seo_title/canonical/
                // noindex/og_image padahal form tak punya field-nya sama sekali —
                // nilai itu mustahil diisi editor. Sekarang tersedia.
                static::seoSection(
                    urlBase: rtrim((string) config('services.frontend.url'), '/').'/profil/',
                    titlePath: 'data.title',
                    fallbackDesc: 'data.meta_description',
                ),
            ])
            ->columns(1);
    }

    /**
     * Skema field per tipe blok — satu sumber kebenaran dipakai builder() di
     * bawah (dibungkus Block::make()->schema()) DAN App\Filament\Pages\
     * PageBlockEditor (modal widget kanvas visual, dibuka via iframe dari
     * canvas-editor/). Closure dipanggil ulang tiap dibutuhkan supaya field
     * yang dihasilkan selalu instance baru — komponen Filament tidak aman
     * dipakai ulang lintas container (statenya ter-bind ke satu tempat),
     * jadi TIDAK bisa diekstrak dari Builder yang sudah dirakit.
     *
     * @return array<string, Closure(): array<int, \Filament\Schemas\Components\Component>>
     */
    public static function blockFieldFactories(): array
    {
        return [
            BlockTypes::HERO => fn () => [
                TextInput::make('eyebrow')->label('Label kecil di atas judul'),
                TextInput::make('title')->label('Judul')->required(),
                Textarea::make('subtitle')->label('Subjudul')->rows(2),
                FileUpload::make('image')->label('Gambar latar')->image()->directory('pages/blocks'),
                TextInput::make('cta_label')->label('Teks tombol'),
                TextInput::make('cta_href')->label('Tautan tombol')->url(),
            ],

            BlockTypes::RICH_TEXT => fn () => [
                TextInput::make('heading')->label('Judul (opsional)'),
                RichEditor::make('body')->label('Isi')->required()->columnSpanFull(),
            ],

            BlockTypes::CARD_GRID => fn () => [
                TextInput::make('heading')->label('Judul (opsional)'),
                Select::make('columns')
                    ->label('Jumlah kolom')
                    ->options([2 => '2', 3 => '3', 4 => '4'])
                    ->default(2)
                    ->required(),
                Repeater::make('cards')
                    ->label('Kartu')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('title')->label('Judul')->required(),
                        Textarea::make('description')->label('Deskripsi')->rows(2)->columnSpanFull(),
                        TextInput::make('icon')->label('Ikon (opsional, nama heroicon)'),
                        FileUpload::make('image')->label('Gambar (opsional)')->image()->directory('pages/blocks'),
                        TextInput::make('href')->label('Tautan (opsional)')->url(),
                    ])
                    ->columns(2)
                    ->defaultItems(1)
                    ->addActionLabel('Tambah kartu'),
            ],

            BlockTypes::ACCORDION => fn () => [
                TextInput::make('heading')->label('Judul (opsional)'),
                Repeater::make('items')
                    ->label('Item')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('question')->label('Pertanyaan / judul')->required(),
                        RichEditor::make('answer')->label('Jawaban / isi')->required(),
                    ])
                    ->defaultItems(1)
                    ->addActionLabel('Tambah item'),
            ],

            BlockTypes::CTA => fn () => [
                TextInput::make('heading')->label('Judul')->required(),
                Textarea::make('text')->label('Teks')->rows(2)->columnSpanFull(),
                TextInput::make('button_label')->label('Teks tombol')->required(),
                TextInput::make('button_href')->label('Tautan tombol')->required()->url(),
                Select::make('style')
                    ->label('Gaya')
                    ->options(['primary' => 'Utama (solid)', 'outline' => 'Outline'])
                    ->default('primary'),
            ],

            BlockTypes::FILE_LIST => fn () => [
                TextInput::make('heading')->label('Judul (opsional)'),
                Repeater::make('files')
                    ->label('Berkas')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('document_id')
                            ->label('Dokumen')
                            ->options(fn () => Document::query()->pluck('title', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->defaultItems(1)
                    ->addActionLabel('Tambah berkas'),
            ],

            BlockTypes::GALLERY_BLOCK => fn () => [
                TextInput::make('heading')->label('Judul (opsional)'),
                Select::make('gallery_id')
                    ->label('Pilih galeri')
                    ->options(fn () => Gallery::query()->pluck('title', 'id'))
                    ->searchable()
                    ->required(),
            ],

            BlockTypes::STATS => fn () => [
                Repeater::make('items')
                    ->label('Angka')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('label')->label('Label')->required(),
                        TextInput::make('value')->label('Nilai')->required(),
                        TextInput::make('icon')->label('Ikon (opsional, nama heroicon)'),
                    ])
                    ->columns(3)
                    ->defaultItems(1)
                    ->addActionLabel('Tambah angka'),
            ],

            BlockTypes::HUB_GRID => fn () => [
                Toggle::make('numbered')->label('Tampilkan nomor urut')->default(true),
                Repeater::make('items')
                    ->label('Tautan')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('title')->label('Judul')->required(),
                        Textarea::make('desc')->label('Deskripsi')->rows(2)->columnSpanFull(),
                        TextInput::make('href')->label('Tautan')->required(),
                        TextInput::make('icon')->label('Ikon (opsional, nama heroicon)'),
                    ])
                    ->columns(2)
                    ->defaultItems(1)
                    ->addActionLabel('Tambah tautan'),
            ],

            BlockTypes::TABLE => fn () => [
                TextInput::make('heading')->label('Judul (opsional)'),
                Repeater::make('columns')
                    ->label('Kolom')
                    ->simple(TextInput::make('label')->label('Nama kolom')->required())
                    ->defaultItems(2)
                    ->addActionLabel('Tambah kolom'),
                Repeater::make('rows')
                    ->label('Baris')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('cells')
                            ->label('Sel')
                            ->simple(TextInput::make('value')->label('Isi sel')->required())
                            ->defaultItems(2)
                            ->addActionLabel('Tambah sel'),
                    ])
                    ->defaultItems(1)
                    ->addActionLabel('Tambah baris'),
            ],

            BlockTypes::STEPS => fn () => [
                TextInput::make('heading')->label('Judul (opsional)'),
                Repeater::make('items')
                    ->label('Langkah')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('title')->label('Judul langkah')->required(),
                        Textarea::make('description')->label('Deskripsi')->rows(2),
                    ])
                    ->defaultItems(1)
                    ->addActionLabel('Tambah langkah')
                    ->reorderable(),
            ],

            BlockTypes::QUOTE => fn () => [
                RichEditor::make('text')->label('Isi kutipan')->required()->columnSpanFull(),
                TextInput::make('attribution')->label('Sumber / nama (opsional)'),
            ],

            BlockTypes::LINK_CARDS => fn () => [
                TextInput::make('heading')->label('Judul (opsional)'),
                Repeater::make('items')
                    ->label('Kartu')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('title')->label('Judul')->required(),
                        Textarea::make('description')->label('Deskripsi')->rows(2)->columnSpanFull(),
                        TextInput::make('href')->label('Tautan')->required(),
                        Toggle::make('external')->label('Buka di tab baru'),
                    ])
                    ->columns(2)
                    ->defaultItems(1)
                    ->addActionLabel('Tambah kartu'),
            ],

            BlockTypes::CHECKLIST => fn () => [
                TextInput::make('heading')->label('Judul (opsional)'),
                Repeater::make('items')
                    ->label('Item')
                    ->simple(TextInput::make('text')->label('Teks item')->required())
                    ->defaultItems(1)
                    ->addActionLabel('Tambah item'),
            ],

            BlockTypes::ICON_LIST => fn () => [
                TextInput::make('heading')->label('Judul (opsional)'),
                Repeater::make('items')
                    ->label('Item')
                    ->simple(TextInput::make('text')->label('Teks item')->required())
                    ->defaultItems(1)
                    ->addActionLabel('Tambah item'),
            ],

            BlockTypes::TIMELINE => fn () => [
                TextInput::make('heading')->label('Judul (opsional)'),
                Repeater::make('items')
                    ->label('Titik waktu')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('label')->label('Label waktu (mis. jam atau tahun)')->required(),
                        TextInput::make('title')->label('Judul (opsional)'),
                        Textarea::make('description')->label('Deskripsi')->rows(2),
                    ])
                    ->columns(3)
                    ->defaultItems(1)
                    ->addActionLabel('Tambah titik waktu')
                    ->reorderable(),
            ],

            // wp: Synced Pattern / Reusable Block. Isinya di-inline saat
            // halaman diserialisasi (PageResource::expandReusable()).
            BlockTypes::REUSABLE => fn () => [
                Select::make('slug')
                    ->label('Pilih blok')
                    ->options(fn () => ReusableBlock::query()
                        ->where('is_active', true)
                        ->orderBy('name')
                        ->pluck('name', 'slug')
                        ->all())
                    ->searchable()
                    ->required()
                    ->helperText('Isi blok ini disisipkan saat halaman ditampilkan — ubah sekali di menu "Blok Dipakai Ulang", berubah di semua halaman yang memakainya. Blok dipakai-ulang di dalam blok dipakai-ulang tidak ikut disisipkan.'),
            ],
        ];
    }

    /**
     * Label + ikon + jumlah kolom tampilan per tipe blok — dipakai builder()
     * di bawah untuk merakit Block::make(). Urutan array ini ikut menentukan
     * urutan tampil di block picker Filament.
     *
     * @return array<string, array{0: string, 1: string, 2: int}>
     */
    private static function blockMeta(): array
    {
        return [
            BlockTypes::HERO => ['Hero', 'heroicon-o-photo', 2],
            BlockTypes::RICH_TEXT => ['Teks Bebas', 'heroicon-o-document-text', 1],
            BlockTypes::CARD_GRID => ['Grid Kartu', 'heroicon-o-squares-2x2', 2],
            BlockTypes::ACCORDION => ['Akordion / FAQ', 'heroicon-o-bars-3-bottom-left', 1],
            BlockTypes::CTA => ['CTA (Ajakan Bertindak)', 'heroicon-o-megaphone', 2],
            BlockTypes::FILE_LIST => ['Daftar Berkas', 'heroicon-o-paper-clip', 1],
            BlockTypes::GALLERY_BLOCK => ['Galeri', 'heroicon-o-photo', 2],
            BlockTypes::STATS => ['Statistik', 'heroicon-o-chart-bar', 1],
            BlockTypes::HUB_GRID => ['Grid Tautan (Hub)', 'heroicon-o-squares-plus', 1],
            BlockTypes::TABLE => ['Tabel', 'heroicon-o-table-cells', 1],
            BlockTypes::STEPS => ['Langkah Bernomor', 'heroicon-o-list-bullet', 1],
            BlockTypes::QUOTE => ['Kutipan', 'heroicon-o-chat-bubble-left-right', 1],
            BlockTypes::LINK_CARDS => ['Kartu Tautan', 'heroicon-o-arrow-top-right-on-square', 1],
            BlockTypes::CHECKLIST => ['Daftar Centang', 'heroicon-o-check-circle', 1],
            BlockTypes::ICON_LIST => ['Daftar Berikon', 'heroicon-o-list-bullet', 1],
            BlockTypes::TIMELINE => ['Linimasa', 'heroicon-o-clock', 1],
            BlockTypes::REUSABLE => ['Blok Dipakai Ulang', 'heroicon-o-rectangle-stack', 1],
        ];
    }

    /**
     * Daftar blok konten. Nama state bisa diganti supaya definisi yang sama
     * dipakai ulang oleh ReusableBlockForm (kolomnya bernama `content`,
     * bukan `blocks`) — satu sumber kebenaran untuk kedua form.
     */
    public static function builder(string $name = 'blocks'): Builder
    {
        return Builder::make($name)
            ->label('Blok konten')
            ->hiddenLabel()
            ->addActionLabel('Tambah blok')
            ->blockPickerColumns(2)
            ->collapsible()
            ->blocks(
                collect(static::blockFieldFactories())
                    ->map(function (Closure $fields, string $type) {
                        [$label, $icon, $columns] = static::blockMeta()[$type];

                        return Block::make($type)
                            ->label($label)
                            ->icon($icon)
                            ->schema($fields())
                            ->columns($columns);
                    })
                    ->values()
                    ->all(),
            );
    }
}
