import type { TreeNodeStyle } from "./types";

/**
 * Kompilasi `style` per node (Phase 2: panel styling) menjadi CSS custom
 * properties, BUKAN kelas Tailwind dinamis — Tailwind v4 men-scan nama kelas
 * di source saat build; halaman builder yang membiarkan editor memilih nilai
 * bebas tidak bisa mensintesis kelas baru saat runtime (constraint deploy:
 * tidak ada rebuild Next.js per edit, lihat memory frontend-deploy-pm2).
 * Custom property SELALU kelas statis (sudah dikompilasi), hanya nilainya
 * yang bervariasi per instance lewat inline style={}.
 *
 * Kosakata harus sinkron dengan backend/app/Support/Blocks/StyleResolver.php
 * — resolver di sana yang jadi sumber kebenaran validasi (nilai yang lolos
 * ke sini SEHARUSNYA sudah bersih, tapi fallback aman tetap diterapkan).
 */

const PADDING_Y_SCALE: Record<string, string> = {
  none: "0",
  sm: "1.5rem",
  md: "2.5rem",
  lg: "3.25rem", // selaras --section-y di globals.css
  xl: "4.25rem", // selaras --section-y-lg di globals.css
};

const SPACING_SCALE: Record<string, string> = {
  none: "0",
  xs: "0.5rem",
  sm: "1rem",
  md: "1.5rem",
  lg: "2rem",
  xl: "3rem",
  "2xl": "4rem",
};

const FONT_SIZE_SCALE: Record<string, string> = {
  xs: "0.75rem",
  sm: "0.875rem",
  base: "1rem",
  lg: "1.125rem",
  xl: "1.25rem",
  "2xl": "1.5rem",
  "3xl": "1.875rem",
  "4xl": "2.25rem",
};

const FONT_WEIGHT_SCALE: Record<string, string> = {
  normal: "400",
  medium: "500",
  semibold: "600",
  bold: "700",
};

const BORDER_WIDTH_SCALE: Record<string, string> = {
  none: "0",
  thin: "1px",
  medium: "2px",
  thick: "4px",
};

const RADIUS_SCALE: Record<string, string> = {
  none: "0",
  sm: "0.25rem",
  md: "0.5rem",
  lg: "1rem",
  full: "9999px",
};

const SHADOW_SCALE: Record<string, string> = {
  none: "none",
  sm: "0 1px 2px rgba(0,0,0,0.06)",
  md: "0 4px 12px rgba(0,0,0,0.10)",
  lg: "0 12px 32px rgba(0,0,0,0.16)",
};

const SPACING_SIDES = ["Top", "Right", "Bottom", "Left"] as const;

const BACKGROUND_TOKENS: Record<string, string> = {
  transparent: "transparent",
  surface: "var(--surface)",
  "surface-muted": "var(--surface-muted)",
  "brand-light": "var(--brand-light)",
  "accent-soft": "var(--accent-soft)",
  "success-soft": "var(--success-soft)",
  "info-soft": "var(--info-soft)",
};

function resolveBackground(value: unknown): string | null {
  if (typeof value !== "string") return null;
  if (value in BACKGROUND_TOKENS) return BACKGROUND_TOKENS[value];
  if (/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/.test(value)) return value;
  return null;
}

function resolvePaddingY(value: unknown): string | null {
  return typeof value === "string" && value in PADDING_Y_SCALE ? PADDING_Y_SCALE[value] : null;
}

function resolveScale(scale: Record<string, string>, value: unknown): string | null {
  return typeof value === "string" && value in scale ? scale[value] : null;
}

function resolveColor(value: unknown): string | null {
  return resolveBackground(value);
}

/** Ekstrak deklarasi CSS spacing (padding/margin 4-sisi) dari props satu breakpoint. */
function spacingDecls(props: Record<string, unknown>): Record<string, string> {
  const out: Record<string, string> = {};
  for (const box of ["padding", "margin"] as const) {
    for (const side of SPACING_SIDES) {
      const val = resolveScale(SPACING_SCALE, props[`${box}${side}`]);
      if (val) out[`${box}${side}`] = val;
    }
  }
  return out;
}

/** Ekstrak deklarasi CSS typography/border/shadow dari props satu breakpoint. */
function visualDecls(props: Record<string, unknown>): Record<string, string> {
  const out: Record<string, string> = {};

  const fontSize = resolveScale(FONT_SIZE_SCALE, props.fontSize);
  if (fontSize) out.fontSize = fontSize;

  const fontWeight = resolveScale(FONT_WEIGHT_SCALE, props.fontWeight);
  if (fontWeight) out.fontWeight = fontWeight;

  const textColor = resolveColor(props.textColor);
  if (textColor) out.color = textColor;

  const borderWidth = resolveScale(BORDER_WIDTH_SCALE, props.borderWidth);
  const borderStyle = typeof props.borderStyle === "string" ? props.borderStyle : null;
  const borderColor = resolveColor(props.borderColor);
  if (borderWidth && borderStyle && borderStyle !== "none") {
    out.borderWidth = borderWidth;
    out.borderStyle = borderStyle;
    out.borderColor = borderColor ?? "currentColor";
  }

  const radius = resolveScale(RADIUS_SCALE, props.radius);
  if (radius) out.borderRadius = radius;

  const shadow = resolveScale(SHADOW_SCALE, props.shadow);
  if (shadow) out.boxShadow = shadow;

  return out;
}

/**
 * Style inline untuk breakpoint dasar (`base`) — dipakai langsung sebagai
 * `style={}` React. Breakpoint lain (md/lg/xl) tidak bisa lewat inline
 * style (CSS inline tak punya media query) — lihat compileResponsiveCss().
 */
export function compileBaseStyle(style: TreeNodeStyle | undefined): React.CSSProperties {
  const base = style?.base;
  if (!base) return {};

  const out: React.CSSProperties & Record<string, string> = {};

  const bg = resolveBackground(base.background);
  if (bg) out.backgroundColor = bg;

  const py = resolvePaddingY(base.paddingY);
  if (py) out.paddingBlock = py;

  if (base.textAlign === "left" || base.textAlign === "center" || base.textAlign === "right") {
    out.textAlign = base.textAlign;
  }

  Object.assign(out, spacingDecls(base), visualDecls(base));

  return out;
}

/**
 * Terapkan compileBaseStyle() langsung ke sebuah elemen DOM — dipakai
 * canvas-selection-bridge.tsx untuk live-patch style saat autosave kanvas
 * mendeteksi perubahan style-only (lihat isSameStructure()/diffStyles() di
 * canvas-editor/src/tree-ops.ts), tanpa reload iframe. Reset dulu ketiga
 * properti sebelum menerapkan yang baru, supaya menghapus style (mis. klik
 * "Hapus warna latar") juga langsung terlihat, bukan cuma penambahan.
 */
const RESETTABLE_PROPS = [
  "backgroundColor",
  "paddingBlock",
  "textAlign",
  "paddingTop",
  "paddingRight",
  "paddingBottom",
  "paddingLeft",
  "marginTop",
  "marginRight",
  "marginBottom",
  "marginLeft",
  "fontSize",
  "fontWeight",
  "color",
  "borderWidth",
  "borderStyle",
  "borderColor",
  "borderRadius",
  "boxShadow",
] as const;

export function applyBaseStyleToElement(el: HTMLElement, style: TreeNodeStyle | undefined): void {
  for (const prop of RESETTABLE_PROPS) {
    (el.style as unknown as Record<string, string>)[prop] = "";
  }

  const compiled = compileBaseStyle(style) as Record<string, string>;
  for (const [key, value] of Object.entries(compiled)) {
    (el.style as unknown as Record<string, string>)[key] = value;
  }
}

const BREAKPOINT_MIN_WIDTH: Record<string, string> = {
  sm: "40rem",
  md: "48rem",
  lg: "64rem",
  xl: "80rem",
};

/**
 * Aturan CSS ber-media-query untuk breakpoint selain `base`, di-scope lewat
 * atribut `data-node-id` (bukan kelas dinamis — SSR sekali per request,
 * cache-friendly, tak butuh runtime CSS-in-JS). Dikumpulkan per halaman dan
 * disuntik sebagai satu blok <style> (lihat page-template.tsx).
 */
export function compileResponsiveCss(nodeId: string, style: TreeNodeStyle | undefined): string {
  if (!style) return "";

  const rules: string[] = [];

  for (const bp of ["sm", "md", "lg", "xl"] as const) {
    const props = style[bp];
    if (!props) continue;

    const decls: string[] = [];
    const bg = resolveBackground(props.background);
    if (bg) decls.push(`background-color:${bg}`);
    const py = resolvePaddingY(props.paddingY);
    if (py) decls.push(`padding-block:${py}`);
    if (props.textAlign === "left" || props.textAlign === "center" || props.textAlign === "right") {
      decls.push(`text-align:${props.textAlign}`);
    }

    const kebab = (s: string) => s.replace(/[A-Z]/g, (m) => `-${m.toLowerCase()}`);
    for (const [prop, value] of Object.entries({ ...spacingDecls(props), ...visualDecls(props) })) {
      decls.push(`${kebab(prop)}:${value}`);
    }

    if (decls.length > 0) {
      rules.push(`@media (min-width:${BREAKPOINT_MIN_WIDTH[bp]}){[data-node-id="${nodeId}"]{${decls.join(";")}}}`);
    }
  }

  return rules.join("\n");
}
