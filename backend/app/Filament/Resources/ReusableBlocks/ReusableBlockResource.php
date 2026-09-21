<?php

namespace App\Filament\Resources\ReusableBlocks;

use App\Filament\Resources\ReusableBlocks\Pages\CreateReusableBlock;
use App\Filament\Resources\ReusableBlocks\Pages\EditReusableBlock;
use App\Filament\Resources\ReusableBlocks\Pages\ListReusableBlocks;
use App\Filament\Resources\ReusableBlocks\Schemas\ReusableBlockForm;
use App\Filament\Resources\ReusableBlocks\Tables\ReusableBlocksTable;
use App\Models\ReusableBlock;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Pengelola "Blok Dipakai Ulang" — setara Synced Pattern (dulu Reusable Block)
 * di WordPress. Halaman merujuk blok ini lewat blok bertipe `reusable`, dan
 * App\Http\Resources\PageResource::expandReusable() menyisipkan isinya saat
 * halaman diserialisasi ke API.
 */
class ReusableBlockResource extends Resource
{
    protected static ?string $model = ReusableBlock::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|UnitEnum|null $navigationGroup = 'Konten';

    protected static ?string $navigationLabel = 'Blok Dipakai Ulang';

    protected static ?string $modelLabel = 'Blok Dipakai Ulang';

    protected static ?string $pluralModelLabel = 'Blok Dipakai Ulang';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return ReusableBlockForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReusableBlocksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReusableBlocks::route('/'),
            'create' => CreateReusableBlock::route('/create'),
            'edit' => EditReusableBlock::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug'];
    }
}
