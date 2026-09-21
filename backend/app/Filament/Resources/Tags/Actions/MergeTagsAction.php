<?php

namespace App\Filament\Resources\Tags\Actions;

use App\Models\Tag;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Gabungkan tag duplikat menjadi satu — setara "Merge Terms" plugin
 * taksonomi WordPress. Semua post dari tag terpilih dipindah ke tag tujuan,
 * lalu tag sumber dihapus permanen.
 */
class MergeTagsAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('mergeTags')
            ->label('Gabungkan ke...')
            ->icon('heroicon-o-arrows-pointing-in')
            ->color('warning')
            ->schema(fn (Collection $records) => [
                Select::make('target_id')
                    ->label('Gabungkan ke tag')
                    ->options(fn () => Tag::query()
                        ->whereNotIn('id', $records->pluck('id'))
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->helperText('Semua berita dari tag terpilih dipindah ke sini, lalu tag terpilih dihapus permanen. Tidak bisa dibatalkan.'),
            ])
            ->requiresConfirmation()
            ->modalHeading('Gabungkan tag terpilih?')
            ->modalDescription(fn (Collection $records) => 'Anda akan menggabungkan '.$records->count().
                ' tag ke satu tujuan. Tag sumber dihapus permanen setelah isinya dipindah — tindakan ini tidak bisa dibatalkan.')
            ->action(function (Collection $records, array $data) {
                $target = Tag::find($data['target_id']);
                if (! $target) {
                    Notification::make()->title('Tag tujuan tidak ditemukan.')->danger()->send();

                    return;
                }

                $sourceIds = $records->pluck('id')->reject(fn ($id) => $id === $target->id)->values();
                if ($sourceIds->isEmpty()) {
                    Notification::make()->title('Tidak ada tag sumber untuk digabungkan.')->warning()->send();

                    return;
                }

                DB::transaction(function () use ($sourceIds, $target) {
                    // Pindah pivot, buang yang akan jadi duplikat (post sudah
                    // punya tag tujuan).
                    DB::table('post_tag')
                        ->whereIn('tag_id', $sourceIds)
                        ->whereNotIn('post_id', function ($q) use ($target) {
                            $q->select('post_id')->from('post_tag')->where('tag_id', $target->id);
                        })
                        ->update(['tag_id' => $target->id]);
                    DB::table('post_tag')->whereIn('tag_id', $sourceIds)->delete();

                    Tag::whereIn('id', $sourceIds)->delete();
                });

                Notification::make()
                    ->title('Tag digabungkan')
                    ->body($sourceIds->count().' tag digabungkan ke "'.$target->name.'".')
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
