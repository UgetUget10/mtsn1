<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\Blocks\TreeNormalizer;
use Illuminate\Http\Request;

/**
 * API kanvas visual (App\Filament\Pages\PageCanvasEditor) — dipanggil oleh
 * SPA React (canvas-editor/), bukan Next.js publik. Auth lewat sesi Filament
 * (lihat routes/web.php), bukan token API terpisah.
 *
 * show()/update() membaca/menulis `blocks_draft` — autosave TIDAK PERNAH
 * menyentuh `blocks` (kolom published) langsung, supaya halaman publik tidak
 * berubah sebelum editor sengaja menekan "Terbitkan".
 */
class PageCanvasController extends Controller
{
    public function show(Page $page): array
    {
        $this->authorizeCanvasAccess();

        return $page->visibleTree();
    }

    public function update(Request $request, Page $page): array
    {
        $this->authorizeCanvasAccess();

        $validated = $request->validate([
            'tree' => ['present', 'array'],
        ]);

        $page->update([
            'blocks_draft' => TreeNormalizer::normalize(['schema' => 2, 'tree' => $validated['tree']]),
        ]);

        return $page->visibleTree();
    }

    /**
     * Menyalin draf kanvas ke `blocks` (kolom published) — Phase 1 tidak
     * merender tree di frontend publik, jadi tree diratakan dulu (lihat
     * TreeNormalizer::flatten()). Draf TETAP disimpan setelahnya (bukan
     * dikosongkan) supaya struktur section/kolom tidak hilang saat editor
     * membuka kanvas lagi nanti — hanya bentuk publik (`blocks`) yang flat.
     */
    public function publish(Page $page): array
    {
        $this->authorizeCanvasAccess();

        $tree = $page->visibleTree();

        $page->update(['blocks' => TreeNormalizer::flatten($tree)]);

        return ['published_at' => $page->fresh()->updated_at?->toIso8601String()];
    }

    /** Membuang draf yang belum diterbitkan — kanvas kembali ke isi `blocks` published saat ini. */
    public function discardDraft(Page $page): array
    {
        $this->authorizeCanvasAccess();

        $page->update(['blocks_draft' => null]);

        return $page->visibleTree();
    }

    /**
     * Sama seperti gate App\Filament\Pages\PageBlockEditor — endpoint ini di
     * luar routing Resource Filament, jadi tidak otomatis diproteksi policy
     * Page seperti CRUD biasa. Tanpa ini, siapa pun yang login ke panel
     * (peran apa pun) bisa mengubah/menerbitkan draf halaman mana pun.
     */
    private function authorizeCanvasAccess(): void
    {
        abort_unless(auth()->user()?->can('Update:Page') ?? false, 403);
    }
}
