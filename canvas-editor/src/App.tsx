import { useCallback, useEffect, useRef, useState } from "react";
import {
  DndContext,
  type DragEndEvent,
  PointerSensor,
  useSensor,
  useSensors,
} from "@dnd-kit/core";
import { SortableContext, verticalListSortingStrategy, arrayMove } from "@dnd-kit/sortable";
import { fetchTree, saveTree, publishTree, discardDraft, saveSectionTemplate, fetchSectionTemplate, config } from "./api";
import type { NodeStyle, SectionNode, TreeResponse, WidgetNode } from "./types";
import { SectionCard } from "./components/SectionCard";
import { WidgetPickerModal } from "./components/WidgetPickerModal";
import { BlockEditorModal } from "./components/BlockEditorModal";
import { StylePanel, type StyleTarget } from "./components/StylePanel";
import { TemplatePickerModal } from "./components/TemplatePickerModal";
import { newSection, moveColumn, moveWidget, updateNodeStyle, locate, isSameStructure, diffStyles } from "./tree-ops";

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
  const [templatePickerOpen, setTemplatePickerOpen] = useState(false);
  const iframeRef = useRef<HTMLIFrameElement>(null);
  const saveTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
  const pendingTree = useRef<SectionNode[] | null>(null);
  /** true bila `tree` lokal punya perubahan yang belum berhasil ditulis ke server. */
  const dirty = useRef(false);
  /** Tree persis seperti yang terakhir berhasil disimpan ke server — dipakai persist() untuk diff style-only. */
  const lastSavedTree = useRef<SectionNode[]>([]);
  /**
   * Riwayat undo/redo — snapshot tree SEBELUM tiap perubahan terstruktur
   * (scheduleSave). Dibatasi HISTORY_LIMIT supaya memori tidak tumbuh tanpa
   * batas pada sesi kanvas yang lama; hilangnya snapshot tertua tidak
   * masalah karena tetap ada di riwayat revisi backend (HasRevisions) untuk
   * pemulihan jangka panjang — ini cuma undo cepat dalam satu sesi edit.
   */
  const historyPast = useRef<SectionNode[][]>([]);
  const historyFuture = useRef<SectionNode[][]>([]);
  const HISTORY_LIMIT = 50;
  const [, setHistoryTick] = useState(0); // nilainya tak dibaca — cuma pemicu re-render saat panjang riwayat berubah (tombol enabled/disabled)

  const loadTree = useCallback(() => {
    setLoading(true);
    setLoadError(null);
    return fetchTree()
      .then((res) => {
        setTree(res.tree);
        lastSavedTree.current = res.tree;
      })
      .catch((e) => setLoadError(String(e)))
      .finally(() => setLoading(false));
  }, []);

  useEffect(() => {
    loadTree();
  }, [loadTree]);

  const persist = useCallback(async (next: SectionNode[]) => {
    setSaveStatus("saving");
    setSaveError(null);

    // Hitung SEBELUM saveTree() — bandingkan terhadap tree tersimpan
    // terakhir, bukan terhadap `tree` state saat ini (bisa beda kalau ada
    // request lain yang menyusul lebih dulu).
    const previous = lastSavedTree.current;
    const structureUnchanged = isSameStructure(previous, next);
    const styleChanges = structureUnchanged ? diffStyles(previous, next) : [];

    try {
      await saveTree(next as unknown as TreeResponse["tree"]);
      dirty.current = false;
      lastSavedTree.current = next;
      setSaveStatus("saved");

      // Struktur sama & yang berubah cuma style → patch langsung ke preview
      // (CSS custom property per node, lihat compileBaseStyle di frontend),
      // tanpa reload iframe. Reload penuh tetap dipakai untuk perubahan
      // struktural (tambah/hapus/pindah node) karena preview perlu me-render
      // ulang komponen baru — style-only tidak.
      if (structureUnchanged && styleChanges.length > 0) {
        iframeRef.current?.contentWindow?.postMessage(
          { source: "mtsn1-canvas", type: "patch-style", changes: styleChanges },
          "*",
        );
      } else {
        iframeRef.current?.contentWindow?.location.reload();
      }
    } catch (e) {
      // dirty TETAP true — perubahan belum benar-benar tersimpan di server,
      // flushPendingSave() (dipanggil sebelum buka modal widget) harus tahu ini.
      setSaveStatus("error");
      setSaveError(String(e));
    }
  }, []);

  const scheduleSave = useCallback(
    (next: SectionNode[], recordHistory = true) => {
      if (recordHistory) {
        // Simpan tree SAAT INI (sebelum perubahan) ke riwayat undo — dipanggil
        // dengan React state closure, jadi pakai functional update supaya
        // selalu dapat nilai `tree` terbaru, bukan yang ter-capture saat
        // scheduleSave() didefinisikan.
        setTree((current) => {
          historyPast.current = [...historyPast.current, current].slice(-HISTORY_LIMIT);
          historyFuture.current = []; // aksi baru memutus jalur redo lama
          setHistoryTick((t) => t + 1);
          return next;
        });
      } else {
        setTree(next);
      }
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

  function undo() {
    const previous = historyPast.current.pop();
    if (!previous) return;
    setHistoryTick((t) => t + 1);
    historyFuture.current = [...historyFuture.current, tree];
    scheduleSave(previous, false);
  }

  function redo() {
    const next = historyFuture.current.pop();
    if (!next) return;
    setHistoryTick((t) => t + 1);
    historyPast.current = [...historyPast.current, tree];
    scheduleSave(next, false);
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
      lastSavedTree.current = res.tree;
      dirty.current = false;
      historyPast.current = [];
      historyFuture.current = [];
      setHistoryTick((t) => t + 1);
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
      if (e.data?.source === "mtsn1-canvas" && e.data?.type === "block-saved") {
        setEditingNode(null);
        fetchTree().then((res) => {
          setTree(res.tree);
          lastSavedTree.current = res.tree;
        });
        iframeRef.current?.contentWindow?.location.reload();
        return;
      }

      // Klik langsung di preview (canvas-selection-bridge.tsx, frontend/) —
      // pilih node yang sama seolah tombol 🎨 di panel kiri yang diklik,
      // supaya bisa "klik apa yang dilihat" ala Elementor, bukan cuma lewat
      // tombol di kanvas kiri.
      if (e.data?.source === "mtsn1-preview" && e.data?.type === "node-clicked") {
        const nodeId = e.data.nodeId as string;
        const loc = locate(tree, nodeId);
        if (loc) setSelectedStyleNodeId((current) => (current === nodeId ? null : nodeId));
      }
    }
    window.addEventListener("message", onMessage);
    return () => window.removeEventListener("message", onMessage);
  }, [tree]);

  // Beri tahu preview node mana yang sedang dipilih, supaya ada highlight di
  // sana juga (bukan cuma di panel kiri) — arah sebaliknya dari efek di atas.
  useEffect(() => {
    iframeRef.current?.contentWindow?.postMessage(
      { source: "mtsn1-canvas", type: "highlight-node", nodeId: selectedStyleNodeId },
      "*",
    );
  }, [selectedStyleNodeId]);

  // Ctrl/Cmd+Z untuk undo, Ctrl/Cmd+Shift+Z atau Ctrl+Y untuk redo — hanya
  // saat fokus bukan sedang di input teks (supaya tidak bentrok dengan undo
  // bawaan browser di dalam field), dan bukan sedang ada modal terbuka
  // (widget picker/editor/template punya undo teks sendiri).
  useEffect(() => {
    function onKeyDown(e: KeyboardEvent) {
      const tag = (e.target as HTMLElement)?.tagName;
      if (tag === "INPUT" || tag === "TEXTAREA" || (e.target as HTMLElement)?.isContentEditable) return;
      if (!(e.ctrlKey || e.metaKey)) return;

      if (e.key.toLowerCase() === "z" && !e.shiftKey) {
        e.preventDefault();
        undo();
      } else if ((e.key.toLowerCase() === "z" && e.shiftKey) || e.key.toLowerCase() === "y") {
        e.preventDefault();
        redo();
      }
    }
    window.addEventListener("keydown", onKeyDown);
    return () => window.removeEventListener("keydown", onKeyDown);
    // eslint-disable-next-line react-hooks/exhaustive-deps -- undo/redo membaca ref + tree lewat closure; didaftarkan ulang tiap render lewat dependency tree di bawah
  }, [tree]);

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

  async function saveSectionAsTemplate(section: SectionNode) {
    const name = window.prompt("Nama template untuk section ini:");
    if (!name?.trim()) return;
    try {
      await saveSectionTemplate(name.trim(), section);
      window.alert(`Template "${name.trim()}" tersimpan.`);
    } catch (e) {
      setSaveError(String(e));
    }
  }

  async function insertFromTemplate(slug: string) {
    setTemplatePickerOpen(false);
    try {
      const res = await fetchSectionTemplate(slug);
      scheduleSave([...tree, res.section]);
    } catch (e) {
      setSaveError(String(e));
    }
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

  function selectStyle(kind: "section" | "column" | "widget", nodeId: string) {
    setSelectedStyleNodeId((current) => (current === nodeId ? null : nodeId));
  }

  function updateStyle(nodeId: string, style: NodeStyle) {
    scheduleSave(updateNodeStyle(tree, nodeId, style));
  }

  const styleTarget: StyleTarget | null = (() => {
    if (!selectedStyleNodeId) return null;
    const loc = locate(tree, selectedStyleNodeId);
    if (!loc) return null;
    const node =
      loc.kind === "section"
        ? tree[loc.sectionIndex]
        : loc.kind === "column"
          ? tree[loc.sectionIndex].children[loc.columnIndex]
          : tree[loc.sectionIndex].children[loc.columnIndex].children[loc.widgetIndex];
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
          <button type="button" onClick={() => setTemplatePickerOpen(true)} className="canvas-btn-ghost">
            📄 Dari Template
          </button>
          <button
            type="button"
            onClick={undo}
            disabled={historyPast.current.length === 0}
            title="Urungkan (Ctrl+Z)"
            className="canvas-btn-ghost"
          >
            ↶ Urungkan
          </button>
          <button
            type="button"
            onClick={redo}
            disabled={historyFuture.current.length === 0}
            title="Ulangi (Ctrl+Shift+Z)"
            className="canvas-btn-ghost"
          >
            ↷ Ulangi
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
                  onSaveAsTemplate={saveSectionAsTemplate}
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

      {templatePickerOpen && (
        <TemplatePickerModal onClose={() => setTemplatePickerOpen(false)} onPick={insertFromTemplate} />
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
