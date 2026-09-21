/**
 * Terpisah dari lib/api.ts dengan sengaja: fungsi ini dipanggil dari
 * Client Component (kontak/contact-form.tsx), dan api.ts mengimpor
 * `next/root-params` yang TIDAK BOLEH ikut ter-bundle ke kode client sama
 * sekali — bahkan lewat rantai impor (build gagal bila digabung).
 */
const BASE = process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8000/api/v1";

export async function sendContact(payload: Record<string, string>) {
  const res = await fetch(`${BASE}/contacts`, {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    body: JSON.stringify(payload),
  });
  const body = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(body?.message ?? "Gagal mengirim pesan");
  return body as { message: string };
}
