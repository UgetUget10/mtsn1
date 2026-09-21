<?php

namespace App\Filament\Resources\Galleries\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Item Galeri';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->options(['image' => 'Gambar', 'video' => 'Video'])
                    ->default('image')
                    ->live()
                    ->required(),
                SpatieMediaLibraryFileUpload::make('path')
                    ->collection('image')
                    ->label('File gambar')
                    ->image()
                    ->imageEditor()
                    ->helperText('Klik ikon pensil pada gambar untuk memangkas sebelum menyimpan.')
                    ->directory('galleries')
                    ->visible(fn (callable $get) => $get('type') === 'image'),
                TextInput::make('video_url')
                    ->label('URL video (YouTube/Vimeo)')
                    ->url()
                    ->visible(fn (callable $get) => $get('type') === 'video'),
                TextInput::make('caption')->label('Keterangan')->maxLength(255),
                TextInput::make('order')->label('Urutan')->numeric()->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('caption')
            ->reorderable('order')
            ->defaultSort('order')
            ->columns([
                SpatieMediaLibraryImageColumn::make('path')->collection('image')->label('Gambar')->square(),
                TextColumn::make('type')->badge(),
                TextColumn::make('caption')->searchable(),
                TextColumn::make('order')->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
