import { useCallback, useEffect, useRef, useState } from "react";
import {
  DndContext,
  type DragEndEvent,
  PointerSensor,
  useSensor,
  useSensors,
} from "@dnd-kit/core";
import { SortableContext, verticalListSortingStrategy, arrayMove } from "@dnd-kit/sortable";
import { fetchTree, saveTree, publishTree, discardDraft, config } from "./api";
import type { NodeStyle, SectionNode, TreeResponse, WidgetNode } from "./types";
import { SectionCard } from "./components/SectionCard";
import { WidgetPickerModal } from "./components/WidgetPickerModal";
import { BlockEditorModal } from "./components/BlockEditorModal";
import { StylePanel, type StyleTarget } from "./components/StylePanel";
import { newSection, moveColumn, moveWidget, updateNodeStyle, locate } from "./tree-ops";

type SaveStatus = "idle" | "saving" | "saved" | "error";

export function App() {
  const [tree, setTree] = useState<SectionNode[]>([]);
  const [loading, setLoading] = useState(true);
  const [loadError, setLoadError] = useState<string | null>(null);
  const [saveStatus, setSaveStatus] = useState<SaveStatus>("idle");
  const [saveError, setSaveError] = useState<string | null>(null);
  const [publishing, setPublishing] = useState(false);
  const [publishedAt, setPublishedAt] = useState<string | null>(null);
  const [pickerForColumn, setPickerForColumn] = useState<string | null>(null);
  const [editingNode, setEditingNode] = useState<{ nodeId: string | null; type: string } | null>(null);
  const [selectedStyleNodeId, setSelectedStyleNodeId] = useState<string | null>(null);
  const iframeRef = useRef<HTMLIFrameElement>(null);
  const saveTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const pendingTree = useRef<SectionNode[] | null>(null);
  /** true bila `tree` lokal punya perubahan yang belum berhasil ditulis ke server. */
  const dirty = useRef(false);

  const loadTree = useCallback(() => {
    setLoading(true);
    setLoadError(null);
    return fetchTree()
      .then((res) => setTree(res.tree))
      .catch((e) => setLoadError(String(e)))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    loadTree();
  }, [loadTree]);

  const persist = useCallback(async (next: SectionNode[]) => {
    setSaveStatus("saving");
    setSaveError(null);
    try {
      await saveTree(next as unknown as TreeResponse["tree"]);
      dirty.current = false;
      setSaveStatus("saved");
      iframeRef.current?.contentWindow?.location.reload();
    } catch (e) {
      // dirty TETAP true — perubahan belum benar-benar tersimpan di server,
      // flushPendingSave() (dipanggil sebelum buka modal widget) harus tahu ini.
      setSaveStatus("error");
      setSaveError(String(e));
    }
  }, []);

  const scheduleSave = useCallback(
    (next: SectionNode[]) => {
      setTree(next);
      pendingTree.current = next;
      dirty.current = true;
      if (saveTimer.current) clearTimeout(saveTimer.current);
      saveTimer.current = setTimeout(() => {
        if (pendingTree.current) void persist(pendingTree.current);
      }, 800);
    },
    [persist],
  );

  function retrySave() {
    if (pendingTree.current) void persist(pendingTree.current);
  }

  /**
   * Selesaikan autosave yang masih tertunda SEBELUM membuka modal widget
   * (App\Filament\Pages\PageBlockEditor, Livewire di dalam iframe). Tanpa
   * ini ada race: modal itu membaca+menulis ulang `blocks_draft` di server
   * secara independen dari autosave kanvas — kalau drag/reorder baru saja
   * terjadi (timer 800ms belum jalan) lalu modal disimpan lebih dulu, saat
   * timer autosave akhirnya jalan ia mengirim state tree LAMA (dari sebelum
   * modal dibuka) dan diam-diam menimpa balik perubahan yang baru disimpan
   * modal. Flush memastikan urutan tulis ke server selalu deterministik:
   * autosave kanvas dulu (kalau ada yang tertunda), baru modal boleh dibuka.
   *
   * @returns false bila gagal menyimpan — pemanggil harus MEMBATALKAN buka
   * modal (kalau tidak, modal akan menulis di atas draf server yang basi).
   */
  async function flushPendingSave(): Promise<boolean> {
    if (saveTimer.current) {
      clearTimeout(saveTimer.current);
      saveTimer.current = null;
    }
    if (dirty.current && pendingTree.current) {
      await persist(pendingTree.current);
    }
    return !dirty.current;
  }

  async function handlePublish() {
    if (
      !window.confirm(
        "Terbitkan halaman ini? Isi kanvas saat ini akan langsung menggantikan konten yang tampil di halaman publik.",
      )
    ) {
      return;
    }
    // Publish membaca blocks_draft APA ADANYA di server — pastikan perubahan
    // lokal yang belum ter-autosave (mis. drag baru saja terjadi) ikut
    // tersimpan dulu, supaya yang diterbitkan benar-benar isi kanvas terkini.
    const ok = await flushPendingSave();
    if (!ok) {
      setSaveError("Gagal menyimpan perubahan terbaru — coba lagi sebelum menerbitkan.");
      return;
    }
    setPublishing(true);
    try {
      const res = await publishTree();
      setPublishedAt(res.published_at);
    } catch (e) {
      setSaveError(String(e));
    } finally {
      setPublishing(false);
    }
  }

  async function handleDiscardDraft() {
    if (
      !window.confirm(
        "Buang semua perubahan draf yang belum diterbitkan? Kanvas akan kembali ke isi halaman yang sedang tayang.",
      )
    ) {
      return;
    }
    try {
      const res = await discardDraft();
      setTree(res.tree);
      iframeRef.current?.contentWindow?.location.reload();
    } catch (e) {
      setSaveError(String(e));
    }
  }

  // Widget disimpan dari PageBlockEditor (Livewire, di dalam iframe modal)
  // menulis langsung ke blocks_draft di server — reload tree kita di sini
  // supaya kanvas React ikut ter-update, lalu refresh preview.
  useEffect(() => {
    function onMessage(e: MessageEvent) {
      if (e.data?.source !== "mtsn1-canvas") return;
      if (e.data.type === "block-saved") {
        setEditingNode(null);
        fetchTree().then((res) => setTree(res.tree));
        iframeRef.current?.contentWindow?.location.reload();
      }
    }
    window.addEventListener("message", onMessage);
    return () => window.removeEventListener("message", onMessage);
  }, []);

  const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 4 } }));

  /**
   * Satu DndContext untuk seluruh kanvas (section, kolom, widget) — dnd-kit
   * tidak tahu "level" tree, jadi active/over dibedakan lewat `data.current.kind`
   * yang disetel di useSortable()/useDroppable() masing-masing komponen.
   * Section hanya reorder di daftar section (tidak berpindah level); kolom
   * bisa berpindah section; widget bisa berpindah kolom — lihat tree-ops.ts.
   */
  function handleDragEnd(e: DragEndEvent) {
    const { active, over } = e;
    if (!over) return;

    const activeKind = active.data.current?.kind as string | undefined;
    const overData = over.data.current as { kind?: string; sectionId?: string; columnId?: string } | undefined;

    if (activeKind === "section") {
      if (active.id === over.id) return;
      const oldIndex = tree.findIndex((s) => s.id === active.id);
      const newIndex = tree.findIndex((s) => s.id === over.id);
      if (oldIndex === -1 || newIndex === -1) return;
      scheduleSave(arrayMove(tree, oldIndex, newIndex));
      return;
    }

    if (activeKind === "column") {
      const overKind = overData?.kind;
      let targetSectionId: string | undefined;
      let beforeColumnId: string | null = null;

      if (overKind === "column") {
        targetSectionId = overData?.sectionId;
        beforeColumnId = over.id as string;
      } else if (overKind === "section-empty") {
        targetSectionId = overData?.sectionId;
      } else {
        return; // dropped somewhere that isn't a valid column target
      }
      if (!targetSectionId) return;

      scheduleSave(moveColumn(tree, active.id as string, targetSectionId, beforeColumnId));
      return;
    }

    if (activeKind === "widget") {
      const overKind = overData?.kind;
      let targetColumnId: string | undefined;
      let beforeWidgetId: string | null = null;

      if (overKind === "widget") {
        targetColumnId = overData?.columnId;
        beforeWidgetId = over.id as string;
      } else if (overKind === "column-empty") {
        targetColumnId = overData?.columnId;
      } else {
        return; // dropped somewhere that isn't a valid widget target
      }
      if (!targetColumnId) return;

      scheduleSave(moveWidget(tree, active.id as string, targetColumnId, beforeWidgetId));
    }
  }

  function addSection() {
    scheduleSave([...tree, newSection()]);
  }

  function updateSection(updated: SectionNode) {
    scheduleSave(tree.map((s) => (s.id === updated.id ? updated : s)));
  }

  function removeSection(id: string) {
    scheduleSave(tree.filter((s) => s.id !== id));
  }

  function openWidgetPicker(columnId: string) {
    setPickerForColumn(columnId);
  }

  async function pickWidgetType(type: string) {
    setPickerForColumn(null);
    const ok = await flushPendingSave();
    if (!ok) {
      setSaveError("Gagal menyimpan perubahan sebelumnya — coba lagi sebelum menambah widget baru.");
      return;
    }
    setEditingNode({ nodeId: null, type });
  }

  async function editWidget(node: WidgetNode) {
    const ok = await flushPendingSave();
    if (!ok) {
      setSaveError("Gagal menyimpan perubahan sebelumnya — coba lagi sebelum membuka widget ini.");
      return;
    }
    setEditingNode({ nodeId: node.id, type: node.type });
  }

  function selectStyle(kind: "section" | "column", nodeId: string) {
    setSelectedStyleNodeId((current) => (current === nodeId ? null : nodeId));
  }

  function updateStyle(nodeId: string, style: NodeStyle) {
    scheduleSave(updateNodeStyle(tree, nodeId, style));
  }

  const styleTarget: StyleTarget | null = (() => {
    if (!selectedStyleNodeId) return null;
    const loc = locate(tree, selectedStyleNodeId);
    if (!loc || loc.kind === "widget") return null;
    const node =
      loc.kind === "section" ? tree[loc.sectionIndex] : tree[loc.sectionIndex].children[loc.columnIndex];
    return { kind: loc.kind, nodeId: selectedStyleNodeId, style: node.style };
  })();

  if (loading) return <p className="p-4 text-sm text-gray-500">Memuat kanvas…</p>;
  if (loadError) {
    return (
      <div className="p-4 text-sm text-red-600">
        <p>{loadError}</p>
        <button type="button" onClick={loadTree} className="canvas-btn-ghost mt-2">
          Coba lagi
        </button>
      </div>
    );
  }

  return (
    <div className="canvas-shell">
      <div className="canvas-editor-pane">
        <div className="canvas-toolbar">
          <button type="button" onClick={addSection} className="canvas-btn-primary">
            + Tambah Section
          </button>
          <button
            type="button"
            onClick={handlePublish}
            disabled={publishing}
            className="canvas-btn-publish"
          >
            {publishing ? "Menerbitkan…" : "Terbitkan"}
          </button>
          <button type="button" onClick={handleDiscardDraft} className="canvas-btn-ghost">
            Buang draf
          </button>
          <span className={`canvas-status canvas-status-${saveStatus}`}>
            {saveStatus === "saving" && "Menyimpan…"}
            {saveStatus === "saved" && "Draf tersimpan"}
            {saveStatus === "error" && (
              <>
                Gagal menyimpan.{" "}
                <button type="button" onClick={retrySave} className="canvas-retry-link">
                  Coba lagi
                </button>
              </>
            )}
            {saveStatus === "idle" && "Belum ada perubahan"}
          </span>
          {publishedAt && <span className="canvas-status">Diterbitkan {new Date(publishedAt).toLocaleTimeString("id-ID")}</span>}
        </div>
        {saveError && saveStatus === "error" && (
          <p className="canvas-error-banner">{saveError}</p>
        )}

        <DndContext sensors={sensors} onDragEnd={handleDragEnd}>
          <SortableContext items={tree.map((s) => s.id)} strategy={verticalListSortingStrategy}>
            <div className="canvas-sections">
              {tree.map((section) => (
                <SectionCard
                  key={section.id}
                  section={section}
                  onChange={updateSection}
                  onRemove={() => removeSection(section.id)}
                  onAddWidget={openWidgetPicker}
                  onEditWidget={editWidget}
                  onSelectStyle={selectStyle}
                  selectedNodeId={selectedStyleNodeId}
                />
              ))}
              {tree.length === 0 && (
                <p className="canvas-empty">Belum ada section. Klik "+ Tambah Section" untuk mulai.</p>
              )}
            </div>
          </SortableContext>
        </DndContext>
      </div>

      <div className="canvas-preview-pane">
        <iframe ref={iframeRef} src={config.previewUrl} title="Pratinjau halaman" />
      </div>

      {styleTarget && (
        <StylePanel
          target={styleTarget}
          onChange={(style) => updateStyle(styleTarget.nodeId, style)}
          onClose={() => setSelectedStyleNodeId(null)}
        />
      )}

      {pickerForColumn && (
        <WidgetPickerModal
          onClose={() => setPickerForColumn(null)}
          onPick={pickWidgetType}
        />
      )}

      {editingNode && (
        <BlockEditorModal
          type={editingNode.type}
          nodeId={editingNode.nodeId}
          onClose={() => setEditingNode(null)}
        />
      )}
    </div>
  );
}
