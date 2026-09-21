<?php

namespace App\Filament\Pages;

use App\Models\ReusableBlock;
use App\Models\WidgetArea;
use App\Support\Widgets\WidgetAreaKeys;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * "Widget Areas" ala WordPress (Appearance → Widgets): tempatkan Blok
 * Dipakai Ulang (Reusable Block) ke zona tetap di frontend (sidebar berita,
 * footer). Zona sendiri TIDAK bisa ditambah dari sini — daftar zona hidup
 * di kode (App\Support\Widgets\WidgetAreaKeys) karena setiap zona baru butuh
 * tempat render baru di frontend juga, sama seperti tema WordPress yang
 * mendaftarkan sidebar-nya sendiri lewat register_sidebar().
 */
class ManageWidgetAreas extends Page
{
    protected string $view = 'filament.pages.manage-site-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleGroup;

    protected static \UnitEnum|string|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Widget Area';

    protected static ?string $title = 'Widget Area';

    protected static ?int $navigationSort = 51;

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('View:ManageSiteSettings') ?? false;
    }

    public function mount(): void
    {
        $data = [];

        foreach (WidgetAreaKeys::all() as $key => $meta) {
            $area = WidgetArea::firstOrCreate(['key' => $key], [
                'label' => $meta['label'],
                'description' => $meta['description'],
            ]);

            $data[$key] = $area->blocks()->get()->map(fn (ReusableBlock $b) => [
                'block_id' => $b->id,
                'is_active' => (bool) $b->pivot->is_active,
            ])->all();
        }

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components(
                collect(WidgetAreaKeys::all())->map(fn (array $meta, string $key) => Section::make($meta['label'])
                    ->description($meta['description'])
                    ->schema([
                        Repeater::make($key)
                            ->label('')
                            ->addActionLabel('Tambah blok ke zona ini')
                            ->schema([
                                Select::make('block_id')
                                    ->label('Blok Dipakai Ulang')
                                    ->options(fn () => ReusableBlock::where('is_active', true)->pluck('name', 'id'))
                                    ->searchable()
                                    ->required()
                                    ->helperText('Kelola isi bloknya sendiri di menu Blok Dipakai Ulang.'),
                                Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true),
                            ])
                            ->columns(2)
                            ->defaultItems(0)
                            ->reorderableWithButtons(),
                    ]))
                    ->values()
                    ->all(),
            );
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label('Simpan')->submit('save'),
        ];
    }

    public function save(): void
    {
        $state = $this->form->getState();

        foreach (array_keys(WidgetAreaKeys::all()) as $key) {
            $area = WidgetArea::where('key', $key)->firstOrFail();

            $rows = collect($state[$key] ?? [])
                ->filter(fn ($row) => filled($row['block_id'] ?? null))
                ->values();

            $sync = [];
            foreach ($rows as $order => $row) {
                $sync[$row['block_id']] = [
                    'order' => $order,
                    'is_active' => (bool) ($row['is_active'] ?? true),
                ];
            }

            $area->blocks()->sync($sync);

            // sync() pada pivot TIDAK memicu event `saved` milik WidgetArea —
            // touch() memanggilnya secara eksplisit supaya frontend disegarkan
            // (lihat TriggersFrontendRevalidation::bootTriggersFrontendRevalidation).
            $area->touch();
        }

        Notification::make()->success()->title('Widget area disimpan')->send();
    }
}
