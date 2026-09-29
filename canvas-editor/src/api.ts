import type { SectionNode, TreeResponse } from "./types";

const root = document.getElementById("canvas-root")!;

export const config = {
  treeUrl: root.dataset.treeUrl!,
  previewUrl: root.dataset.previewUrl!,
  csrfToken: root.dataset.csrfToken!,
};

export async function fetchTree(): Promise<TreeResponse> {
  const res = await fetch(config.treeUrl, {
    headers: { Accept: "application/json" },
    credentials: "same-origin",
  });
  if (!res.ok) throw new Error(`Gagal memuat kanvas (${res.status})`);
  return res.json();
}

export async function saveTree(tree: TreeResponse["tree"]): Promise<TreeResponse> {
  const res = await fetch(config.treeUrl, {
    method: "PUT",
    credentials: "same-origin",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
      "X-CSRF-TOKEN": config.csrfToken,
    },
    body: JSON.stringify({ tree }),
  });
  if (!res.ok) throw new Error(`Gagal menyimpan (${res.status})`);
  return res.json();
}

function withRequest(method: string) {
  return (url: string) =>
    fetch(url, {
      method,
      credentials: "same-origin",
      headers: { Accept: "application/json", "X-CSRF-TOKEN": config.csrfToken },
    });
}

/** treeUrl berakhiran ".../pages/{slug}/tree" — publish/discard berbagi prefiks yang sama. */
const canvasBaseUrl = config.treeUrl.replace(/\/tree$/, "");

/**
 * treeUrl berakhiran ".../admin/api/pages/{slug}/tree" ATAU
 * ".../admin/api/global-templates/{id}/tree" (Theme Builder header/footer) —
 * section-templates sejajar dengan keduanya di bawah admin/api/.
 */
const adminApiBaseUrl = config.treeUrl.replace(/\/(pages|global-templates)\/.+$/, "");

export async function publishTree(): Promise<{ published_at: string | null }> {
  const res = await withRequest("POST")(`${canvasBaseUrl}/publish`);
  if (!res.ok) throw new Error(`Gagal menerbitkan (${res.status})`);
  return res.json();
}

export async function discardDraft(): Promise<TreeResponse> {
  const res = await withRequest("DELETE")(`${canvasBaseUrl}/draft`);
  if (!res.ok) throw new Error(`Gagal membuang draf (${res.status})`);
  return res.json();
}

export type SectionTemplateSummary = { id: number; name: string; slug: string; widgetCount: number };

export async function listSectionTemplates(): Promise<SectionTemplateSummary[]> {
  const res = await withRequest("GET")(`${adminApiBaseUrl}/section-templates`);
  if (!res.ok) throw new Error(`Gagal memuat daftar template (${res.status})`);
  return res.json();
}

export async function saveSectionTemplate(name: string, section: SectionNode): Promise<SectionTemplateSummary> {
  const res = await fetch(`${adminApiBaseUrl}/section-templates`, {
    method: "POST",
    credentials: "same-origin",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
      "X-CSRF-TOKEN": config.csrfToken,
    },
    body: JSON.stringify({ name, section }),
  });
  if (!res.ok) throw new Error(`Gagal menyimpan template (${res.status})`);
  return res.json();
}

export async function fetchSectionTemplate(slug: string): Promise<{ section: SectionNode }> {
  const res = await withRequest("GET")(`${adminApiBaseUrl}/section-templates/${encodeURIComponent(slug)}`);
  if (!res.ok) throw new Error(`Gagal memuat template (${res.status})`);
  return res.json();
}
