<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Filament\Concerns\BuildsSeoSection;
use App\Filament\Concerns\HasTranslatableTabs;
use App\Models\Document;
use App\Models\Gallery;
use App\Models\Page;
use App\Models\ReusableBlock;
use App\Support\Blocks\BlockTypes;
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
            ->blocks([
                Block::make(BlockTypes::HERO)
                    ->label('Hero')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        TextInput::make('eyebrow')->label('Label kecil di atas judul'),
                        TextInput::make('title')->label('Judul')->required(),
                        Textarea::make('subtitle')->label('Subjudul')->rows(2),
                        FileUpload::make('image')->label('Gambar latar')->image()->directory('pages/blocks'),
                        TextInput::make('cta_label')->label('Teks tombol'),
                        TextInput::make('cta_href')->label('Tautan tombol')->url(),
                    ])
                    ->columns(2),

                Block::make(BlockTypes::RICH_TEXT)
                    ->label('Teks Bebas')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        TextInput::make('heading')->label('Judul (opsional)'),
                        RichEditor::make('body')->label('Isi')->required()->columnSpanFull(),
                    ])
                    ->columns(1),

                Block::make(BlockTypes::CARD_GRID)
                    ->label('Grid Kartu')
                    ->icon('heroicon-o-squares-2x2')
                    ->schema([
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
                    ])
                    ->columns(2),

                Block::make(BlockTypes::ACCORDION)
                    ->label('Akordion / FAQ')
                    ->icon('heroicon-o-bars-3-bottom-left')
                    ->schema([
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
                    ])
                    ->columns(1),

                Block::make(BlockTypes::CTA)
                    ->label('CTA (Ajakan Bertindak)')
                    ->icon('heroicon-o-megaphone')
                    ->schema([
                        TextInput::make('heading')->label('Judul')->required(),
                        Textarea::make('text')->label('Teks')->rows(2)->columnSpanFull(),
                        TextInput::make('button_label')->label('Teks tombol')->required(),
                        TextInput::make('button_href')->label('Tautan tombol')->required()->url(),
                        Select::make('style')
                            ->label('Gaya')
                            ->options(['primary' => 'Utama (solid)', 'outline' => 'Outline'])
                            ->default('primary'),
                    ])
                    ->columns(2),

                Block::make(BlockTypes::FILE_LIST)
                    ->label('Daftar Berkas')
                    ->icon('heroicon-o-paper-clip')
                    ->schema([
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
                    ])
                    ->columns(1),

                Block::make(BlockTypes::GALLERY_BLOCK)
                    ->label('Galeri')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        TextInput::make('heading')->label('Judul (opsional)'),
                        Select::make('gallery_id')
                            ->label('Pilih galeri')
                            ->options(fn () => Gallery::query()->pluck('title', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->columns(2),

                Block::make(BlockTypes::STATS)
                    ->label('Statistik')
                    ->icon('heroicon-o-chart-bar')
                    ->schema([
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
                    ])
                    ->columns(1),

                Block::make(BlockTypes::HUB_GRID)
                    ->label('Grid Tautan (Hub)')
                    ->icon('heroicon-o-squares-plus')
                    ->schema([
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
                    ])
                    ->columns(1),

                Block::make(BlockTypes::TABLE)
                    ->label('Tabel')
                    ->icon('heroicon-o-table-cells')
                    ->schema([
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
                    ])
                    ->columns(1),

                Block::make(BlockTypes::STEPS)
                    ->label('Langkah Bernomor')
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
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
                    ])
                    ->columns(1),

                Block::make(BlockTypes::QUOTE)
                    ->label('Kutipan')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->schema([
                        RichEditor::make('text')->label('Isi kutipan')->required()->columnSpanFull(),
                        TextInput::make('attribution')->label('Sumber / nama (opsional)'),
                    ])
                    ->columns(1),

                Block::make(BlockTypes::LINK_CARDS)
                    ->label('Kartu Tautan')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->schema([
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
                    ])
                    ->columns(1),

                Block::make(BlockTypes::CHECKLIST)
                    ->label('Daftar Centang')
                    ->icon('heroicon-o-check-circle')
                    ->schema([
                        TextInput::make('heading')->label('Judul (opsional)'),
                        Repeater::make('items')
                            ->label('Item')
                            ->simple(TextInput::make('text')->label('Teks item')->required())
                            ->defaultItems(1)
                            ->addActionLabel('Tambah item'),
                    ])
                    ->columns(1),

                Block::make(BlockTypes::ICON_LIST)
                    ->label('Daftar Berikon')
                    ->icon('heroicon-o-list-bullet')
                    ->schema([
                        TextInput::make('heading')->label('Judul (opsional)'),
                        Repeater::make('items')
                            ->label('Item')
                            ->simple(TextInput::make('text')->label('Teks item')->required())
                            ->defaultItems(1)
                            ->addActionLabel('Tambah item'),
                    ])
                    ->columns(1),

                Block::make(BlockTypes::TIMELINE)
                    ->label('Linimasa')
                    ->icon('heroicon-o-clock')
                    ->schema([
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
                    ])
                    ->columns(1),

                // wp: Synced Pattern / Reusable Block. Isinya di-inline saat
                // halaman diserialisasi (PageResource::expandReusable()).
                Block::make(BlockTypes::REUSABLE)
                    ->label('Blok Dipakai Ulang')
                    ->icon('heroicon-o-rectangle-stack')
                    ->schema([
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
                    ])
                    ->columns(1),
            ]);
    }
}
