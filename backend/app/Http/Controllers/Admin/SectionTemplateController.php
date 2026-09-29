<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SectionTemplate;
use App\Support\Blocks\StyleResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * API "Template Section" kanvas visual (canvas-editor/) — setara "Save as
 * Template" Elementor Pro. Dipanggil dari tombol pada section, TERPISAH dari
 * App\Http\Controllers\Admin\PageCanvasController (yang urusannya draf per
 * halaman) karena template lintas-halaman dan lintas-tabel sendiri (lihat
 * App\Models\SectionTemplate).
 */
class SectionTemplateController extends Controller
{
    /** Daftar ringkas untuk panel "Sisipkan dari Template" di kanvas. */
    public function index(): array
    {
        $this->authorizeCanvasAccess();

        return SectionTemplate::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'section'])
            ->map(fn (SectionTemplate $t) => [
                'id' => $t->id,
                'name' => $t->name,
                'slug' => $t->slug,
                'widgetCount' => collect($t->section['children'] ?? [])
                    ->sum(fn ($col) => count($col['children'] ?? [])),
            ])
            ->all();
    }

    /**
     * Simpan SATU node section (utuh dengan style + children) sebagai
     * template baru. `id` di dalam node disimpan apa adanya di sini — id
     * baru dibuat saat template DIPAKAI (lihat show()), bukan saat disimpan,
     * supaya template yang sama bisa disisipkan berkali-kali tanpa tabrakan id.
     */
    public function store(Request $request): array
    {
        $this->authorizeCanvasAccess();

        // validate() hanya menegakkan BENTUK section (type/children wajib
        // ada) — hasilnya SENGAJA tidak dipakai langsung untuk menyimpan,
        // karena Laravel merekonstruksi array tervalidasi hanya dari key
        // yang punya rule eksplisit (section.type, section.children), jadi
        // key lain (id, style) akan hilang kalau dipakai. Ambil section
        // mentah dari input() setelah validasi lolos, lalu bersihkan style-nya
        // sendiri lewat sanitizeSectionStyles()/StyleResolver di bawah.
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'section' => ['required', 'array'],
            'section.type' => ['required', 'in:section'],
            'section.children' => ['present', 'array'],
        ]);

        $sanitized = $this->sanitizeSectionStyles($request->input('section'));

        $template = SectionTemplate::create([
            'name' => $request->input('name'),
            'section' => $sanitized,
        ]);

        return ['id' => $template->id, 'name' => $template->name, 'slug' => $template->slug];
    }

    /**
     * Ambil isi satu template untuk disisipkan ke kanvas — id di setiap node
     * (section/column/widget) diganti baru di SINI (server), bukan di client,
     * supaya sumber id acak konsisten satu tempat dan template asal tidak
     * pernah bisa tertaut balik ke halaman manapun yang memakainya.
     */
    public function show(SectionTemplate $sectionTemplate): array
    {
        $this->authorizeCanvasAccess();

        return ['section' => $this->regenerateIds($sectionTemplate->section ?? [])];
    }

    private function authorizeCanvasAccess(): void
    {
        abort_unless(Auth::user()?->can('Update:Page') ?? false, 403);
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    private function sanitizeSectionStyles(array $section): array
    {
        if (isset($section['style'])) {
            $section['style'] = StyleResolver::sanitize($section['style']);
        }

        $section['children'] = collect($section['children'] ?? [])
            ->map(function (array $column) {
                if (isset($column['style'])) {
                    $column['style'] = StyleResolver::sanitize($column['style']);
                }
                $column['children'] = collect($column['children'] ?? [])
                    ->map(function (array $widget) {
                        if (isset($widget['style'])) {
                            $widget['style'] = StyleResolver::sanitize($widget['style']);
                        }

                        return $widget;
                    })
                    ->all();

                return $column;
            })
            ->all();

        return $section;
    }

    /**
     * @param  array<string, mixed>  $section
     * @return array<string, mixed>
     */
    private function regenerateIds(array $section): array
    {
        $section['id'] = 'sec_'.str()->random(8);

        $section['children'] = collect($section['children'] ?? [])
            ->map(function (array $column) {
                $column['id'] = 'col_'.str()->random(8);
                $column['children'] = collect($column['children'] ?? [])
                    ->map(function (array $widget) {
                        $widget['id'] = 'wid_'.str()->random(8);

                        return $widget;
                    })
                    ->all();

                return $column;
            })
            ->all();

        return $section;
    }
}
