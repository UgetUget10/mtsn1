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

/** Timpa `style` node ber-id tertentu (section atau column) — dipakai StylePanel. */
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

  return tree;
}
