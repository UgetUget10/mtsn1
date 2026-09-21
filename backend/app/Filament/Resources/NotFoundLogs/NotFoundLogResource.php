<?php

namespace App\Filament\Resources\NotFoundLogs;

use App\Filament\Resources\NotFoundLogs\Pages\ListNotFoundLogs;
use App\Filament\Resources\NotFoundLogs\Tables\NotFoundLogsTable;
use App\Models\NotFoundLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Log 404 ala plugin Redirection (menu "Redirection → 404s").
 * Tidak ada form create — baris hanya masuk dari frontend lewat API /log-404.
 * Editor melihat URL mati yang benar-benar diakses pengunjung lalu satu klik
 * "Buatkan pengalihan" (membuat baris Redirect + menandai log ini selesai).
 */
class NotFoundLogResource extends Resource
{
    protected static ?string $model = NotFoundLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Log 404';

    protected static ?string $modelLabel = 'Log 404';

    protected static ?string $pluralModelLabel = 'Log 404';

    protected static ?int $navigationSort = 50;

    public static function getNavigationBadge(): ?string
    {
        $count = NotFoundLog::query()->outstanding()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return NotFoundLogsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotFoundLogs::route('/'),
        ];
    }
}
