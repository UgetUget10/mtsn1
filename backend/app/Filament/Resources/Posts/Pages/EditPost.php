<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Concerns\HasAutosave;
use App\Filament\Concerns\SyncsCoverMeta;
use App\Filament\Concerns\SyncsTranslatableFields;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditPost extends EditRecord
{
    use HasAutosave;
    use SyncsCoverMeta;
    use SyncsTranslatableFields;

    protected function autosaveFields(): array
    {
        return ['title', 'excerpt', 'body'];
    }

    protected static string $resource = PostResource::class;

    protected function translatableAttributes(): array
    {
        return ['title', 'excerpt', 'body'];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Pratinjau')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->url(fn () => $this->previewUrl(), shouldOpenInNewTab: true)
                ->visible(fn () => filled($this->previewUrl())),
            DeleteAction::make(),      // wp: "Move to Trash"
            RestoreAction::make(),
            ForceDeleteAction::make(),
        ];
    }

    /**
     * URL Draft Mode di frontend Next.js. Butuh FRONTEND_URL di .env; token
     * unik per baris memastikan hanya editor yang punya tautan bisa melihat draft.
     */
    private function previewUrl(): ?string
    {
        $base = rtrim((string) env('FRONTEND_URL', ''), '/');
        if (! $base) {
            return null;
        }

        $record = $this->getRecord();

        return "{$base}/api/preview?type=post&slug={$record->slug}&token={$record->preview_token}";
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->fillCoverMeta(
            $this->expandTranslatableFields($data, $this->getRecord()),
        );
    }

    protected function afterSave(): void
    {
        $this->syncCoverMeta();
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['status'])) {
            $allowed = array_keys(Post::assignableStatuses(auth()->user()));
            if (! in_array($data['status'], $allowed, true)) {
                // Kontributor memaksa status di luar haknya → jatuhkan ke nilai
                // tersimpan saat ini (tidak menaikkan visibilitas diam-diam).
                $data['status'] = $this->getRecord()->getRawOriginal('status');
            }
        }

        return $this->stripCoverMeta(
            $this->collapseTranslatableFields($data, $this->getRecord()),
        );
    }
}
