<?php

namespace App\Filament\Resources\NotFoundLogs\Pages;

use App\Filament\Resources\NotFoundLogs\NotFoundLogResource;
use App\Models\NotFoundLog;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListNotFoundLogs extends ListRecords
{
    protected static string $resource = NotFoundLogResource::class;

    /**
     * Tab ala WordPress. PENTING: closure modifyQueryUsing HARUS memakai nama
     * parameter $query — Filament evaluate() inject by name; $q → null → error.
     */
    public function getTabs(): array
    {
        return [
            'outstanding' => Tab::make('Belum ditangani')
                ->badge(NotFoundLog::query()->outstanding()->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query) => $query->outstanding()),

            'resolved' => Tab::make('Sudah dialihkan')
                ->badge(NotFoundLog::query()->resolved()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->resolved()),

            'ignored' => Tab::make('Diabaikan')
                ->badge(NotFoundLog::query()->ignored()->count())
                ->modifyQueryUsing(fn (Builder $query) => $query->ignored()),

            'all' => Tab::make('Semua'),
        ];
    }
}
