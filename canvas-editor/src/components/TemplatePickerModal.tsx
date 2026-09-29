import { useEffect, useState } from "react";
import { listSectionTemplates, type SectionTemplateSummary } from "../api";

export function TemplatePickerModal({
  onClose,
  onPick,
}: {
  onClose: () => void;
  onPick: (slug: string) => void;
}) {
  const [templates, setTemplates] = useState<SectionTemplateSummary[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    listSectionTemplates()
      .then(setTemplates)
      .catch((e) => setError(String(e)));
  }, []);

  return (
    <div className="canvas-modal-overlay" onClick={onClose}>
      <div className="canvas-modal" onClick={(e) => e.stopPropagation()}>
        <div className="canvas-modal-header">
          <h2>Sisipkan dari Template</h2>
          <button type="button" onClick={onClose}>×</button>
        </div>

        {error && <p className="canvas-error-banner">{error}</p>}
        {!templates && !error && <p className="canvas-empty-inline">Memuat…</p>}
        {templates && templates.length === 0 && (
          <p className="canvas-empty-inline">
            Belum ada template. Simpan section sebagai template dulu lewat tombol 📄 pada section.
          </p>
        )}

        <div className="canvas-widget-picker-grid">
          {templates?.map((t) => (
            <button
              key={t.id}
              type="button"
              className="canvas-widget-picker-item"
              onClick={() => onPick(t.slug)}
            >
              {t.name}
              <span className="canvas-template-meta"> · {t.widgetCount} widget</span>
            </button>
          ))}
        </div>
      </div>
    </div>
  );
}
