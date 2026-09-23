/** Cermin dari backend/app/Support/Blocks/TreeNormalizer.php + BlockTypes.php. */

export type NodeStyle = Record<string, Record<string, unknown>>;

export type WidgetNode = {
  id: string;
  type: string; // salah satu dari BlockTypes widget (hero, card_grid, dst)
  data: Record<string, unknown>;
  style?: NodeStyle;
};

export type ColumnNode = {
  id: string;
  type: "column";
  style?: NodeStyle;
  children: WidgetNode[];
};

export type SectionNode = {
  id: string;
  type: "section";
  style?: NodeStyle;
  children: ColumnNode[];
};

export type TreeResponse = {
  schema: number;
  tree: SectionNode[];
};

/** Label Bahasa Indonesia — cermin PageForm::builder() di backend. */
export const WIDGET_LABELS: Record<string, string> = {
  hero: "Hero",
  rich_text: "Teks Bebas",
  card_grid: "Grid Kartu",
  accordion: "Akordion / FAQ",
  cta: "CTA (Ajakan Bertindak)",
  file_list: "Daftar Berkas",
  gallery_block: "Galeri",
  stats: "Statistik",
  hub_grid: "Grid Tautan (Hub)",
  table: "Tabel",
  steps: "Langkah Bernomor",
  quote: "Kutipan",
  link_cards: "Kartu Tautan",
  checklist: "Daftar Centang",
  icon_list: "Daftar Berikon",
  timeline: "Linimasa",
  reusable: "Blok Dipakai Ulang",
};
