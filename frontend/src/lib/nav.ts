import type { ApiMenu, ApiMenuItem } from "@/lib/types";

export type NavLeaf = { label: string; href: string; desc?: string };
export type NavSection = { title: string; items: NavLeaf[] };
export type NavGroup = {
  label: string;
  base: string;
  icon: string;
  /** Semua tautan (gabungan section) — dipakai footer, peta situs, roving focus. */
  items: NavLeaf[];
  /** Bila diisi, mega-panel dibagi per kolom berjudul. */
  sections?: NavSection[];
  /** Kartu aksen di dalam mega-panel (mis. CTA PPDB). */
  feature?: { title: string; text: string; href: string; cta: string };
};
export type NavEntry = NavLeaf | NavGroup;

export const isNavGroup = (e: NavEntry): e is NavGroup => "items" in e;

/** Ikon garis 24×24 (satu path) — dipakai di mega-panel & drawer. */
export const navIcons = {
  profil: "M12 3l8 4v5c0 5-3.4 8.5-8 9-4.6-.5-8-4-8-9V7l8-4z",
  akademik: "M12 3 2 8l10 5 10-5-10-5zM4 10v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6",
  informasi: "M4 4h16v14H5.2L4 19.2zM8 9h8M8 13h5",
  layanan: "M3 21h18M6 21V7l6-4 6 4v14M10 21v-5h4v5",
  zi: "M12 3l8 4v5c0 5-3.4 8.5-8 9-4.6-.5-8-4-8-9V7l8-4zM9 12l2 2 4-4",
  ppdb: "M12 2a5 5 0 0 1 5 5v3H7V7a5 5 0 0 1 5-5zM5 10h14v10a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2z",
  kontak: "M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.7A2 2 0 0 1 4.1 2H7a2 2 0 0 1 2 1.7c.1 1.2.4 2.4.8 3.5a2 2 0 0 1-.5 2.1L8 10.6a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c1.1.4 2.3.7 3.5.8A2 2 0 0 1 22 16.9z",
} as const;

const toLeaf = (item: ApiMenuItem): NavLeaf | null =>
  item.href ? { label: item.label, href: item.href } : null;

/**
 * "base" dipakai untuk deteksi item aktif di header (highlight saat path
 * cocok) — turunkan dari tautan pertama yang ditemukan dalam grup, sesuai
 * pola lama nav.ts yang selalu memakai path section index sebagai base.
 */
function deriveBase(leaves: NavLeaf[]): string {
  const first = leaves[0]?.href;
  if (!first) return "/";
  return first.split("/").slice(0, 2).join("/") || "/";
}

function toGroup(item: ApiMenuItem): NavGroup {
  const hasSubSections = item.children.some((c) => c.type === "section");

  const sections: NavSection[] | undefined = hasSubSections
    ? item.children
        .filter((c) => c.type === "section")
        .map((sec) => ({
          title: sec.label,
          items: sec.children.map(toLeaf).filter((l): l is NavLeaf => l !== null),
        }))
    : undefined;

  const items: NavLeaf[] = hasSubSections
    ? (sections ?? []).flatMap((s) => s.items)
    : item.children.map(toLeaf).filter((l): l is NavLeaf => l !== null);

  return {
    label: item.label,
    base: deriveBase(items),
    icon: item.icon ?? navIcons.informasi,
    items,
    sections,
    feature: item.feature
      ? {
          title: item.feature.title,
          text: item.feature.text ?? "",
          href: items[0]?.href ?? "/",
          cta: item.feature.cta ?? "Selengkapnya",
        }
      : undefined,
  };
}

/**
 * Ubah pohon menu dari API (lihat App\Http\Controllers\Api\MenuController)
 * menjadi struktur NavEntry yang dipakai header, footer, dan peta situs.
 * Item level teratas bertipe "section" (punya anak) jadi NavGroup dengan
 * mega-panel; bertipe "custom_url"/"page" tanpa anak jadi NavLeaf langsung.
 */
export function buildNav(menu: ApiMenu): NavEntry[] {
  return menu.items
    .map((item): NavEntry | null => {
      if (item.children.length > 0) return toGroup(item);

      return toLeaf(item);
    })
    .filter((e): e is NavEntry => e !== null);
}
