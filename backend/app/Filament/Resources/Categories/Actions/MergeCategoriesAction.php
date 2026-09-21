<?php

namespace App\Filament\Resources\Categories\Actions;

use App\Models\Category;
use App\Models\Document;
use App\Models\Gallery;
use App\Models\MenuItem;
use App\Models\Post;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Gabungkan kategori duplikat (mis. "Berita" & "berita-sekolah") menjadi
 * satu — setara "Merge tags/categories" beberapa plugin taksonomi WordPress.
 * Semua Post/Gallery/Document/MenuItem yang menunjuk ke kategori terpilih
 * dipindah ke kategori tujuan, lalu kategori sumber dihapus permanen.
 */
class MergeCategoriesAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('mergeCategories')
            ->label('Gabungkan ke...')
            ->icon('heroicon-o-arrows-pointing-in')
            ->color('warning')
            ->schema(fn (Collection $records) => [
                Select::make('target_id')
                    ->label('Gabungkan ke kategori')
                    ->options(fn () => Category::query()
                        ->where('type', $records->first()?->type)
                        ->whereNotIn('id', $records->pluck('id'))
                        ->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->helperText('Semua berita/galeri/dokumen dari kategori terpilih dipindah ke sini, lalu kategori terpilih dihapus permanen. Tidak bisa dibatalkan.'),
            ])
            ->requiresConfirmation()
            ->modalHeading('Gabungkan kategori terpilih?')
            ->modalDescription(fn (Collection $records) => 'Anda akan menggabungkan '.$records->count().
                ' kategori ke satu tujuan. Kategori sumber dihapus permanen setelah isinya dipindah — tindakan ini tidak bisa dibatalkan.')
            ->action(function (Collection $records, array $data) {
                $target = Category::find($data['target_id']);
                if (! $target) {
                    Notification::make()->title('Kategori tujuan tidak ditemukan.')->danger()->send();

                    return;
                }

                $sourceIds = $records->pluck('id')->reject(fn ($id) => $id === $target->id)->values();
                if ($sourceIds->isEmpty()) {
                    Notification::make()->title('Tidak ada kategori sumber untuk digabungkan.')->warning()->send();

                    return;
                }

                DB::transaction(function () use ($sourceIds, $target) {
                    // Kolom category_id langsung (WP: kategori utama).
                    Post::whereIn('category_id', $sourceIds)->update(['category_id' => $target->id]);
                    Gallery::whereIn('category_id', $sourceIds)->update(['category_id' => $target->id]);
                    Document::whereIn('category_id', $sourceIds)->update(['category_id' => $target->id]);
                    MenuItem::whereIn('category_id', $sourceIds)->update(['category_id' => $target->id]);

                    // Pivot many-to-many (WP: kategori tambahan) — pindah baris,
                    // buang yang akan jadi duplikat (post sudah punya target).
                    DB::table('category_post')
                        ->whereIn('category_id', $sourceIds)
                        ->whereNotIn('post_id', function ($q) use ($target) {
                            $q->select('post_id')->from('category_post')->where('category_id', $target->id);
                        })
                        ->update(['category_id' => $target->id]);
                    DB::table('category_post')->whereIn('category_id', $sourceIds)->delete();

                    // Anak kategori dari sumber ikut dipindah ke tujuan (jangan
                    // sampai jadi yatim karena induknya dihapus).
                    Category::whereIn('parent_id', $sourceIds)->update(['parent_id' => $target->id]);

                    Category::whereIn('id', $sourceIds)->delete();
                });

                Notification::make()
                    ->title('Kategori digabungkan')
                    ->body($sourceIds->count().' kategori digabungkan ke "'.$target->name.'".')
                    ->success()
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
