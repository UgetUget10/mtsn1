import type { NodeStyle } from "../types";
import { PADDING_Y_OPTIONS, BACKGROUND_PRESETS, TEXT_ALIGN_OPTIONS, HEX_COLOR_RE } from "../style-vocab";

export type StyleTarget = { kind: "section" | "column"; nodeId: string; style: NodeStyle | undefined };

/**
 * Panel styling (Phase 2) — muncul saat section/kolom dipilih di kanvas.
 * Hanya mengedit breakpoint `base` (mobile-first / semua ukuran layar);
 * kontrol per-breakpoint (md/lg/xl) tetap dalam kosakata yang sama, disiapkan
 * untuk iterasi berikutnya, TIDAK diekspos di UI Phase 2 awal supaya panel
 * tidak langsung penuh sebelum kebutuhannya jelas dari pemakaian nyata.
 */
export function StylePanel({
  target,
  onChange,
  onClose,
}: {
  target: StyleTarget;
  onChange: (style: NodeStyle) => void;
  onClose: () => void;
}) {
  const base = target.style?.base ?? {};

  function setBase(patch: Record<string, unknown>) {
    onChange({ ...target.style, base: { ...base, ...patch } });
  }

  function clearProp(key: string) {
    const next = { ...base };
    delete next[key];
    onChange({ ...target.style, base: next });
  }

  const currentBg = typeof base.background === "string" ? base.background : "";
  const isPresetBg = BACKGROUND_PRESETS.some((p) => p.value === currentBg);
  const isCustomHex = currentBg !== "" && !isPresetBg;

  return (
    <div className="canvas-style-panel">
      <div className="canvas-style-panel-header">
        <h2>Gaya {target.kind === "section" ? "Section" : "Kolom"}</h2>
        <button type="button" onClick={onClose}>×</button>
      </div>

      <div className="canvas-style-field">
        <label>Warna latar</label>
        <div className="canvas-style-swatches">
          {BACKGROUND_PRESETS.map((preset) => (
            <button
              key={preset.value}
              type="button"
              title={preset.label}
              className={`canvas-swatch${currentBg === preset.value ? " canvas-swatch-active" : ""}`}
              style={{ background: preset.swatch }}
              onClick={() => setBase({ background: preset.value })}
            />
          ))}
          <label className="canvas-swatch canvas-swatch-custom" title="Warna bebas">
            <input
              type="color"
              value={isCustomHex ? currentBg : "#ffffff"}
              onChange={(e) => setBase({ background: e.target.value })}
            />
          </label>
        </div>
        {currentBg && (
          <button type="button" className="canvas-style-clear" onClick={() => clearProp("background")}>
            Hapus warna latar
          </button>
        )}
      </div>

      <div className="canvas-style-field">
        <label htmlFor="style-padding-y">Jarak atas/bawah</label>
        <select
          id="style-padding-y"
          value={typeof base.paddingY === "string" ? base.paddingY : ""}
          onChange={(e) => (e.target.value ? setBase({ paddingY: e.target.value }) : clearProp("paddingY"))}
        >
          <option value="">Default</option>
          {PADDING_Y_OPTIONS.map((opt) => (
            <option key={opt.value} value={opt.value}>{opt.label}</option>
          ))}
        </select>
      </div>

      <div className="canvas-style-field">
        <label htmlFor="style-text-align">Perataan teks</label>
        <select
          id="style-text-align"
          value={typeof base.textAlign === "string" ? base.textAlign : ""}
          onChange={(e) => (e.target.value ? setBase({ textAlign: e.target.value }) : clearProp("textAlign"))}
        >
          <option value="">Default</option>
          {TEXT_ALIGN_OPTIONS.map((opt) => (
            <option key={opt.value} value={opt.value}>{opt.label}</option>
          ))}
        </select>
      </div>

      {isCustomHex && !HEX_COLOR_RE.test(currentBg) && (
        <p className="canvas-style-warning">Format warna tidak valid — pakai kode hex (#rrggbb).</p>
      )}
    </div>
  );
}
