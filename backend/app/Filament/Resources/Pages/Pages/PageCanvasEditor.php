<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page as ResourcePage;

/**
 * Shell kanvas visual (Phase 1) — hanya mount point untuk SPA React di
 * canvas-editor/ (build ke public/canvas-editor/). Semua interaksi drag-drop
 * terjadi di React; halaman Filament ini tidak punya form/state sendiri,
 * cuma menyuntikkan slug + id halaman ke `window` lewat Blade view.
 *
 * Dibuka lewat tombol "Edit Visual" di EditPage, bukan lewat navigasi utama.
 */
class PageCanvasEditor extends ResourcePage
{
    use InteractsWithRecord;

    protected static string $resource = PageResource::class;

    protected string $view = 'filament.pages.page-canvas-editor';

    protected static ?string $title = 'Kanvas Visual';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }
}
