/**
 * Buka post terlindungi kata sandi (wp: post_password). Terpisah dari lib/api.ts
 * karena dipanggil dari Client Component (post-password-form.tsx).
 */
const BASE = process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8000/api/v1";

/** Nama cookie tempat token unlock disimpan per-slug. */
export const unlockCookieName = (slug: string) => `mtsn1_unlock_${slug}`;

export async function unlockPost(slug: string, password: string) {
  const res = await fetch(`${BASE}/posts/${encodeURIComponent(slug)}/unlock`, {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    body: JSON.stringify({ password }),
  });
  const body = await res.json().catch(() => ({}));
  if (!res.ok) {
    throw new Error(body?.message ?? "Gagal membuka artikel.");
  }
  return body as { unlocked: true; token: string; expires_in: number };
}
