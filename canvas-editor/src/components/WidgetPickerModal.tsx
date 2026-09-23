import { useState } from "react";
import { WIDGET_LABELS } from "../types";

export function WidgetPickerModal({
  onClose,
  onPick,
}: {
  onClose: () => void;
  onPick: (type: string) => void;
}) {
  const [query, setQuery] = useState("");

  const items = Object.entries(WIDGET_LABELS)
    .filter(([type]) => type !== "reusable")
    .filter(([, label]) => label.toLowerCase().includes(query.trim().toLowerCase()));

  return (
    <div className="canvas-modal-overlay" onClick={onClose}>
      <div className="canvas-modal" onClick={(e) => e.stopPropagation()}>
        <div className="canvas-modal-header">
          <h2>Pilih Widget</h2>
          <button type="button" onClick={onClose}>×</button>
        </div>
        <input
          type="text"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder="Cari widget…"
          autoFocus
          className="canvas-widget-picker-search"
        />
        <div className="canvas-widget-picker-grid">
          {items.map(([type, label]) => (
            <button key={type} type="button" className="canvas-widget-picker-item" onClick={() => onPick(type)}>
              {label}
            </button>
          ))}
          {items.length === 0 && <p className="canvas-empty-inline">Tidak ada widget yang cocok.</p>}
        </div>
      </div>
    </div>
  );
}
