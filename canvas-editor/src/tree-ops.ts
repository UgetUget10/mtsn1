import type { ColumnNode, NodeStyle, SectionNode, WidgetNode } from "./types";

function rid(prefix: string): string {
  return `${prefix}_${Math.random().toString(36).slice(2, 10)}`;
}

export function newColumn(): ColumnNode {
  return { id: rid("col"), type: "column", style: { base: { width: 12 } }, children: [] };
}

export function newSection(): SectionNode {
  return { id: rid("sec"), type: "section", children: [newColumn()] };
}

type Location =
  | { kind: "section"; sectionIndex: number }
  | { kind: "column"; sectionIndex: number; columnIndex: number }
  | { kind: "widget"; sectionIndex: number; columnIndex: number; widgetIndex: number };

/** Cari posisi node ber-id tertentu di tree — dipakai drag-drop lintas level. */
export function locate(tree: SectionNode[], id: string): Location | null {
  for (let s = 0; s < tree.length; s++) {
    if (tree[s].id === id) return { kind: "section", sectionIndex: s };
    for (let c = 0; c < tree[s].children.length; c++) {
      if (tree[s].children[c].id === id) return { kind: "column", sectionIndex: s, columnIndex: c };
      for (let w = 0; w < tree[s].children[c].children.length; w++) {
        if (tree[s].children[c].children[w].id === id) {
          return { kind: "widget", sectionIndex: s, columnIndex: c, widgetIndex: w };
        }
      }
    }
  }
  return null;
}

/** Pindahkan/reorder section (drag di antar section — reorder saja, tidak ada level di atasnya). */
export function moveSection(tree: SectionNode[], fromIndex: number, toIndex: number): SectionNode[] {
  const next = [...tree];
  const [moved] = next.splice(fromIndex, 1);
  next.splice(toIndex, 0, moved);
  return next;
}

/**
 * Pindahkan kolom ke section lain (atau reorder di section sama), disisipkan
 * SEBELUM `beforeColumnId` (atau di akhir bila null/tak ditemukan).
 */
export function moveColumn(
  tree: SectionNode[],
  columnId: string,
  targetSectionId: string,
  beforeColumnId: string | null,
): SectionNode[] {
  const from = locate(tree, columnId);
  if (!from || from.kind !== "column") return tree;

  const next = tree.map((s) => ({ ...s, children: [...s.children] }));
  const [moved] = next[from.sectionIndex].children.splice(from.columnIndex, 1);

  const targetSectionIndex = next.findIndex((s) => s.id === targetSectionId);
  if (targetSectionIndex === -1) return tree;

  const targetChildren = next[targetSectionIndex].children;
  const insertAt = beforeColumnId ? targetChildren.findIndex((c) => c.id === beforeColumnId) : -1;
  targetChildren.splice(insertAt === -1 ? targetChildren.length : insertAt, 0, moved);

  return next;
}

/**
 * Pindahkan widget ke kolom lain (atau reorder di kolom sama), disisipkan
 * SEBELUM `beforeWidgetId` (atau di akhir bila null/tak ditemukan).
 */
export function moveWidget(
  tree: SectionNode[],
  widgetId: string,
  targetColumnId: string,
  beforeWidgetId: string | null,
): SectionNode[] {
  const from = locate(tree, widgetId);
  if (!from || from.kind !== "widget") return tree;

  const next = tree.map((s) => ({
    ...s,
    children: s.children.map((c) => ({ ...c, children: [...c.children] })),
  }));
  const [moved] = next[from.sectionIndex].children[from.columnIndex].children.splice(from.widgetIndex, 1);

  for (const section of next) {
    const col = section.children.find((c) => c.id === targetColumnId);
    if (col) {
      const insertAt = beforeWidgetId ? col.children.findIndex((w) => w.id === beforeWidgetId) : -1;
      col.children.splice(insertAt === -1 ? col.children.length : insertAt, 0, moved as WidgetNode);
      return next;
    }
  }

  return tree;
}

/** Timpa `style` node ber-id tertentu (section, column, atau widget) — dipakai StylePanel. */
export function updateNodeStyle(tree: SectionNode[], nodeId: string, style: NodeStyle): SectionNode[] {
  const loc = locate(tree, nodeId);
  if (!loc) return tree;

  if (loc.kind === "section") {
    const next = [...tree];
    next[loc.sectionIndex] = { ...next[loc.sectionIndex], style };
    return next;
  }

  if (loc.kind === "column") {
    const next = tree.map((s) => ({ ...s, children: [...s.children] }));
    next[loc.sectionIndex].children[loc.columnIndex] = {
      ...next[loc.sectionIndex].children[loc.columnIndex],
      style,
    };
    return next;
  }

  const next = tree.map((s) => ({
    ...s,
    children: s.children.map((c) => ({ ...c, children: [...c.children] })),
  }));
  next[loc.sectionIndex].children[loc.columnIndex].children[loc.widgetIndex] = {
    ...next[loc.sectionIndex].children[loc.columnIndex].children[loc.widgetIndex],
    style,
  };
  return next;
}

/**
 * Bandingkan dua tree: true hanya bila STRUKTURnya identik (jumlah & urutan
 * section/kolom/widget, id-nya) — beda `style`/`data` di node manapun tetap
 * dianggap "struktur sama". Dipakai App.tsx untuk memutuskan apakah preview
 * boleh di-patch langsung (live style update, tanpa reload iframe) atau
 * harus reload penuh (perubahan struktural: tambah/hapus/pindah node).
 */
export function isSameStructure(a: SectionNode[], b: SectionNode[]): boolean {
  if (a.length !== b.length) return false;
  for (let s = 0; s < a.length; s++) {
    if (a[s].id !== b[s].id) return false;
    if (a[s].children.length !== b[s].children.length) return false;
    for (let c = 0; c < a[s].children.length; c++) {
      if (a[s].children[c].id !== b[s].children[c].id) return false;
      const aw = a[s].children[c].children;
      const bw = b[s].children[c].children;
      if (aw.length !== bw.length) return false;
      for (let w = 0; w < aw.length; w++) {
        if (aw[w].id !== bw[w].id) return false;
      }
    }
  }
  return true;
}

/**
 * Node id + style baru untuk tiap node yang `style`-nya berubah antara dua
 * tree BERSTRUKTUR SAMA (panggil isSameStructure() dulu). Dipakai untuk
 * membangun payload patch style-only ke preview.
 */
export function diffStyles(a: SectionNode[], b: SectionNode[]): { nodeId: string; style: NodeStyle | undefined }[] {
  const changes: { nodeId: string; style: NodeStyle | undefined }[] = [];

  for (let s = 0; s < a.length; s++) {
    if (JSON.stringify(a[s].style) !== JSON.stringify(b[s].style)) {
      changes.push({ nodeId: b[s].id, style: b[s].style });
    }
    for (let c = 0; c < a[s].children.length; c++) {
      if (JSON.stringify(a[s].children[c].style) !== JSON.stringify(b[s].children[c].style)) {
        changes.push({ nodeId: b[s].children[c].id, style: b[s].children[c].style });
      }
      for (let w = 0; w < a[s].children[c].children.length; w++) {
        if (JSON.stringify(a[s].children[c].children[w].style) !== JSON.stringify(b[s].children[c].children[w].style)) {
          changes.push({ nodeId: b[s].children[c].children[w].id, style: b[s].children[c].children[w].style });
        }
      }
    }
  }

  return changes;
}
