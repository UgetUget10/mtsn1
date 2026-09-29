/**
 * Cermin backend/app/Support/Blocks/StyleResolver.php — kosakata tertutup
 * untuk kontrol panel styling. Backend tetap sumber kebenaran validasi;
 * daftar di sini HANYA untuk membangun UI pilihan (dropdown/preset).
 */

export const PADDING_Y_OPTIONS: { value: string; label: string }[] = [
  { value: "none", label: "Tanpa jarak" },
  { value: "sm", label: "Kecil" },
  { value: "md", label: "Sedang" },
  { value: "lg", label: "Besar" },
  { value: "xl", label: "Sangat besar" },
];

export const BACKGROUND_PRESETS: { value: string; label: string; swatch: string }[] = [
  { value: "transparent", label: "Tanpa warna", swatch: "transparent" },
  { value: "surface", label: "Putih (Surface)", swatch: "#ffffff" },
  { value: "surface-muted", label: "Abu Muda", swatch: "#f3f6f4" },
  { value: "brand-light", label: "Hijau Muda (Brand)", swatch: "#e6f4ee" },
  { value: "accent-soft", label: "Krem (Accent)", swatch: "#fbf0dd" },
  { value: "success-soft", label: "Hijau Sukses", swatch: "#e7f6ec" },
  { value: "info-soft", label: "Biru Info", swatch: "#e8effd" },
];

export const TEXT_ALIGN_OPTIONS: { value: string; label: string }[] = [
  { value: "left", label: "Kiri" },
  { value: "center", label: "Tengah" },
  { value: "right", label: "Kanan" },
];

export const HEX_COLOR_RE = /^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/;

export const SPACING_OPTIONS: { value: string; label: string }[] = [
  { value: "none", label: "0" },
  { value: "xs", label: "XS" },
  { value: "sm", label: "S" },
  { value: "md", label: "M" },
  { value: "lg", label: "L" },
  { value: "xl", label: "XL" },
  { value: "2xl", label: "2XL" },
];

export const SPACING_SIDES: { key: "Top" | "Right" | "Bottom" | "Left"; label: string }[] = [
  { key: "Top", label: "Atas" },
  { key: "Right", label: "Kanan" },
  { key: "Bottom", label: "Bawah" },
  { key: "Left", label: "Kiri" },
];

export const FONT_SIZE_OPTIONS: { value: string; label: string }[] = [
  { value: "xs", label: "XS" },
  { value: "sm", label: "Kecil" },
  { value: "base", label: "Normal" },
  { value: "lg", label: "Besar" },
  { value: "xl", label: "XL" },
  { value: "2xl", label: "2XL" },
  { value: "3xl", label: "3XL" },
  { value: "4xl", label: "4XL" },
];

export const FONT_WEIGHT_OPTIONS: { value: string; label: string }[] = [
  { value: "normal", label: "Normal" },
  { value: "medium", label: "Medium" },
  { value: "semibold", label: "Semibold" },
  { value: "bold", label: "Bold" },
];

export const BORDER_STYLE_OPTIONS: { value: string; label: string }[] = [
  { value: "none", label: "Tanpa garis" },
  { value: "solid", label: "Solid" },
  { value: "dashed", label: "Putus-putus" },
  { value: "dotted", label: "Titik-titik" },
];

export const BORDER_WIDTH_OPTIONS: { value: string; label: string }[] = [
  { value: "none", label: "0" },
  { value: "thin", label: "Tipis" },
  { value: "medium", label: "Sedang" },
  { value: "thick", label: "Tebal" },
];

export const RADIUS_OPTIONS: { value: string; label: string }[] = [
  { value: "none", label: "Kotak" },
  { value: "sm", label: "Kecil" },
  { value: "md", label: "Sedang" },
  { value: "lg", label: "Besar" },
  { value: "full", label: "Bulat penuh" },
];

export const SHADOW_OPTIONS: { value: string; label: string }[] = [
  { value: "none", label: "Tanpa bayangan" },
  { value: "sm", label: "Tipis" },
  { value: "md", label: "Sedang" },
  { value: "lg", label: "Kuat" },
];
