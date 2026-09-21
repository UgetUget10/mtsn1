<?php

namespace App\Filament\Resources\MediaLibrary;

use App\Filament\Resources\MediaLibrary\Pages\EditMedia;
use App\Filament\Resources\MediaLibrary\Pages\ListMedia;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use UnitEnum;

/**
 * Pustaka Media terpusat — meniru wp-admin/upload.php. Menampilkan SEMUA
 * berkas yang dikelola spatie/laravel-medialibrary di seluruh model (cover
 * berita, foto guru, gambar galeri, logo, dsb.), tempat menyunting alt text
 * & caption sekali untuk semua tempat file itu dipakai.
 *
 * Upload dilakukan di form masing-masing konten (seperti WordPress yang juga
 * mengunggah dari dalam editor); halaman ini untuk mengelola, bukan menambah.
 */
class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPhoto;

    protected static string|UnitEnum|null $navigationGroup = 'Konten';

    protected static ?string $navigationLabel = 'Pustaka Media';

    protected static ?string $modelLabel = 'Berkas Media';

    protected static ?string $pluralModelLabel = 'Pustaka Media';

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return MediaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MediaTable::configure($table);
    }

    public static function canCreate(): bool
    {
        return false; // upload lewat form konten, bukan di sini
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedia::route('/'),
            'edit' => EditMedia::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        // Jumlah berkas gambar tanpa alt text — mirip peringatan aksesibilitas WP.
        $missing = Media::query()
            ->where('mime_type', 'like', 'image/%')
            ->where(fn ($q) => $q
                ->whereNull('custom_properties->alt')
                ->orWhere('custom_properties->alt', ''))
            ->count();

        return $missing > 0 ? (string) $missing : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
