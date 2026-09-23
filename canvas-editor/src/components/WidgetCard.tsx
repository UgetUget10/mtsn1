import { useSortable } from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
import type { WidgetNode } from "../types";
import { WIDGET_LABELS } from "../types";

export function WidgetCard({
  widget,
  columnId,
  onEdit,
  onRemove,
}: {
  widget: WidgetNode;
  columnId: string;
  onEdit: () => void;
  onRemove: () => void;
}) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({
    id: widget.id,
    data: { kind: "widget", columnId },
  });

  return (
    <div
      ref={setNodeRef}
      style={{ transform: CSS.Transform.toString(transform), transition, opacity: isDragging ? 0.4 : 1 }}
      className="canvas-widget"
    >
      <span className="canvas-drag-handle" {...attributes} {...listeners}>⠿</span>
      <button type="button" className="canvas-widget-label" onClick={onEdit}>
        {WIDGET_LABELS[widget.type] ?? widget.type}
      </button>
      <button type="button" onClick={onRemove} className="canvas-btn-danger">×</button>
    </div>
  );
}
