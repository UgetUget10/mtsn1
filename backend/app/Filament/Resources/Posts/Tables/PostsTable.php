<?php

namespace App\Filament\Resources\Posts\Tables;

use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use App\Support\Content\Duplicator;
use Filament\Actions\Action;
// Aksi di dalam notifikasi Filament v5 memakai Filament\Actions\Action juga —
// diberi alias agar tidak bentrok dengan Action tabel di atas.
use Filament\Actions\Action as NotificationAction;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class PostsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('cover')->collection('cover')->label('')->square(),
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->limit(50)
                    ->formatStateUsing(fn (string $state, $record) => match ($record->visibility) {
                        Post::VISIBILITY_PASSWORD => '🔒 '.$state,
                        Post::VISIBILITY_PRIVATE => '👁️‍🗨️ '.$state,
                        default => $state,
                    })
                    ->description(fn ($record) => $record->category?->name),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'pending',
                        'info' => 'scheduled',
                        'success' => 'published',
                    ]),
                TextColumn::make('visibility')
                    ->label('Visibilitas')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Post::visibilityOptions()[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        Post::VISIBILITY_PASSWORD => 'warning',
                        Post::VISIBILITY_PRIVATE => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                // Toggle "sorotan" langsung dari tabel (wp: "Make sticky").
                ToggleColumn::make('is_featured')->label('Sorotan'),
                TextColumn::make('author.name')
                    ->label('Penulis')
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('views')->label('Dilihat')->numeric()->sortable(),
                // Kolom gelembung komentar ala daftar Posts WordPress.
                TextColumn::make('comments_count')
                    ->label('Komentar')
                    ->counts([
                        'comments' => fn (Builder $q) => $q->where('status', 'approved'),
                        'comments as pending_comments_count' => fn (Builder $q) => $q->where('status', 'pending'),
                    ])
                    ->badge()
                    ->formatStateUsing(fn ($record) => $record->pending_comments_count > 0
                        ? "{$record->comments_count} (+{$record->pending_comments_count})"
                        : (string) $record->comments_count)
                    ->color(fn ($record) => $record->pending_comments_count > 0 ? 'warning' : 'gray')
                    ->url(fn () => route('filament.admin.resources.comments.index'))
                    ->toggleable(),
                TextColumn::make('published_at')->label('Terbit')->dateTime('d M Y')->sortable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'pending' => 'Menunggu review',
                    'scheduled' => 'Terjadwal',
                    'published' => 'Terbit',
                ]),
                SelectFilter::make('category_id')->relationship('category', 'name')->label('Kategori'),
                // Dropdown "Semua penulis" ala WordPress.
                SelectFilter::make('user_id')
                    ->label('Penulis')
                    ->relationship('author', 'name')
                    ->searchable()
                    ->preload(),
                // Filter per bulan tayang (wp: "All dates"). Opsi & kueri
                // portabel MySQL/SQLite: kelompokkan by tahun+bulan pakai
                // ekspresi driver yang sesuai.
                Filter::make('month')
                    ->schema([
                        Select::make('month')
                            ->label('Bulan tayang')
                            ->options(fn () => Post::query()
                                ->whereNotNull('published_at')
                                ->pluck('published_at')
                                ->map(fn ($d) => Carbon::parse($d)->format('Y-m'))
                                ->unique()
                                ->sortDesc()
                                ->mapWithKeys(fn ($m) => [$m => Carbon::parse($m.'-01')->translatedFormat('F Y')])
                                ->all()),
                    ])
                    ->query(fn (Builder $q, array $data) => $q->when(
                        $data['month'] ?? null,
                        fn (Builder $q, string $m) => $q
                            ->whereYear('published_at', substr($m, 0, 4))
                            ->whereMonth('published_at', substr($m, 5, 2)),
                    )),
                TrashedFilter::make(), // wp: filter "Trash"
            ])
            ->recordActions([
                // "View" → buka artikel di situs publik (wp: "View" row link).
                Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (Post $record) => rtrim((string) env('FRONTEND_URL', ''), '/').'/berita/'.$record->slug, shouldOpenInNewTab: true)
                    ->visible(fn (Post $record) => $record->status === Post::STATUS_PUBLISHED && filled(env('FRONTEND_URL'))),

                // wp: plugin "Duplicate Post" — salin artikel jadi draft baru
                // untuk dipakai sebagai kerangka.
                Action::make('duplicate')
                    ->label('Duplikat')
                    ->icon('heroicon-m-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalHeading('Duplikat berita ini?')
                    ->modalDescription('Salinan dibuat sebagai draft dengan judul bertanda "(salinan)". Tanggal terbit, jumlah dibaca, sorotan, dan kata sandi tidak ikut disalin.')
                    ->modalSubmitActionLabel('Duplikat')
                    ->action(function (Post $record) {
                        $copy = Duplicator::post($record);

                        Notification::make()
                            ->success()
                            ->title('Berita diduplikat')
                            ->body('Salinan tersimpan sebagai draft.')
                            ->actions([
                                NotificationAction::make('edit')
                                    ->label('Sunting salinan')
                                    ->url(PostResource::getUrl('edit', ['record' => $copy]))
                                    ->button(),
                            ])
                            ->send();
                    })
                    ->authorize(fn () => auth()->user()?->can('create', Post::class) ?? false),

                // "Quick Edit" ala WordPress — ubah judul/slug/status/tanggal
                // langsung dari baris tabel tanpa pindah ke halaman penuh.
                // Isi artikel & SEO TIDAK ada di sini (persis WP: Quick Edit
                // hanya field ringkas, bukan editor lengkap).
                Action::make('quickEdit')
                    ->label('Sunting cepat')
                    ->icon('heroicon-m-bolt')
                    ->color('gray')
                    ->slideOver()
                    ->modalHeading('Sunting cepat')
                    ->fillForm(fn (Post $record) => [
                        'title' => $record->title,
                        'slug' => $record->slug,
                        'status' => $record->status,
                        'published_at' => $record->published_at,
                        'is_featured' => $record->is_featured,
                    ])
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul')
                            ->required(),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(table: 'posts', ignoreRecord: true),
                        Select::make('status')
                            ->label('Status')
                            ->options(fn () => Post::assignableStatuses(auth()->user()))
                            ->live()
                            ->required(),
                        DateTimePicker::make('published_at')
                            ->label('Tanggal terbit')
                            ->required(fn ($get) => $get('status') === Post::STATUS_SCHEDULED),
                        Toggle::make('is_featured')
                            ->label('Sorotan / headline'),
                    ])
                    ->action(function (Post $record, array $data) {
                        // Sama seperti validasi form penuh: jangan terbitkan
                        // berita tanpa gambar sampul lewat jalur cepat ini juga.
                        if ($data['status'] === Post::STATUS_PUBLISHED && ! $record->getFirstMedia('cover')) {
                            Notification::make()
                                ->title('Belum bisa diterbitkan')
                                ->body('Berita ini belum punya gambar sampul. Lengkapi lewat "Sunting" penuh sebelum menerbitkan.')
                                ->danger()
                                ->send();

                            return;
                        }

                        if (! $record->published_at && $data['status'] === Post::STATUS_PUBLISHED) {
                            $data['published_at'] = now();
                        }

                        $record->update($data);

                        Notification::make()->title('Berita diperbarui')->success()->send();
                    }),

                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // Bulk ubah status ala WordPress "Bulk Actions".
                    BulkAction::make('publish')
                        ->label('Terbitkan')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => self::setStatus($records, Post::STATUS_PUBLISHED))
                        ->deselectRecordsAfterCompletion(),
                    BulkAction::make('toDraft')
                        ->label('Jadikan draft')
                        ->icon('heroicon-m-pencil')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => self::setStatus($records, Post::STATUS_DRAFT))
                        ->deselectRecordsAfterCompletion(),

                    // wp: "Bulk Actions → Edit" — ubah beberapa field sekaligus
                    // untuk banyak berita. Field yang dibiarkan "— tidak diubah —"
                    // tidak menyentuh baris. Kategori & tag DITAMBAHKAN (tidak
                    // menimpa), persis Quick/Bulk Edit WordPress.
                    BulkAction::make('bulkEdit')
                        ->label('Sunting massal')
                        ->icon('heroicon-m-pencil-square')
                        ->color('primary')
                        ->schema([
                            Select::make('user_id')
                                ->label('Penulis')
                                ->placeholder('— tidak diubah —')
                                ->options(fn () => User::query()->orderBy('name')->pluck('name', 'id'))
                                ->searchable(),
                            Select::make('status')
                                ->label('Status')
                                ->placeholder('— tidak diubah —')
                                ->options([
                                    Post::STATUS_DRAFT => 'Draft',
                                    Post::STATUS_PENDING => 'Menunggu review',
                                    Post::STATUS_PUBLISHED => 'Terbit',
                                ]),
                            Select::make('category_id')
                                ->label('Kategori utama')
                                ->placeholder('— tidak diubah —')
                                ->options(fn () => Category::query()->where('type', 'post')
                                    ->get(['id', 'name'])->pluck('name', 'id'))
                                ->searchable(),
                            Select::make('add_categories')
                                ->label('Tambah kategori')
                                ->placeholder('— tidak diubah —')
                                ->multiple()
                                ->options(fn () => Category::query()->where('type', 'post')
                                    ->get(['id', 'name'])->pluck('name', 'id'))
                                ->searchable(),
                            Select::make('add_tags')
                                ->label('Tambah tag')
                                ->placeholder('— tidak diubah —')
                                ->multiple()
                                ->options(fn () => Tag::query()->get(['id', 'name'])->pluck('name', 'id'))
                                ->searchable(),
                            Select::make('is_featured')
                                ->label('Sorotan / headline')
                                ->placeholder('— tidak diubah —')
                                ->options([1 => 'Ya', 0 => 'Tidak']),
                            Select::make('comments_closed')
                                ->label('Komentar')
                                ->placeholder('— tidak diubah —')
                                ->options([0 => 'Buka', 1 => 'Tutup']),
                        ])
                        ->action(fn (Collection $records, array $data) => self::bulkEdit($records, $data))
                        ->deselectRecordsAfterCompletion(),

                    // Isi meta SEO yang masih kosong dari judul/ringkasan —
                    // titik awal cepat untuk berita lama yang belum diisi manual.
                    BulkAction::make('fillSeoDefaults')
                        ->label('Isi SEO default')
                        ->icon('heroicon-m-magnifying-glass')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalHeading('Isi SEO default untuk berita terpilih?')
                        ->modalDescription('Hanya mengisi judul/deskripsi SEO yang masih kosong, dari judul dan ringkasan berita. Yang sudah terisi tidak ditimpa.')
                        ->action(fn (Collection $records) => self::fillSeoDefaults($records))
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ForceDeleteBulkAction::make()
                        ->modalDescription(fn (Collection $records) => \App\Filament\Support\ForceDeleteImpact::describe($records, [
                            'gambar sampul' => fn (Post $r) => $r->getMedia('cover')->count(),
                            'komentar' => fn (Post $r) => $r->comments()->count(),
                        ])),
                ]),
            ]);
    }

    private static function fillSeoDefaults(Collection $records): void
    {
        $changed = 0;
        foreach ($records as $post) {
            $meta = $post->meta ?? [];
            $dirty = false;

            if (blank($meta['seo_title'] ?? null)) {
                $meta['seo_title'] = \Illuminate\Support\Str::limit((string) $post->title, 60, '');
                $dirty = true;
            }

            if (blank($meta['seo_description'] ?? null)) {
                $source = filled($post->excerpt) ? (string) $post->excerpt : strip_tags((string) $post->body);
                $meta['seo_description'] = \Illuminate\Support\Str::limit(trim($source), 160);
                $dirty = true;
            }

            if ($dirty) {
                $post->update(['meta' => $meta]);
                $changed++;
            }
        }

        Notification::make()
            ->success()
            ->title($changed > 0 ? "SEO default diisi untuk {$changed} berita" : 'Tidak ada yang perlu diisi')
            ->body($changed > 0 ? null : 'Semua berita terpilih sudah punya judul & deskripsi SEO.')
            ->send();
    }

    /**
     * wp Bulk Edit. `$data` field kosong / null → tidak menyentuh baris.
     * Kategori & tag di-`syncWithoutDetaching` (tambah, tidak menimpa).
     */
    private static function bulkEdit(Collection $records, array $data): void
    {
        $direct = [];
        foreach (['user_id', 'status', 'category_id'] as $key) {
            if (filled($data[$key] ?? null)) {
                $direct[$key] = $data[$key];
            }
        }
        if (($data['is_featured'] ?? null) !== null && $data['is_featured'] !== '') {
            $direct['is_featured'] = (bool) $data['is_featured'];
        }

        // Terbit tanpa tanggal → set sekarang (konsisten dgn setStatus).
        $publishNow = ($direct['status'] ?? null) === Post::STATUS_PUBLISHED;

        $changed = 0;
        $skipped = 0;
        foreach ($records as $post) {
            $row = $direct;

            // Sama seperti validasi form: jangan terbitkan berita tanpa sampul.
            if ($publishNow && ! $post->getFirstMedia('cover')) {
                unset($row['status']);
                $skipped++;
            }

            if ($publishNow && ($row['status'] ?? null) === Post::STATUS_PUBLISHED && $post->published_at === null) {
                $row['published_at'] = now();
            }

            if (($data['comments_closed'] ?? null) !== null && $data['comments_closed'] !== '') {
                $meta = $post->meta ?? [];
                $meta['comments_closed'] = (bool) $data['comments_closed'];
                $row['meta'] = $meta;
            }

            if ($row) {
                $post->update($row); // memicu revalidation frontend per baris
            }

            $pivotChanged = false;
            if (filled($data['add_categories'] ?? null)) {
                $post->categories()->syncWithoutDetaching($data['add_categories']);
                $pivotChanged = true;
            }
            if (filled($data['add_tags'] ?? null)) {
                $post->tags()->syncWithoutDetaching($data['add_tags']);
                $pivotChanged = true;
            }

            // Attach pivot tidak memicu event `Post::saved`, jadi revalidasi
            // frontend tidak jalan sendiri. Bila HANYA pivot yang berubah
            // (tak ada `$row`), purge manual — tanpa `touch()` yang akan
            // meregenerasi slug pada model ber-HasSlug.
            if ($pivotChanged && ! $row) {
                self::purgeFrontend($post);
            }

            $changed++;
        }

        Notification::make()
            ->success()
            ->title("{$changed} berita diperbarui")
            ->body($skipped > 0 ? "{$skipped} tidak diterbitkan karena belum punya gambar sampul (perubahan lain tetap disimpan)." : null)
            ->send();
    }

    /** Purge cache frontend untuk satu Post tanpa menyentuh baris DB. */
    private static function purgeFrontend(Post $post): void
    {
        $url = config('services.frontend.revalidate_url');
        $secret = config('services.frontend.revalidate_secret');
        if (! $url || ! $secret) {
            return;
        }

        $t = \App\Support\Revalidation\RevalidationTargets::for($post);
        try {
            \Illuminate\Support\Facades\Http::timeout(3)->acceptJson()->post($url, [
                'secret' => $secret,
                'tags' => $t['tags'],
                'paths' => $t['paths'],
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Bulk-edit purge gagal: '.$e->getMessage());
        }
    }

    private static function setStatus(Collection $records, string $status): void
    {
        $changed = 0;
        $skipped = 0;
        foreach ($records as $post) {
            // Sama seperti validasi form: jangan terbitkan berita tanpa sampul.
            if ($status === Post::STATUS_PUBLISHED && ! $post->getFirstMedia('cover')) {
                $skipped++;

                continue;
            }

            $data = ['status' => $status];
            // Terbitkan tanpa tanggal tayang → set sekarang, seperti WordPress.
            if ($status === Post::STATUS_PUBLISHED && $post->published_at === null) {
                $data['published_at'] = now();
            }
            $post->update($data); // memicu revalidation frontend per baris
            $changed++;
        }

        Notification::make()
            ->success()
            ->title("{$changed} berita diperbarui")
            ->body($skipped > 0 ? "{$skipped} dilewati karena belum punya gambar sampul." : null)
            ->send();
    }
}
