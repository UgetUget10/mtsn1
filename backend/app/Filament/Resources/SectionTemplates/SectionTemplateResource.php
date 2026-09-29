<?php

namespace App\Filament\Resources\SectionTemplates;

use App\Filament\Resources\SectionTemplates\Pages\EditSectionTemplate;
use App\Filament\Resources\SectionTemplates\Pages\ListSectionTemplates;
use App\Filament\Resources\SectionTemplates\Schemas\SectionTemplateForm;
use App\Filament\Resources\SectionTemplates\Tables\SectionTemplatesTable;
use App\Models\SectionTemplate;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Pengelola "Template Section" — setara "Save as Template" / library blok
 * Elementor Pro (App\Models\SectionTemplate). Hanya dibuat lewat kanvas
 * visual (tombol "Simpan sebagai Template" pada section, lihat
 * App\Http\Controllers\Admin\SectionTemplateController::store()) — resource
 * ini cuma untuk melihat/mengganti nama/menghapus, TIDAK ada form "Create"
 * untuk menyusun isi section dari nol di sini (isinya JSON mentah, tidak
 * masuk akal disunting manual).
 */
class SectionTemplateResource extends Resource
{
    protected static ?string $model = SectionTemplate::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static string|UnitEnum|null $navigationGroup = 'Konten';

    protected static ?string $navigationLabel = 'Template Section';

    protected static ?string $modelLabel = 'Template Section';

    protected static ?string $pluralModelLabel = 'Template Section';

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return SectionTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SectionTemplatesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSectionTemplates::route('/'),
            'edit' => EditSectionTemplate::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'slug'];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
