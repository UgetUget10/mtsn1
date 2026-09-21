import type { Settings } from "@/lib/types";

export type HomepageSectionConfig = { id: string; label: string; is_visible: boolean };

/**
 * Baca urutan & tampil/sembunyi section beranda dari Setting `homepage_sections`
 * (dikelola di admin lewat App\Filament\Pages\HomepageBuilder). Bila belum
 * pernah disimpan (JSON kosong/invalid), semua section dianggap tampil —
 * beranda tetap identik seperti sebelum fitur ini ada.
 */
export function parseHomepageSections(settings: Settings): HomepageSectionConfig[] {
  const raw = settings.homepage_sections;
  if (!raw) return [];

  try {
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
}

/** true bila section `id` boleh tampil — default tampil jika belum dikonfigurasi. */
export function isSectionVisible(sections: HomepageSectionConfig[], id: string): boolean {
  const found = sections.find((s) => s.id === id);
  return found ? found.is_visible : true;
}

/**
 * Urutan final id section yang tampil di beranda:
 * - jika Setting sudah dikonfigurasi → pakai urutan dari admin, buang yang
 *   is_visible=false, dan tempel id fallback yang belum ada di config
 *   (mis. section baru setelah upgrade) di akhir.
 * - jika belum pernah dikonfigurasi → pakai `fallbackOrder` apa adanya.
 */
export function orderedVisibleSectionIds(
  sections: HomepageSectionConfig[],
  fallbackOrder: string[],
): string[] {
  if (sections.length === 0) return [...fallbackOrder];

  const configured = sections.filter((s) => s.is_visible).map((s) => s.id);
  const known = new Set(sections.map((s) => s.id));
  const missing = fallbackOrder.filter((id) => !known.has(id));

  return [...configured, ...missing];
}
