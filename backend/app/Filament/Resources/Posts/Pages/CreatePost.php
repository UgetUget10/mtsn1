<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Concerns\SyncsCoverMeta;
use App\Filament\Concerns\SyncsTranslatableFields;
use App\Filament\Resources\Posts\PostResource;
use App\Models\Post;
use Filament\Resources\Pages\CreateRecord;

class CreatePost extends CreateRecord
{
    use SyncsCoverMeta;
    use SyncsTranslatableFields;

    protected static string $resource = PostResource::class;

    protected function translatableAttributes(): array
    {
        return ['title', 'excerpt', 'body'];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] ??= auth()->id();
        $data['status'] = $this->clampStatus($data['status'] ?? Post::STATUS_DRAFT);

        return $this->stripCoverMeta($this->collapseTranslatableFields($data));
    }

    protected function afterCreate(): void
    {
        $this->syncCoverMeta();
    }

    /**
     * Pertahankan aturan status di sisi server: kontributor tak bisa
     * menerbitkan/menjadwalkan meski memaksa nilai lewat request.
     */
    private function clampStatus(string $status): string
    {
        $allowed = array_keys(Post::assignableStatuses(auth()->user()));

        return in_array($status, $allowed, true) ? $status : Post::STATUS_PENDING;
    }
}
