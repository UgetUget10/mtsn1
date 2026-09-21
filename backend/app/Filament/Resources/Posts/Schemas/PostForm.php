<?php

namespace App\Filament\Resources\Posts\Schemas;

use App\Filament\Concerns\BuildsSeoSection;
use App\Filament\Concerns\HasTranslatableTabs;
use App\Models\Post;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PostForm
{
    use BuildsSeoSection;
    use HasTranslatableTabs;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Konten')
                    ->columnSpan(2)
                    ->schema([
                        static::translatableTabs('content_tabs', fn (string $locale) => [
                            TextInput::make("title.{$locale}")
                                ->label('Judul')
                                ->required($locale === array_key_first(config('translatable.locales', ['id' => null])))
                                ->live(onBlur: true)
                                ->afterStateUpdated(function (?string $state, callable $set) use ($locale) {
                                    // Slug hanya mengikuti judul locale default (kunci pertama
                                    // di config('translatable.locales')) agar tidak berubah-ubah
                                    // saat mengetik di tab bahasa lain.
                                    if ($locale === array_key_first(config('translatable.locales', ['id' => null]))) {
                                        $set('slug', Str::slug((string) $state));
                                    }
                                }),
                            Textarea::make("excerpt.{$locale}")
                                ->label('Ringkasan')
                                ->rows(3)
                                ->maxLength(500)
                                ->helperText('Opsional. Bila dikosongkan, ringkasan dibuat otomatis dari isi (± 55 kata), atau dari teks sebelum tag <!--more--> bila ada.')
                                ->columnSpanFull(),
                            RichEditor::make("body.{$locale}")
                                ->label('Isi')
                                ->helperText('Tempel tautan YouTube, Vimeo, Spotify, Google Maps, Instagram, X, atau TikTok di baris tersendiri — otomatis jadi sematan (auto-embed ala WordPress). Sisipkan <!--more--> untuk menandai batas ringkasan "Baca selengkapnya". Klik ikon lampiran (📎) untuk menyisipkan gambar ke isi artikel.')
                                // Toolbar default Filament TIDAK menyertakan warna
                                // teks/highlight/garis horizontal/rata-kanan-kiri
                                // meski tool-nya sudah tersedia bawaan — hanya perlu
                                // didaftarkan di sini. `alignJustify` ditambah di
                                // grup align, `textColor`+`highlight` di grup format
                                // teks, `horizontalRule` di grup blok.
                                ->toolbarButtons([
                                    ['bold', 'italic', 'underline', 'strike', 'subscript', 'superscript', 'link', 'textColor', 'highlight'],
                                    ['h2', 'h3'],
                                    ['alignStart', 'alignCenter', 'alignEnd', 'alignJustify'],
                                    ['blockquote', 'codeBlock', 'horizontalRule', 'bulletList', 'orderedList'],
                                    ['table', 'attachFiles'],
                                    ['undo', 'redo'],
                                ])
                                // Tinggi minimum area ketik dinaikkan — bawaan
                                // Filament terasa pendek untuk artikel berita yang
                                // biasanya beberapa paragraf; lihat CSS kustom di
                                // AdminPanelProvider (selector .fi-rich-editor
                                // .ProseMirror) untuk penyesuaian tingginya.
                                ->extraAttributes(['class' => 'mtsn1-rich-editor-tall'])
                                ->columnSpanFull(),
                        ])->columnSpanFull(),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->columnSpanFull(),
                    ]),

                Section::make('Publikasi')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('status')
                            ->options(fn () => Post::assignableStatuses(auth()->user()))
                            ->default('draft')
                            ->live()
                            ->helperText(fn ($state) => $state === 'scheduled'
                                ? 'Akan terbit otomatis saat "Tanggal terbit" tercapai.'
                                : null)
                            ->required(),
                        DateTimePicker::make('published_at')
                            ->label('Tanggal terbit')
                            ->default(now())
                            ->required(fn ($get) => $get('status') === 'scheduled'),
                        Select::make('visibility')
                            ->label('Visibilitas')
                            ->options(Post::visibilityOptions())
                            ->default(Post::VISIBILITY_PUBLIC)
                            ->live()
                            ->helperText(fn ($state) => match ($state) {
                                Post::VISIBILITY_PASSWORD => 'Isi hanya tampil setelah pengunjung memasukkan kata sandi.',
                                Post::VISIBILITY_PRIVATE => 'Tidak muncul di daftar, feed, atau sitemap publik.',
                                default => null,
                            })
                            ->required(),
                        TextInput::make('password')
                            ->label('Kata sandi')
                            ->password()
                            ->revealable()
                            ->visible(fn ($get) => $get('visibility') === Post::VISIBILITY_PASSWORD)
                            ->required(fn ($get, string $operation) => $operation === 'create'
                                && $get('visibility') === Post::VISIBILITY_PASSWORD)
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->helperText('Kosongkan saat menyunting bila tidak ingin mengganti kata sandi.'),
                        Select::make('category_id')
                            ->label('Kategori utama')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->required(),
                            ]),
                        Select::make('categories')
                            ->label('Kategori tambahan')
                            ->relationship('categories', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload(),
                        Select::make('tags')
                            ->label('Tag')
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('name')->required(),
                            ]),
                        Toggle::make('is_featured')
                            ->label('Sorotan / headline'),
                        Toggle::make('meta.comments_closed')
                            ->label('Tutup komentar untuk artikel ini')
                            ->helperText('WordPress: kotak "Allow comments" (kebalikan). Pengaturan global ada di Pengaturan → Diskusi.'),
                        SpatieMediaLibraryFileUpload::make('cover')
                            ->collection('cover')
                            ->label('Gambar sampul')
                            ->image()
                            ->directory('posts')
                            ->imageEditor()
                            // Berita terbit tanpa gambar sampul terlihat kosong di
                            // daftar & kartu berita frontend — wajibkan sebelum
                            // status bisa diubah ke "Terbit".
                            ->required(fn ($get) => $get('status') === Post::STATUS_PUBLISHED)
                            ->helperText(fn ($get) => $get('status') === Post::STATUS_PUBLISHED
                                ? 'Wajib diisi sebelum berita bisa diterbitkan.'
                                : null),
                        // Disimpan sebagai custom property pada entri media
                        // (bukan kolom tabel) — lihat EditPost/CreatePost
                        // ::syncCoverMeta(). PostResource mengirimnya ke frontend
                        // sebagai `cover_caption` / `cover_credit`.
                        TextInput::make('cover_caption')
                            ->label('Keterangan gambar')
                            ->maxLength(255)
                            ->helperText('Penjelasan singkat isi foto. Tampil di bawah gambar sampul.'),
                        TextInput::make('cover_credit')
                            ->label('Sumber / kredit foto')
                            ->maxLength(255)
                            ->placeholder('Humas MTsN 1 Kota Malang')
                            ->helperText('Nama fotografer atau sumber. Tampil miring sebagai "Foto: ...".'),
                    ]),

                static::seoSection(
                    urlBase: rtrim((string) config('services.frontend.url'), '/').'/berita/',
                    titlePath: 'data.title',
                    fallbackDesc: 'data.excerpt',
                )->columnSpan(1),

                // "Custom Fields" ala WordPress — pasangan kunci/nilai bebas untuk
                // data tambahan yang tidak punya field khusus (mis. narasumber,
                // nomor SK, sumber data lomba). Disimpan di meta.custom (BUKAN
                // kolom baru) supaya tidak perlu migrasi tiap kali kebutuhan baru
                // muncul — persis filosofi wp_postmeta. Diteruskan ke frontend
                // sebagai `custom_fields` (lihat App\Http\Resources\PostResource).
                Section::make('Kolom Kustom')
                    ->description('Data tambahan bebas (kunci/nilai) untuk kebutuhan yang tidak punya field khusus — setara "Custom Fields" WordPress.')
                    ->collapsed()
                    ->columnSpan(2)
                    ->schema([
                        Repeater::make('meta.custom')
                            ->label('')
                            ->addActionLabel('Tambah kolom kustom')
                            ->schema([
                                TextInput::make('key')
                                    ->label('Nama')
                                    ->required()
                                    ->maxLength(100),
                                TextInput::make('value')
                                    ->label('Nilai')
                                    ->maxLength(500),
                            ])
                            ->columns(2)
                            ->reorderable(false)
                            ->defaultItems(0),
                    ]),
            ])
            ->columns(3);
    }
}
