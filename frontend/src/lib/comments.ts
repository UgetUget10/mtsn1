/**
 * Pengiriman komentar dari Client Component. Terpisah dari lib/api.ts dengan
 * alasan sama seperti lib/contact.ts (api.ts mengimpor next/root-params yang
 * tak boleh ter-bundle ke client).
 */
const BASE = process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8000/api/v1";

export type CommentPayload = {
  author_name: string;
  author_email?: string;
  author_url?: string;
  body: string;
  parent_id?: number;
  /** wp: "Notify me of follow-up comments by email". */
  subscribe?: boolean;
  /** Honeypot — harus tetap kosong. */
  website?: string;
};

export async function postComment(slug: string, payload: CommentPayload) {
  const res = await fetch(`${BASE}/posts/${encodeURIComponent(slug)}/comments`, {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    body: JSON.stringify(payload),
  });
  const body = await res.json().catch(() => ({}));
  if (!res.ok) {
    const msg =
      body?.message ??
      (body?.errors ? Object.values(body.errors).flat()[0] : null) ??
      "Gagal mengirim komentar";
    throw new Error(String(msg));
  }
  return body as { message: string; approved: boolean };
}
