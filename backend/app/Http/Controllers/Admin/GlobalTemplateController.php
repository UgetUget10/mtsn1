<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GlobalTemplate;
use App\Support\Blocks\TreeNormalizer;
use Illuminate\Http\Request;

/**
 * API kanvas visual untuk Theme Builder (header/footer) — dipanggil oleh SPA
 * React (canvas-editor/), sama seperti App\Http\Controllers\Admin\PageCanvasController
 * tapi untuk App\Models\GlobalTemplate, bukan Page. Auth lewat sesi Filament
 * (lihat routes/web.php), bukan token API terpisah.
 */
class GlobalTemplateController extends Controller
{
    public function show(GlobalTemplate $globalTemplate): array
    {
        $this->authorizeCanvasAccess();

        return $globalTemplate->visibleTree();
    }

    public function update(Request $request, GlobalTemplate $globalTemplate): array
    {
        $this->authorizeCanvasAccess();

        $validated = $request->validate([
            'tree' => ['present', 'array'],
        ]);

        $globalTemplate->update([
            'tree_draft' => TreeNormalizer::normalize(['schema' => 2, 'tree' => $validated['tree']]),
        ]);

        return $globalTemplate->visibleTree();
    }

    /**
     * Salin draf ke `tree` (kolom published) DAN aktifkan slot ini —
     * menonaktifkan baris lain di slot yang sama (lihat GlobalTemplate::activate()).
     */
    public function publish(GlobalTemplate $globalTemplate): array
    {
        $this->authorizeCanvasAccess();

        $tree = $globalTemplate->visibleTree();

        $globalTemplate->update(['tree' => $tree]);
        $globalTemplate->activate();

        return ['published_at' => $globalTemplate->fresh()->updated_at?->toIso8601String()];
    }

    /** Membuang draf yang belum diterbitkan — kanvas kembali ke isi `tree` published saat ini. */
    public function discardDraft(GlobalTemplate $globalTemplate): array
    {
        $this->authorizeCanvasAccess();

        $globalTemplate->update(['tree_draft' => null]);

        return $globalTemplate->visibleTree();
    }

    private function authorizeCanvasAccess(): void
    {
        abort_unless(auth()->user()?->can('Update:Page') ?? false, 403);
    }
}
