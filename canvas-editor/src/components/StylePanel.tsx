import type { NodeStyle } from "../types";
import {
  PADDING_Y_OPTIONS,
  BACKGROUND_PRESETS,
  TEXT_ALIGN_OPTIONS,
  HEX_COLOR_RE,
  SPACING_OPTIONS,
  SPACING_SIDES,
  FONT_SIZE_OPTIONS,
  FONT_WEIGHT_OPTIONS,
  BORDER_STYLE_OPTIONS,
  BORDER_WIDTH_OPTIONS,
  RADIUS_OPTIONS,
  SHADOW_OPTIONS,
} from "../style-vocab";

export type StyleTarget = { kind: "section" | "column" | "widget"; nodeId: string; style: NodeStyle | undefined };

const KIND_LABELS: Record<StyleTarget["kind"], string> = { section: "Section", column: "Kolom", widget: "Widget" };

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
        <h2>Gaya {KIND_LABELS[target.kind]}</h2>
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

      <div className="canvas-style-section">
        <h3>Spasi luar (margin)</h3>
        <SpacingGrid box="margin" base={base} setBase={setBase} clearProp={clearProp} />
      </div>

      <div className="canvas-style-section">
        <h3>Spasi dalam (padding)</h3>
        <SpacingGrid box="padding" base={base} setBase={setBase} clearProp={clearProp} />
      </div>

      <div className="canvas-style-section">
        <h3>Tipografi</h3>
        <div className="canvas-style-field">
          <label htmlFor="style-font-size">Ukuran teks</label>
          <select
            id="style-font-size"
            value={typeof base.fontSize === "string" ? base.fontSize : ""}
            onChange={(e) => (e.target.value ? setBase({ fontSize: e.target.value }) : clearProp("fontSize"))}
          >
            <option value="">Default</option>
            {FONT_SIZE_OPTIONS.map((opt) => (
              <option key={opt.value} value={opt.value}>{opt.label}</option>
            ))}
          </select>
        </div>
        <div className="canvas-style-field">
          <label htmlFor="style-font-weight">Ketebalan</label>
          <select
            id="style-font-weight"
            value={typeof base.fontWeight === "string" ? base.fontWeight : ""}
            onChange={(e) => (e.target.value ? setBase({ fontWeight: e.target.value }) : clearProp("fontWeight"))}
          >
            <option value="">Default</option>
            {FONT_WEIGHT_OPTIONS.map((opt) => (
              <option key={opt.value} value={opt.value}>{opt.label}</option>
            ))}
          </select>
        </div>
        <div className="canvas-style-field">
          <label>Warna teks</label>
          <div className="canvas-style-swatches">
            <label className="canvas-swatch canvas-swatch-custom" title="Warna teks">
              <input
                type="color"
                value={typeof base.textColor === "string" && HEX_COLOR_RE.test(base.textColor) ? base.textColor : "#000000"}
                onChange={(e) => setBase({ textColor: e.target.value })}
              />
            </label>
          </div>
          {typeof base.textColor === "string" && (
            <button type="button" className="canvas-style-clear" onClick={() => clearProp("textColor")}>
              Hapus warna teks
            </button>
          )}
        </div>
      </div>

      <div className="canvas-style-section">
        <h3>Border & bayangan</h3>
        <div className="canvas-style-field">
          <label htmlFor="style-border-style">Gaya garis</label>
          <select
            id="style-border-style"
            value={typeof base.borderStyle === "string" ? base.borderStyle : ""}
            onChange={(e) => (e.target.value ? setBase({ borderStyle: e.target.value }) : clearProp("borderStyle"))}
          >
            <option value="">Default</option>
            {BORDER_STYLE_OPTIONS.map((opt) => (
              <option key={opt.value} value={opt.value}>{opt.label}</option>
            ))}
          </select>
        </div>
        <div className="canvas-style-field">
          <label htmlFor="style-border-width">Ketebalan garis</label>
          <select
            id="style-border-width"
            value={typeof base.borderWidth === "string" ? base.borderWidth : ""}
            onChange={(e) => (e.target.value ? setBase({ borderWidth: e.target.value }) : clearProp("borderWidth"))}
          >
            <option value="">Default</option>
            {BORDER_WIDTH_OPTIONS.map((opt) => (
              <option key={opt.value} value={opt.value}>{opt.label}</option>
            ))}
          </select>
        </div>
        <div className="canvas-style-field">
          <label>Warna garis</label>
          <div className="canvas-style-swatches">
            <label className="canvas-swatch canvas-swatch-custom" title="Warna garis">
              <input
                type="color"
                value={typeof base.borderColor === "string" && HEX_COLOR_RE.test(base.borderColor) ? base.borderColor : "#000000"}
                onChange={(e) => setBase({ borderColor: e.target.value })}
              />
            </label>
          </div>
          {typeof base.borderColor === "string" && (
            <button type="button" className="canvas-style-clear" onClick={() => clearProp("borderColor")}>
              Hapus warna garis
            </button>
          )}
        </div>
        <div className="canvas-style-field">
          <label htmlFor="style-radius">Sudut</label>
          <select
            id="style-radius"
            value={typeof base.radius === "string" ? base.radius : ""}
            onChange={(e) => (e.target.value ? setBase({ radius: e.target.value }) : clearProp("radius"))}
          >
            <option value="">Default</option>
            {RADIUS_OPTIONS.map((opt) => (
              <option key={opt.value} value={opt.value}>{opt.label}</option>
            ))}
          </select>
        </div>
        <div className="canvas-style-field">
          <label htmlFor="style-shadow">Bayangan</label>
          <select
            id="style-shadow"
            value={typeof base.shadow === "string" ? base.shadow : ""}
            onChange={(e) => (e.target.value ? setBase({ shadow: e.target.value }) : clearProp("shadow"))}
          >
            <option value="">Default</option>
            {SHADOW_OPTIONS.map((opt) => (
              <option key={opt.value} value={opt.value}>{opt.label}</option>
            ))}
          </select>
        </div>
      </div>
    </div>
  );
}

/** Grid 4-sisi (atas/kanan/bawah/kiri) untuk margin atau padding. */
function SpacingGrid({
  box,
  base,
  setBase,
  clearProp,
}: {
  box: "margin" | "padding";
  base: Record<string, unknown>;
  setBase: (patch: Record<string, unknown>) => void;
  clearProp: (key: string) => void;
}) {
  return (
    <div className="canvas-style-spacing-grid">
      {SPACING_SIDES.map((side) => {
        const key = `${box}${side.key}`;
        const value = typeof base[key] === "string" ? (base[key] as string) : "";
        return (
          <div className="canvas-style-field canvas-style-spacing-cell" key={key}>
            <label htmlFor={`style-${key}`}>{side.label}</label>
            <select
              id={`style-${key}`}
              value={value}
              onChange={(e) => (e.target.value ? setBase({ [key]: e.target.value }) : clearProp(key))}
            >
              <option value="">—</option>
              {SPACING_OPTIONS.map((opt) => (
                <option key={opt.value} value={opt.value}>{opt.label}</option>
              ))}
            </select>
          </div>
        );
      })}
    </div>
  );
}
