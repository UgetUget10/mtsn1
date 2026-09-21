<?php

namespace App\Filament\Resources\Contacts\Pages;

use App\Filament\Resources\Contacts\ContactResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewContact extends ViewRecord
{
    protected static string $resource = ContactResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if (! $this->record->is_read) {
            $this->record->update(['is_read' => true]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleRead')
                ->label(fn () => $this->record->is_read ? 'Tandai belum dibaca' : 'Tandai sudah dibaca')
                ->icon('heroicon-o-envelope')
                ->action(fn () => $this->record->update(['is_read' => ! $this->record->is_read])),
            DeleteAction::make(),
        ];
    }
}
