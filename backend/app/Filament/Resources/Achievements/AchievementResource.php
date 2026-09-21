<?php

namespace App\Filament\Resources\Achievements;

use App\Filament\Resources\Achievements\Pages\CreateAchievement;
use App\Filament\Resources\Achievements\Pages\EditAchievement;
use App\Filament\Resources\Achievements\Pages\ListAchievements;
use App\Filament\Resources\Achievements\Schemas\AchievementForm;
use App\Filament\Resources\Achievements\Tables\AchievementsTable;
use App\Models\Achievement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Table;
use UnitEnum;

class AchievementResource extends Resource
{
    protected static ?string $model = Achievement::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static string|UnitEnum|null $navigationGroup = 'Profil Madrasah';

    protected static ?string $navigationLabel = 'Prestasi';

    protected static ?string $modelLabel = 'Prestasi';

    protected static ?string $pluralModelLabel = 'Prestasi';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return AchievementForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AchievementsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    /** Sertakan item Trash agar TrashedFilter berfungsi (wp: tampilan "Trash"). */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAchievements::route('/'),
            'create' => CreateAchievement::route('/create'),
            'edit' => EditAchievement::route('/{record}/edit'),
        ];
    }
}
