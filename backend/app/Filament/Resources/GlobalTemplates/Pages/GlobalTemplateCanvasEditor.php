<?php

namespace App\Filament\Resources\GlobalTemplates\Pages;

use App\Filament\Resources\GlobalTemplates\GlobalTemplateResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page as ResourcePage;

/**
 * Shell kanvas visual Theme Builder — sama seperti
 * App\Filament\Resources\Pages\Pages\PageCanvasEditor tapi untuk
 * App\Models\GlobalTemplate (header/footer). SPA React yang sama
 * (canvas-editor/) dipakai ulang, dibedakan lewat data-tree-url.
 */
class GlobalTemplateCanvasEditor extends ResourcePage
{
    use InteractsWithRecord;

    protected static string $resource = GlobalTemplateResource::class;

    protected string $view = 'filament.pages.global-template-canvas-editor';

    protected static ?string $title = 'Kanvas Visual';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }
}
