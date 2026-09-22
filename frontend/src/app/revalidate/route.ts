import { revalidatePath, revalidateTag } from "next/cache";
import { NextRequest, NextResponse } from "next/server";
import { locales } from "@/lib/i18n";

/**
 * Dipanggil backend Laravel setiap konten disimpan/dihapus
 * (App\Models\Concerns\TriggersFrontendRevalidation).
 *
 * Dua bentuk didukung:
 *  - JSON body { secret, tags[], paths[] } → segarkan hanya yang terpengaruh
 *    (mirip purge per-URL WordPress). Ini jalur normal.
 *  - Tanpa body / hanya ?secret= → fallback lama: segarkan seluruh situs.
 *
 * `paths` dari backend adalah path TANPA prefix locale (mis. "/berita/slug").
 * Karena locale default (id) juga tanpa prefix tapi App Router merutekan lewat
 * /[locale], setiap path disegarkan untuk kedua pohon locale.
 */
export async function POST(request: NextRequest) {
  const secretExpected = process.env.REVALIDATE_SECRET;

  let body: { secret?: string; tags?: unknown; paths?: unknown } = {};
  try {
    body = await request.json();
  } catch {
    // body kosong → cek query ?secret= (fallback lama)
  }

  const secret = body.secret ?? request.nextUrl.searchParams.get("secret");
  if (!secretExpected || secret !== secretExpected) {
    return NextResponse.json({ message: "Invalid secret" }, { status: 401 });
  }

  const tags = Array.isArray(body.tags) ? body.tags.filter((t): t is string => typeof t === "string") : [];
  const paths = Array.isArray(body.paths)
    ? body.paths.filter((p): p is string => typeof p === "string")
    : [];

  // Tidak ada instruksi spesifik → fallback: segarkan seluruh situs.
  if (tags.length === 0 && paths.length === 0) {
    for (const locale of locales) revalidatePath(`/${locale}`, "layout");
    return NextResponse.json({ revalidated: true, mode: "full", now: Date.now() });
  }

  // { expire: 0 } BUKAN "max" — ini dipanggil dari webhook Laravel (bukan
  // Server Action), jadi updateTag() tidak tersedia (lihat docs updateTag).
  // Profile "max" = stale-while-revalidate SATU TAHUN: request berikutnya
  // tetap disajikan data LAMA sambil regenerasi jalan di background — utk
  // tag seperti "settings" yang TIDAK punya `paths` pendamping (tak ada
  // revalidatePath yang memaksa satu halaman fresh), traffic normal nyaris
  // tidak pernah memicu regenerasi itu, jadi perubahan di panel admin
  // terasa "tidak pernah muncul". { expire: 0 } membuat tag kedaluwarsa
  // SEKARANG, sehingga request berikutnya blocking-fetch data baru.
  for (const tag of tags) revalidateTag(tag, { expire: 0 });

  for (const path of paths) {
    const clean = path.startsWith("/") ? path : `/${path}`;
    for (const locale of locales) {
      // "/" → "/id" ; "/berita/slug" → "/id/berita/slug"
      revalidatePath(clean === "/" ? `/${locale}` : `/${locale}${clean}`);
    }
  }

  return NextResponse.json({
    revalidated: true,
    mode: "targeted",
    tags,
    paths,
    now: Date.now(),
  });
}

export async function GET() {
  return NextResponse.json({ ok: true, hint: "POST { secret, tags[], paths[] }" });
}
