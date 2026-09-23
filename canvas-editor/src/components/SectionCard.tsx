import { useDroppable } from "@dnd-kit/core";
import { useSortable } from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
import { SortableContext, horizontalListSortingStrategy } from "@dnd-kit/sortable";
import type { ColumnNode, SectionNode, WidgetNode } from "../types";
import { ColumnCard } from "./ColumnCard";
import { newColumn } from "../tree-ops";

/** Zona jatuh untuk section kosong — SortableContext tanpa item bukan target drop yang valid. */
function EmptySectionDropZone({ sectionId }: { sectionId: string }) {
  const { setNodeRef, isOver } = useDroppable({ id: `${sectionId}__empty`, data: { kind: "section-empty", sectionId } });
  return (
    <p ref={setNodeRef} className={`canvas-empty-inline${isOver ? " canvas-empty-inline-over" : ""}`}>
      Section kosong — klik "+ Kolom", atau seret kolom ke sini.
    </p>
  );
}

export function SectionCard({
  section,
  onChange,
  onRemove,
  onAddWidget,
  onEditWidget,
  onSelectStyle,
  selectedNodeId,
}: {
  section: SectionNode;
  onChange: (s: SectionNode) => void;
  onRemove: () => void;
  onAddWidget: (columnId: string) => void;
  onEditWidget: (node: WidgetNode) => void;
  onSelectStyle: (kind: "section" | "column", nodeId: string) => void;
  selectedNodeId: string | null;
}) {
  const isStyleSelected = selectedNodeId === section.id;
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: section.id,
    data: { kind: "section" },
  });

  function updateColumn(updated: ColumnNode) {
    onChange({ ...section, children: section.children.map((c) => (c.id === updated.id ? updated : c)) });
  }

  function removeColumn(id: string) {
    onChange({ ...section, children: section.children.filter((c) => c.id !== id) });
  }

  function addColumn() {
    onChange({ ...section, children: [...section.children, newColumn()] });
  }

  function handleRemove() {
    const widgetCount = section.children.reduce((sum, c) => sum + c.children.length, 0);
    const message =
      widgetCount > 0
        ? `Hapus section ini beserta ${widgetCount} widget di dalamnya? Perubahan baru benar-benar hilang setelah section ini disimpan (autosave) dan halaman diterbitkan.`
        : "Hapus section kosong ini?";
    if (window.confirm(message)) onRemove();
  }

  return (
    <div
      ref={setNodeRef}
      style={{ transform: CSS.Transform.toString(transform), transition, opacity: isDragging ? 0.4 : 1 }}
      className={`canvas-section${isStyleSelected ? " canvas-node-style-selected" : ""}`}
    >
      <div className="canvas-section-header" {...attributes} {...listeners}>
        <span className="canvas-drag-handle">⠿ Section</span>
        <div className="canvas-section-actions">
          <button
            type="button"
            onClick={() => onSelectStyle("section", section.id)}
            className={`canvas-btn-ghost${isStyleSelected ? " canvas-btn-ghost-active" : ""}`}
          >
            🎨 Gaya
          </button>
          <button type="button" onClick={addColumn} className="canvas-btn-ghost">+ Kolom</button>
          <button type="button" onClick={handleRemove} className="canvas-btn-danger">Hapus</button>
        </div>
      </div>

      <SortableContext
        id={section.id}
        items={section.children.map((c) => c.id)}
        strategy={horizontalListSortingStrategy}
      >
        <div className="canvas-columns" data-droppable-section={section.id}>
          {section.children.map((col) => (
            <ColumnCard
              key={col.id}
              column={col}
              sectionId={section.id}
              onChange={updateColumn}
              onRemove={() => removeColumn(col.id)}
              onAddWidget={() => onAddWidget(col.id)}
              onEditWidget={onEditWidget}
              onSelectStyle={onSelectStyle}
              selectedNodeId={selectedNodeId}
            />
          ))}
          {section.children.length === 0 && <EmptySectionDropZone sectionId={section.id} />}
        </div>
      </SortableContext>
    </div>
  );
}
