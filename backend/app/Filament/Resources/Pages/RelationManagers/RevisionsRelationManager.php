<?php

namespace App\Filament\Resources\Pages\RelationManagers;

use App\Models\Revision;
use App\Support\Revisions\RevisionDiff;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

/**
 * Panel Revisi halaman — sama seperti Revisions WordPress. Baca-saja; tombol
 * "Pulihkan" menimpa title/body/blocks/meta_description dengan snapshot lama.
 */
class RevisionsRelationManager extends RelationManager
{
    protected static string $relationship = 'revisions';

    protected static ?string $title = 'Revisi';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('user.name')->label('Oleh')->placeholder('—'),
                TextColumn::make('reason')->label('Jenis')->badge()->placeholder('manual'),
                TextColumn::make('data')
                    ->label('Cuplikan')
                    ->formatStateUsing(fn ($state) => Str::limit(
                        strip_tags(is_array($state) ? implode(' ', array_map(
                            fn ($v) => is_array($v) ? json_encode($v) : (string) $v,
                            $state,
                        )) : (string) $state),
                        80,
                    )),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                // Setara "Compare revisions" WordPress: lihat apa yang berubah
                // SEBELUM memulihkan, bukan memulihkan buta.
                Action::make('diff')
                    ->label('Bandingkan')
                    ->icon('heroicon-o-arrows-right-left')
                    ->color('gray')
                    ->modalHeading('Perbandingan dengan versi sekarang')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->modalWidth('4xl')
                    ->modalContent(fn (Revision $record) => view('filament.revision-diff', [
                        'rows' => RevisionDiff::against($record, $this->getOwnerRecord()),
                    ])),

                Action::make('restore')
                    ->label('Pulihkan')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->modalHeading('Pulihkan revisi ini?')
                    ->action(function (Revision $record): void {
                        $this->getOwnerRecord()->restoreRevision($record);

                        Notification::make()->success()->title('Revisi dipulihkan')->send();
                    }),
            ])
            ->paginated([10, 25, 50]);
    }

    public function isReadOnly(): bool
    {
        return true;
    }
}
