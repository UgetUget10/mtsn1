<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Pages\Schemas\PageForm;
use App\Models\Page as PageModel;
use App\Support\Blocks\TreeNormalizer;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

/**
 * Modal edit SATU widget di kanvas visual (Phase 1) — dibuka dalam <iframe>
 * dari canvas-editor/ (SPA React), bukan lewat navigasi panel biasa. Memakai
 * ulang field schema per-tipe blok dari PageForm::blockFieldFactories(),
 * supaya form yang tampil identik dengan form Filament Builder biasa.
 *
 * Menyunting langsung ke dalam `blocks_draft` milik Page — mencari node
 * berdasarkan `node_id` di seluruh tree (rekursif), lalu menimpa `data`-nya.
 * Saat Simpan berhasil, mem-postMessage ke parent window (canvas-editor)
 * supaya modal ditutup & tree di-refresh.
 */
class PageBlockEditor extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.pages.page-block-editor';

    protected static ?string $slug = 'pages/{pageSlug}/canvas/block/{type}/{nodeId?}';

    public ?array $data = [];

    public PageModel $page;

    public string $blockType;

    public ?string $nodeId = null;

    public function mount(string $pageSlug, string $type, ?string $nodeId = null): void
    {
        $this->page = PageModel::where('slug', $pageSlug)->firstOrFail();
        abort_unless(auth()->user()?->can('Update:Page') ?? false, 403);

        $this->blockType = $type;
        $this->nodeId = $nodeId;

        $existing = $nodeId ? $this->findNode($nodeId) : null;

        $this->form->fill(['data' => $existing['data'] ?? []]);
    }

    public function form(Schema $schema): Schema
    {
        $factory = PageForm::blockFieldFactories()[$this->blockType] ?? null;

        abort_unless($factory !== null, 404, "Tipe blok tidak dikenal: {$this->blockType}");

        return $schema
            ->statePath('data')
            ->components($factory());
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

        // $this->page adalah properti Eloquent model bertipe (public PageModel
        // $page) — Livewire men-dehidrasi properti begini hanya sebagai
        // kelas+primary key, lalu MENGAMBIL ULANG dari DB saat hidrasi request
        // berikutnya (lihat Livewire\Features\SupportModels\ModelSynth::hydrate()).
        // Jadi ini SUDAH baris terbaru per request save() ini, tidak perlu
        // ->fresh() eksplisit — race dengan autosave kanvas React (yang
        // menulis blocks_draft lewat request PUT terpisah) sudah tertutup
        // secara alami oleh perilaku Livewire ini.
        $tree = TreeNormalizer::normalize($this->page->blocks_draft ?? $this->page->visibleBlocks());

        if ($this->nodeId) {
            $tree['tree'] = $this->replaceNode($tree['tree'], $this->nodeId, $state);
        } else {
            // Widget baru tanpa section/column induk eksplisit (kanvas kosong)
            // — bungkus jadi satu section/column baru, konsisten dengan
            // TreeNormalizer::normalize() untuk konten lama.
            $newLeaf = [
                'id' => 'wid_'.Str::random(8),
                'type' => $this->blockType,
                'data' => $state,
            ];
            $tree['tree'][] = [
                'id' => 'sec_'.Str::random(8),
                'type' => 'section',
                'children' => [[
                    'id' => 'col_'.Str::random(8),
                    'type' => 'column',
                    'style' => ['base' => ['width' => 12]],
                    'children' => [$newLeaf],
                ]],
            ];
        }

        $this->page->update(['blocks_draft' => $tree]);

        Notification::make()->success()->title('Widget disimpan')->send();

        $this->js('window.parent.postMessage({ source: "mtsn1-canvas", type: "block-saved" }, "*")');
    }

    /** @return array<string, mixed>|null */
    private function findNode(string $id): ?array
    {
        $tree = TreeNormalizer::normalize($this->page->blocks_draft ?? $this->page->visibleBlocks());

        return $this->searchNode($tree['tree'], $id);
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<string, mixed>|null
     */
    private function searchNode(array $nodes, string $id): ?array
    {
        foreach ($nodes as $node) {
            if (($node['id'] ?? null) === $id) {
                return $node;
            }

            if (! empty($node['children'])) {
                $found = $this->searchNode($node['children'], $id);
                if ($found) {
                    return $found;
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @param  array<string, mixed>  $newData
     * @return array<int, array<string, mixed>>
     */
    private function replaceNode(array $nodes, string $id, array $newData): array
    {
        foreach ($nodes as &$node) {
            if (($node['id'] ?? null) === $id) {
                $node['data'] = $newData;
            } elseif (! empty($node['children'])) {
                $node['children'] = $this->replaceNode($node['children'], $id, $newData);
            }
        }

        return $nodes;
    }
}
