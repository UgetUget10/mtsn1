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

  return out;
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

    if (decls.length > 0) {
      rules.push(`@media (min-width:${BREAKPOINT_MIN_WIDTH[bp]}){[data-node-id="${nodeId}"]{${decls.join(";")}}}`);
    }
  }

  return rules.join("\n");
}
