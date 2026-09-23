import { useDroppable } from "@dnd-kit/core";
import { useSortable } from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
import { SortableContext, verticalListSortingStrategy } from "@dnd-kit/sortable";
import { useRef, useState } from "react";
import type { ColumnNode, WidgetNode } from "../types";
import { WidgetCard } from "./WidgetCard";

const COLUMN_WIDTHS = [3, 4, 6, 8, 9, 12];

/** Bulatkan ke lebar kolom terdekat yang valid (kosakata StyleResolver::COLUMN_WIDTHS). */
function snapWidth(raw: number): number {
  return COLUMN_WIDTHS.reduce((closest, w) => (Math.abs(w - raw) < Math.abs(closest - raw) ? w : closest));
}

/** Zona jatuh untuk kolom kosong — SortableContext tanpa item bukan target drop yang valid. */
function EmptyColumnDropZone({ columnId }: { columnId: string }) {
  const { setNodeRef, isOver } = useDroppable({ id: `${columnId}__empty`, data: { kind: "column-empty", columnId } });
  return (
    <p ref={setNodeRef} className={`canvas-empty-inline${isOver ? " canvas-empty-inline-over" : ""}`}>
      Kosong — seret widget ke sini.
    </p>
  );
}

export function ColumnCard({
  column,
  sectionId,
  onChange,
  onRemove,
  onAddWidget,
  onEditWidget,
  onSelectStyle,
  selectedNodeId,
}: {
  column: ColumnNode;
  sectionId: string;
  onChange: (c: ColumnNode) => void;
  onRemove: () => void;
  onAddWidget: () => void;
  onEditWidget: (node: WidgetNode) => void;
  onSelectStyle: (kind: "section" | "column", nodeId: string) => void;
  selectedNodeId: string | null;
}) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: column.id,
    data: { kind: "column", sectionId },
  });
  const columnElRef = useRef<HTMLDivElement | null>(null);
  const [resizing, setResizing] = useState(false);
  const [liveWidth, setLiveWidth] = useState<number | null>(null);

  function setRefs(el: HTMLDivElement | null) {
    setNodeRef(el);
    columnElRef.current = el;
  }

  function setWidth(newWidth: number) {
    onChange({ ...column, style: { ...column.style, base: { ...column.style?.base, width: newWidth } } });
  }

  /**
   * Seret tepi kanan kolom untuk resize — dihitung relatif terhadap lebar
   * section induk (bukan window), lalu di-snap ke skala 12 kolom yang sama
   * dengan dropdown lama (StyleResolver::COLUMN_WIDTHS backend), supaya
   * kedua cara mengatur lebar tetap menghasilkan nilai yang sama-sama valid.
   */
  function handleResizeStart(e: React.PointerEvent) {
    e.preventDefault();
    e.stopPropagation(); // jangan ikut memicu drag-reorder useSortable di header
    const sectionEl = columnElRef.current?.closest(".canvas-columns") as HTMLElement | null;
    if (!sectionEl) return;

    const sectionWidth = sectionEl.getBoundingClientRect().width;
    const startX = e.clientX;
    const startWidth = width;

    setResizing(true);

    function onMove(ev: PointerEvent) {
      const deltaCols = ((ev.clientX - startX) / sectionWidth) * 12;
      const raw = Math.min(12, Math.max(1, startWidth + deltaCols));
      setLiveWidth(snapWidth(raw));
    }

    function onUp() {
      window.removeEventListener("pointermove", onMove);
      window.removeEventListener("pointerup", onUp);
      setResizing(false);
      setLiveWidth((finalWidth) => {
        if (finalWidth !== null && finalWidth !== startWidth) setWidth(finalWidth);
        return null;
      });
    }

    window.addEventListener("pointermove", onMove);
    window.addEventListener("pointerup", onUp);
  }

  function removeWidget(id: string) {
    onChange({ ...column, children: column.children.filter((w) => w.id !== id) });
  }

  function handleRemove() {
    const message =
      column.children.length > 0
        ? `Hapus kolom ini beserta ${column.children.length} widget di dalamnya?`
        : "Hapus kolom kosong ini?";
    if (window.confirm(message)) onRemove();
  }

  const width = (column.style?.base?.width as number | undefined) ?? 12;
  const displayWidth = liveWidth ?? width;
  const isStyleSelected = selectedNodeId === column.id;

  return (
    <div
      ref={setRefs}
      style={{
        transform: CSS.Transform.toString(transform),
        transition: resizing ? "none" : transition,
        flexBasis: `${(displayWidth / 12) * 100}%`,
        opacity: isDragging ? 0.4 : 1,
      }}
      className={`canvas-column${isStyleSelected ? " canvas-node-style-selected" : ""}${resizing ? " canvas-column-resizing" : ""}`}
    >
      <div className="canvas-column-header" {...attributes} {...listeners}>
        <span className="canvas-drag-handle">⠿ Kolom ({displayWidth}/12)</span>
        <div className="canvas-column-actions">
          <button
            type="button"
            onClick={() => onSelectStyle("column", column.id)}
            className={`canvas-btn-ghost${isStyleSelected ? " canvas-btn-ghost-active" : ""}`}
          >
            🎨
          </button>
          <select value={width} onChange={(e) => setWidth(Number(e.target.value))}>
            {COLUMN_WIDTHS.map((w) => (
              <option key={w} value={w}>{w}/12</option>
            ))}
          </select>
          <button type="button" onClick={handleRemove} className="canvas-btn-danger">×</button>
        </div>
      </div>

      <SortableContext id={column.id} items={column.children.map((w) => w.id)} strategy={verticalListSortingStrategy}>
        <div className="canvas-widgets" data-droppable-column={column.id}>
          {column.children.map((w) => (
            <WidgetCard
              key={w.id}
              widget={w}
              columnId={column.id}
              onEdit={() => onEditWidget(w)}
              onRemove={() => removeWidget(w.id)}
            />
          ))}
          {column.children.length === 0 && <EmptyColumnDropZone columnId={column.id} />}
        </div>
      </SortableContext>

      <button type="button" onClick={onAddWidget} className="canvas-btn-ghost canvas-add-widget">
        + Tambah widget
      </button>

      {/* Seret untuk resize — alternatif visual dari dropdown lebar di atas. */}
      <div
        className="canvas-column-resize-handle"
        onPointerDown={handleResizeStart}
        title="Seret untuk ubah lebar kolom"
      />
    </div>
  );
}
