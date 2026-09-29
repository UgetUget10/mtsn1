<?php

namespace App\Filament\Resources\GlobalTemplates;

use App\Filament\Resources\GlobalTemplates\Pages\CreateGlobalTemplate;
use App\Filament\Resources\GlobalTemplates\Pages\EditGlobalTemplate;
use App\Filament\Resources\GlobalTemplates\Pages\GlobalTemplateCanvasEditor;
use App\Filament\Resources\GlobalTemplates\Pages\ListGlobalTemplates;
use App\Filament\Resources\GlobalTemplates\Schemas\GlobalTemplateForm;
use App\Filament\Resources\GlobalTemplates\Tables\GlobalTemplatesTable;
use App\Models\GlobalTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * "Theme Builder" — desain header/footer situs lewat kanvas visual yang sama
 * dengan halaman biasa (App\Models\GlobalTemplate), setara Theme Builder
 * Elementor Pro. Hanya SATU baris per slot yang aktif tampil publik (lihat
 * GlobalTemplate::activate(), dipanggil saat "Terbitkan" di kanvas) — baris
 * lain di slot yang sama adalah draf/desain alternatif tersimpan.
 */
class GlobalTemplateResource extends Resource
{
    protected static ?string $model = GlobalTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Konten';

    protected static ?string $navigationLabel = 'Theme Builder';

    protected static ?string $modelLabel = 'Desain Header/Footer';

    protected static ?string $pluralModelLabel = 'Theme Builder';

    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return GlobalTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GlobalTemplatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGlobalTemplates::route('/'),
            'create' => CreateGlobalTemplate::route('/create'),
            'edit' => EditGlobalTemplate::route('/{record}/edit'),
            'canvas' => GlobalTemplateCanvasEditor::route('/{record}/canvas'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slot'];
    }
}
