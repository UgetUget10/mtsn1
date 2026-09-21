<?php

namespace App\Filament\Resources\MediaLibrary;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Pratinjau')
                    ->columnSpan(1)
                    ->schema([
                        Placeholder::make('preview')
                            ->hiddenLabel()
                            ->content(fn (Media $record) => str_starts_with((string) $record->mime_type, 'image/')
                                ? new HtmlString('<img src="'.e($record->getUrl()).'" alt="" style="max-width:100%;border-radius:.5rem">')
                                : new HtmlString('<a href="'.e($record->getUrl()).'" target="_blank" class="text-primary-600 underline">Buka berkas</a>')),
                        Placeholder::make('meta')
                            ->hiddenLabel()
                            ->content(fn (Media $record) => new HtmlString(implode('<br>', array_filter([
                                '<strong>Nama:</strong> '.e($record->file_name),
                                '<strong>Tipe:</strong> '.e($record->mime_type),
                                '<strong>Ukuran:</strong> '.self::humanSize($record->size),
                                '<strong>Dipakai oleh:</strong> '.e(class_basename((string) $record->model_type)).' #'.$record->model_id,
                                '<strong>Koleksi:</strong> '.e($record->collection_name),
                                '<strong>URL:</strong> <a href="'.e($record->getUrl()).'" target="_blank" class="underline">'.e($record->getUrl()).'</a>',
                            ])))),
                    ]),

                Section::make('Detail Berkas')
                    ->description('Alt text & caption dipakai frontend di semua tempat berkas ini muncul (wp: Alternative Text / Caption).')
                    ->columnSpan(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Judul')
                            ->helperText('Nama tampilan berkas. Tidak mengubah nama file di disk.'),
                        TextInput::make('custom_properties.alt')
                            ->label('Teks alternatif (alt)')
                            ->helperText('Deskripsi gambar untuk pembaca layar & SEO. Kosongkan bila gambar murni dekoratif.')
                            ->maxLength(255),
                        Textarea::make('custom_properties.caption')
                            ->label('Keterangan (caption)')
                            ->rows(2)
                            ->maxLength(500),
                        TextInput::make('custom_properties.credit')
                            ->label('Kredit / sumber')
                            ->maxLength(255),
                    ]),
            ]);
    }

    private static function humanSize(?int $bytes): string
    {
        if (! $bytes) {
            return '—';
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $n = (float) $bytes;
        while ($n >= 1024 && $i < count($units) - 1) {
            $n /= 1024;
            $i++;
        }

        return round($n, 1).' '.$units[$i];
    }
}
