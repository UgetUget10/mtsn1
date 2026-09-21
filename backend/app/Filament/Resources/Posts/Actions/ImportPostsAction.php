<?php

namespace App\Filament\Resources\Posts\Actions;

use App\Filament\Support\PostImporter;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Support\Enums\Alignment;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

/**
 * Import berita dari CSV atau XML WordPress Export — mirip fitur import
 * plugin populer di WordPress (mis. WP All Import), diproses SYNCHRONOUS
 * (lihat catatan panjang di App\Filament\Support\PostImporter) supaya
 * hasilnya langsung terlihat tanpa bergantung queue worker yang tidak
 * berjalan di server ini.
 */
class ImportPostsAction
{
    public static function make(): Action
    {
        return Action::make('importPosts')
            ->label('Import Berita')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('gray')
            ->modalHeading('Import Berita')
            ->modalDescription('Unggah berkas CSV, XLSX, atau XML (WordPress Export / WXR) berisi kumpulan berita.')
            ->modalSubmitActionLabel('Jalankan Import')
            ->schema([
                SchemaActions::make([
                    Action::make('downloadTemplateCsv')
                        ->label('Unduh Template CSV')
                        ->icon(Heroicon::OutlinedArrowDownTray)
                        ->color('gray')
                        ->size('sm')
                        ->url(route('admin.posts.import-template.csv'))
                        ->openUrlInNewTab(),
                    Action::make('downloadTemplateXlsx')
                        ->label('Unduh Template XLSX')
                        ->icon(Heroicon::OutlinedArrowDownTray)
                        ->color('gray')
                        ->size('sm')
                        ->url(route('admin.posts.import-template.xlsx'))
                        ->openUrlInNewTab(),
                ])->alignment(Alignment::Start),
                FileUpload::make('file')
                    ->label('Berkas')
                    ->required()
                    ->acceptedFileTypes([
                        'text/csv', 'text/xml', 'application/xml',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        '.csv', '.xml', '.xlsx',
                    ])
                    ->disk('local')
                    ->directory('imports')
                    ->visibility('private')
                    ->helperText(
                        'CSV/XLSX: kolom title, slug, excerpt, body, category, tags (pisah koma), '.
                        'status, published_at, is_featured, cover_url — baris pertama = header. '.
                        'XML: hasil ekspor WordPress (Tools → Export → Posts). Untuk XLSX hanya sheet pertama yang dibaca.',
                    ),
                Select::make('duplicate_strategy')
                    ->label('Bila slug sudah ada')
                    ->options([
                        PostImporter::DUPLICATE_SKIP => 'Lewati (jangan ubah yang sudah ada)',
                        PostImporter::DUPLICATE_UPDATE => 'Perbarui berita yang sudah ada',
                        PostImporter::DUPLICATE_CREATE_NEW => 'Selalu buat baru (slug ditambah angka)',
                    ])
                    ->default(PostImporter::DUPLICATE_SKIP)
                    ->native(false)
                    ->required(),
                Select::make('default_status')
                    ->label('Status bila tidak ditentukan di berkas')
                    ->options([
                        'draft' => 'Draf',
                        'pending' => 'Menunggu tinjauan',
                        'published' => 'Terbit',
                    ])
                    ->default('draft')
                    ->native(false)
                    ->required()
                    ->helperText('Berita berstatus "Terbit" langsung tampil di situs publik begitu import selesai.'),
                Toggle::make('download_covers')
                    ->label('Unduh gambar sampul dari URL')
                    ->default(true)
                    ->helperText('Nonaktifkan bila berkas tidak menyertakan URL gambar, atau untuk mempercepat import berkas besar.'),
            ])
            ->action(function (array $data) {
                $relativePath = is_array($data['file']) ? ($data['file'][0] ?? null) : $data['file'];
                if (! $relativePath) {
                    Notification::make()->title('Berkas tidak ditemukan.')->danger()->send();

                    return;
                }

                $absolutePath = Storage::disk('local')->path($relativePath);
                $extension = strtolower(pathinfo($relativePath, PATHINFO_EXTENSION));
                $format = match ($extension) {
                    'xml' => 'xml',
                    'xlsx' => 'xlsx',
                    default => 'csv',
                };

                try {
                    $result = PostImporter::run(
                        path: $absolutePath,
                        format: $format,
                        authorId: auth()->id(),
                        defaultStatus: $data['default_status'],
                        duplicateStrategy: $data['duplicate_strategy'],
                        downloadCovers: (bool) $data['download_covers'],
                    );
                } catch (\Throwable $e) {
                    report($e);
                    Notification::make()
                        ->title('Import gagal diproses.')
                        ->body($e->getMessage())
                        ->danger()
                        ->persistent()
                        ->send();

                    return;
                } finally {
                    Storage::disk('local')->delete($relativePath);
                }

                $summary = "Dibuat: {$result['created']} · Diperbarui: {$result['updated']} · ".
                    "Dilewati: {$result['skipped']} · Gagal: {$result['failed']}";

                $notification = Notification::make()
                    ->title('Import selesai')
                    ->body($summary);

                if ($result['failed'] > 0) {
                    $notification->warning()->persistent();
                    if (! empty($result['errors'])) {
                        $notification->body(
                            $summary."\n\n".implode("\n", array_slice($result['errors'], 0, 5)).
                            (count($result['errors']) > 5 ? "\n… dan ".(count($result['errors']) - 5)." lainnya." : ''),
                        );
                    }
                } else {
                    $notification->success();
                }

                $notification->send();
            });
    }
}
