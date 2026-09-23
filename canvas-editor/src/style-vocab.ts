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
