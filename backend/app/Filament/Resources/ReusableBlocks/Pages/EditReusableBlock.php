<?php

namespace App\Filament\Resources\ReusableBlocks\Pages;

use App\Filament\Resources\ReusableBlocks\ReusableBlockResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReusableBlock extends EditRecord
{
    protected static string $resource = ReusableBlockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
