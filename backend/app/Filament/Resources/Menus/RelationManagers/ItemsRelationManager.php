<?php

namespace App\Filament\Resources\Menus\RelationManagers;

use App\Filament\Concerns\HasTranslatableTabs;
use App\Filament\Concerns\SyncsTranslatableFields;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\Tag;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    use HasTranslatableTabs;
    use SyncsTranslatableFields;

    protected static string $relationship = 'allItems';

    protected static ?string $title = 'Item Menu';

    protected function translatableAttributes(): array
    {
        return ['label'];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        static::translatableTabs('menu_item_translatable_tabs', fn (string $locale) => [
                            TextInput::make("label.{$locale}")
                                ->label('Label')
                                ->required($locale === array_key_first(config('translatable.locales', ['id' => null]))),
                        ])->columnSpanFull(),
                        Select::make('parent_id')
                            ->label('Induk (opsional)')
                            ->helperText('Kosongkan untuk item level teratas. Isi untuk menjadikan sub-item — bisa dipilih hingga 2 tingkat ke bawah (mega-menu → grup → leaf). Tampilan header hanya dirancang sampai 3 tingkat; lebih dalam dari itu tidak akan terlihat di navigasi meski tersimpan.')
                            ->relationship(
                                'parent',
                                'label',
                                // Sampai 2 tingkat ke bawah (bukan hanya level teratas) —
                                // konsisten dengan MenuController::show() yang eager-load
                                // 3 tingkat (item > children > children). Cucu (tingkat ke-3)
                                // sengaja TIDAK bisa dijadikan induk lagi karena frontend
                                // (SiteHeader mega-menu) tidak merender tingkat ke-4.
                                fn ($query) => $query->where('menu_id', $this->getOwnerRecord()->id)
                                    ->where(function ($q) {
                                        $q->whereNull('parent_id')
                                            ->orWhereHas('parent', fn ($p) => $p->whereNull('parent_id'));
                                    }),
                            )
                            ->searchable()
                            ->preload(),
                        Select::make('type')
                            ->label('Tipe tautan')
                            ->options([
                                'custom_url' => 'URL bebas',
                                'page' => 'Halaman (Page)',
                                'post' => 'Berita (Post)',
                                'category' => 'Arsip kategori',
                                'tag' => 'Arsip tag',
                                'section' => 'Pengelompok (tanpa tautan)',
                            ])
                            ->default('custom_url')
                            ->live()
                            ->required()
                            ->helperText('Berita/Kategori/Tag menyimpan referensi — tautannya ikut slug terbaru, tak patah bila slug diubah.'),
                        TextInput::make('url')
                            ->label('URL')
                            ->helperText('Boleh path internal (mis. /akademik) atau URL penuh.')
                            ->visible(fn ($get) => $get('type') === 'custom_url')
                            ->required(fn ($get) => $get('type') === 'custom_url'),
                        Select::make('page_id')
                            ->label('Halaman')
                            ->options(fn () => Page::query()->pluck('title', 'id'))
                            ->searchable()
                            ->visible(fn ($get) => $get('type') === 'page')
                            ->required(fn ($get) => $get('type') === 'page'),
                        Select::make('post_id')
                            ->label('Berita')
                            // `title` translatable → map lewat model agar accessor
                            // menerjemahkan (pluck() mentah kembalikan JSON).
                            ->options(fn () => Post::query()->orderByDesc('published_at')->limit(300)
                                ->get(['id', 'title'])->pluck('title', 'id'))
                            ->searchable()
                            ->visible(fn ($get) => $get('type') === 'post')
                            ->required(fn ($get) => $get('type') === 'post'),
                        Select::make('category_id')
                            ->label('Kategori')
                            ->options(fn () => Category::query()->where('type', 'post')
                                ->get(['id', 'name'])->pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn ($get) => $get('type') === 'category')
                            ->required(fn ($get) => $get('type') === 'category'),
                        Select::make('tag_id')
                            ->label('Tag')
                            ->options(fn () => Tag::query()->get(['id', 'name'])->pluck('name', 'id'))
                            ->searchable()
                            ->visible(fn ($get) => $get('type') === 'tag')
                            ->required(fn ($get) => $get('type') === 'tag'),
                        TextInput::make('icon')
                            ->label('Ikon (opsional, path SVG 24×24)')
                            ->columnSpanFull(),
                        TextInput::make('order')
                            ->label('Urutan')
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),

                Section::make('Kartu Aksen (opsional)')
                    ->description('Hanya tampil untuk item level teratas — kartu highlight di dalam mega-panel, mis. CTA PPDB.')
                    ->collapsed()
                    ->schema([
                        TextInput::make('feature_title')->label('Judul kartu'),
                        Textarea::make('feature_text')->label('Teks kartu')->rows(2),
                        TextInput::make('feature_cta')->label('Teks tombol'),
                    ]),
            ]);
    }

    /**
     * Kosongkan kolom acuan yang tidak dipakai tipe terpilih, supaya berganti
     * tipe (mis. dari "post" ke "page") tak meninggalkan `post_id` basi yang
     * bisa membuat `resolvedUrl()` salah.
     */
    private function nullifyUnusedRefs(array $data): array
    {
        $keep = match ($data['type'] ?? null) {
            'page' => 'page_id',
            'post' => 'post_id',
            'category' => 'category_id',
            'tag' => 'tag_id',
            'custom_url' => 'url',
            default => null,
        };

        foreach (['page_id', 'post_id', 'category_id', 'tag_id', 'url'] as $col) {
            if ($col !== $keep) {
                $data[$col] = null;
            }
        }

        return $data;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->reorderable('order')
            ->defaultSort('order')
            ->columns([
                TextColumn::make('label')->label('Label')->searchable(),
                TextColumn::make('parent.label')->label('Induk')->placeholder('— level teratas —'),
                TextColumn::make('type')->label('Tipe')->badge(),

                // Tautan tujuan + peringatan bila mati. Item bertautan mati
                // (mis. halaman tujuannya sudah dihapus) TIDAK dikirim ke
                // frontend oleh MenuController, jadi diam-diam hilang dari
                // navigasi — admin perlu melihatnya di sini.
                TextColumn::make('resolved_url')
                    ->label('Tujuan')
                    ->state(fn (MenuItem $record) => $record->type === 'section'
                        ? '— pengelompok —'
                        : ($record->resolvedUrl() ?? 'TAUTAN MATI'))
                    ->badge()
                    ->color(fn (MenuItem $record) => match (true) {
                        $record->type === 'section' => 'gray',
                        $record->isRenderable() => 'success',
                        default => 'danger',
                    })
                    ->tooltip(fn (MenuItem $record) => $record->isRenderable()
                        ? null
                        : 'Halaman tujuan tidak ditemukan. Item ini disembunyikan dari menu di situs publik sampai diperbaiki.'),

                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('order')->label('Urutan')->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->mutateFormDataUsing(fn (array $data) => $this->nullifyUnusedRefs($this->collapseTranslatableFields($data))),
            ])
            ->recordActions([
                EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, MenuItem $record) => $this->expandTranslatableFields($data, $record))
                    ->mutateFormDataUsing(fn (array $data, MenuItem $record) => $this->nullifyUnusedRefs($this->collapseTranslatableFields($data, $record))),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
