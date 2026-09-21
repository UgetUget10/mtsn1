import { NextResponse } from "next/server";
import type { NextRequest } from "next/server";
import { defaultLocale, isLocale, locales } from "@/lib/i18n";

/**
 * Skema URL: locale default (id) TANPA prefix (mis. /berita) demi kompatibel
 * dengan seluruh URL yang sudah ada; locale lain pakai prefix eksplisit
 * (mis. /en/berita). Proxy ini menulis ulang path tanpa prefix menjadi
 * /id/... secara internal (address bar tetap /berita) agar App Router bisa
 * merutekan lewat struktur folder app/[locale]/....
 *
 * Selain itu, proxy inilah yang menangani REDIRECT 301 untuk slug lama
 * (wp: _wp_old_slug). `redirect()`/`permanentRedirect()` dari Server Component
 * TIDAK menghasilkan respons HTTP redirect di Next 16 bila request sudah
 * di-rewrite oleh proxy — jadi cek tabel redirect backend di sini, sebelum
 * rewrite, dan pancarkan `NextResponse.redirect()` yang sungguhan.
 */

const BACKEND =
  process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8000/api/v1";

// Prefiks path konten yang slug-nya bisa berubah (punya baris redirect backend).
const REDIRECTABLE = ["/berita/", "/profil/"];

/** Tanya backend apakah `path` (tanpa prefix locale) diarahkan ke path lain. */
async function lookupRedirect(
  path: string,
): Promise<{ to: string; status: number } | null> {
  try {
    const res = await fetch(
      `${BACKEND}/resolve?path=${encodeURIComponent(path)}`,
      { next: { revalidate: 300, tags: ["redirects"] } },
    );
    if (!res.ok) return null;
    return (await res.json()) as { to: string; status: number };
  } catch {
    return null;
  }
}

/** Apakah bulan arsip itu punya berita terbit? (backend membalas 404 bila tidak) */
async function archiveMonthExists(
  year: number,
  month: number,
): Promise<boolean> {
  return resourceExists(`/archives/${year}/${month}`, ["archives", "posts"]);
}

/**
 * Peta URL konten → endpoint backend yang memastikan sumber daya itu ada.
 * Dipakai untuk memancarkan 404 SUNGGUHAN (lihat catatan soft-404 di bawah).
 * Urutan penting: pola yang lebih spesifik harus lebih dulu, karena
 * `/berita/tag/x` juga cocok dengan pola `/berita/{slug}`.
 */
/**
 * Path yang PUNYA rute statis sendiri di app router, sehingga tidak boleh
 * divalidasi ke backend (backend akan membalas 404 padahal halamannya sah).
 * Sinkronkan bila menambah folder statis di bawah /berita atau /profil.
 */
const STATIC_ROUTES = new Set(["/profil/sambutan"]);

const EXISTENCE_CHECKS: {
  pattern: RegExp;
  endpoint: (slug: string) => string;
  tags: string[];
}[] = [
  {
    pattern: /^\/berita\/tag\/([^/]+)\/?$/,
    endpoint: (s) => `/tags/${s}`,
    tags: ["tags", "posts"],
  },
  {
    pattern: /^\/berita\/kategori\/([^/]+)\/?$/,
    endpoint: (s) => `/categories/${s}`,
    tags: ["posts"],
  },
  {
    pattern: /^\/penulis\/([^/]+)\/?$/,
    endpoint: (s) => `/authors/${s}`,
    tags: ["authors", "posts"],
  },
  {
    pattern: /^\/berita\/([^/]+)\/?$/,
    endpoint: (s) => `/posts/${s}`,
    tags: ["posts"],
  },
  {
    pattern: /^\/profil\/([^/]+)\/?$/,
    endpoint: (s) => `/pages/${s}`,
    tags: ["pages"],
  },
];

/** GET ke endpoint backend; `false` HANYA bila backend menjawab 404. */
async function resourceExists(
  endpoint: string,
  tags: string[],
): Promise<boolean> {
  try {
    const res = await fetch(`${BACKEND}${endpoint}`, {
      next: { revalidate: 600, tags },
    });
    // 404 = benar-benar tak ada. Status lain (500, 503, …) jangan dijadikan
    // alasan menutup halaman — biarkan rute yang memutuskan.
    return res.status !== 404;
  } catch {
    // Backend tak terjangkau — jangan menutup halaman yang mungkin sah.
    return true;
  }
}

/**
 * Lapor satu 404 ke backend (wp: tab "404s" plugin Redirection). Fire-and-forget
 * — jangan pernah menahan respons untuk ini, dan telan semua error. Editor lalu
 * bisa satu klik "Buatkan pengalihan" di panel (SEO & Tautan → Log 404).
 */
function report404(request: NextRequest, barePath: string): void {
  try {
    void fetch(`${BACKEND}/log-404`, {
      method: "POST",
      headers: { "content-type": "application/json" },
      body: JSON.stringify({
        path: barePath,
        referrer: request.headers.get("referer") ?? undefined,
      }),
      // Sekali jalan; jangan cache, jangan ikut ISR.
      cache: "no-store",
    }).catch(() => {});
  } catch {
    /* tak boleh menggagalkan respons 404 hanya karena logging gagal */
  }
}

export async function proxy(request: NextRequest) {
  const { pathname } = request.nextUrl;

  // Lewati aset Next.js, API route (revalidate), dan file statis (punya titik,
  // mis. favicon.ico, manifest.webmanifest).
  if (
    pathname.startsWith("/_next") ||
    pathname.startsWith("/api") ||
    pathname.startsWith("/revalidate") ||
    pathname === "/robots.txt" ||
    pathname === "/sitemap.xml" ||
    /\.[^/]+$/.test(pathname)
  ) {
    return NextResponse.next();
  }

  const firstSegment = pathname.split("/")[1];
  const hasLocalePrefix = firstSegment && isLocale(firstSegment);

  // Path tanpa prefix locale (mis. "/berita/judul") untuk pencocokan redirect.
  const barePath = hasLocalePrefix
    ? "/" + pathname.split("/").slice(2).join("/")
    : pathname;
  const localePrefix =
    hasLocalePrefix && firstSegment !== defaultLocale ? `/${firstSegment}` : "";

  // Arsip tanggal: tolak tahun/bulan yang tak masuk akal di sini, sebelum
  // rute dirender. `notFound()` di dalam Server Component memang menampilkan
  // halaman 404 tapi TETAP berstatus HTTP 200 bila ada `loading.tsx` sibling
  // (bug hulu Next.js vercel/next.js#76474 & #93239) — dan
  // src/app/[locale]/berita/loading.tsx memenuhi syarat itu. Menjawab di sini
  // memastikan mesin pencari menerima 404 sungguhan, bukan soft-404.
  const archiveMatch = barePath.match(/^\/berita\/arsip\/(\d+)\/(\d+)\/?$/);
  if (archiveMatch) {
    const year = Number(archiveMatch[1]);
    const month = Number(archiveMatch[2]);
    if (year < 1970 || year > 2200 || month < 1 || month > 12) {
      return new NextResponse(null, { status: 404 });
    }
    // Bulan valid tapi tanpa berita terbit juga harus 404 (wp: arsip kosong
    // tidak dilayani). Endpoint ini di-cache 10 menit, jadi murah.
    if (!(await archiveMonthExists(year, month))) {
      return new NextResponse(null, { status: 404 });
    }
  }

  // Cek redirect 301 untuk slug lama (wp: perubahan slug otomatis → 301).
  if (REDIRECTABLE.some((p) => barePath.startsWith(p))) {
    const hit = await lookupRedirect(barePath);
    if (hit && hit.to !== barePath) {
      const dest = request.nextUrl.clone();
      dest.pathname = `${localePrefix}${hit.to}`;
      return NextResponse.redirect(dest, hit.status === 301 ? 308 : 307);
    }
  }

  // Slug yang tidak ada → 404 SUNGGUHAN. Dijalankan SETELAH cek redirect di
  // atas supaya slug lama tetap dapat 301, bukan 404.
  //
  // Kenapa di sini dan bukan cukup `notFound()` di halaman: bila sebuah rute
  // punya `loading.tsx` sibling, `notFound()` di Server Component memang
  // merender halaman 404 tapi status HTTP-nya tetap 200 (soft-404) — bug hulu
  // Next.js vercel/next.js#76474 & #93239. `src/app/[locale]/berita/loading.tsx`
  // memicunya. Tanpa guard ini mesin pencari mengindeks tiap URL ngawur.
  // Mode pratinjau (Draft Mode) MELEWATI cek ini: URL draft memang belum
  // terbit, jadi endpoint publiknya membalas 404 — editor tetap harus bisa
  // membukanya lewat /preview/... (lihat lib/api.ts previewContext()).
  const isPreview = request.cookies.has("mtsn1_preview_token");

  if (!isPreview && !STATIC_ROUTES.has(barePath.replace(/\/$/, ""))) {
    for (const check of EXISTENCE_CHECKS) {
      const m = barePath.match(check.pattern);
      if (!m) continue;
      if (!(await resourceExists(check.endpoint(m[1]), check.tags))) {
        // Slug konten yang benar-benar tak ada — kandidat redirect yang
        // berguna. Catat ke backend (wp: tab "404s" Redirection).
        report404(request, barePath);
        return new NextResponse(null, { status: 404 });
      }
      break;
    }
  }

  if (hasLocalePrefix) {
    // Prefix locale eksplisit sudah ada (mis. /en/berita) — termasuk
    // /id/berita bila seseorang mengetiknya manual, biarkan lolos apa
    // adanya, App Router yang menangani.
    return NextResponse.next();
  }

  // Tanpa prefix locale — rewrite internal ke /id/... (locale default).
  const url = request.nextUrl.clone();
  url.pathname = `/${defaultLocale}${pathname}`;

  return NextResponse.rewrite(url);
}

export const config = {
  matcher: [
    // Semua path kecuali aset internal Next.js dan file statis di /public.
    "/((?!api|_next/static|_next/image|favicon.ico|icon.svg|manifest.webmanifest).*)",
  ],
};

// Ekspor daftar locale agar mudah diimpor bila proxy lain butuh referensinya.
export { locales };
