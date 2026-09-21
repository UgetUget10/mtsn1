import { draftMode, cookies } from "next/headers";
import { redirect } from "next/navigation";
import { NextRequest } from "next/server";
import { PREVIEW_TOKEN_COOKIE } from "@/lib/api";
import { defaultLocale, isLocale } from "@/lib/i18n";

const BASE = process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8000/api/v1";

/**
 * Titik masuk pratinjau — dibuka di tab baru oleh tombol "Pratinjau" di admin
 * Filament: `/api/preview?type=post&slug=...&token=...`.
 *
 * Alur (pola "separate draft endpoint" dokumen Next Draft Mode):
 *  1. Verifikasi token ke backend `/preview/{type}s/{slug}?token=` — kalau
 *     backend menolak (403/404), tolak juga di sini. Ini mencegah open
 *     redirect sekaligus memastikan token valid sebelum cookie dipasang.
 *  2. Simpan token di cookie httpOnly supaya lib/api.ts bisa memakainya saat
 *     mengambil ulang konten draft.
 *  3. draftMode().enable() lalu redirect ke URL kanonik konten (dari respons
 *     backend, bukan dari query) di dalam pohon locale.
 */
export async function GET(request: NextRequest) {
  const { searchParams } = request.nextUrl;
  const type = searchParams.get("type");
  const slug = searchParams.get("slug");
  const token = searchParams.get("token");
  const localeParam = searchParams.get("locale");
  const locale = localeParam && isLocale(localeParam) ? localeParam : defaultLocale;

  if ((type !== "post" && type !== "page") || !slug || !token) {
    return new Response("Parameter tidak lengkap", { status: 400 });
  }

  const verifyUrl = new URL(`${BASE}/preview/${type}s/${encodeURIComponent(slug)}`);
  verifyUrl.searchParams.set("token", token);

  const res = await fetch(verifyUrl, { cache: "no-store" });
  if (!res.ok) {
    return new Response("Token pratinjau tidak valid atau konten tidak ada", { status: 401 });
  }

  const body = (await res.json()) as { data?: { slug?: string } };
  const canonicalSlug = body.data?.slug ?? slug;

  const jar = await cookies();
  jar.set(PREVIEW_TOKEN_COOKIE, token, {
    httpOnly: true,
    sameSite: "lax",
    path: "/",
    maxAge: 60 * 60, // 1 jam — sesi pratinjau singkat
  });

  const draft = await draftMode();
  draft.enable();

  const path = type === "post" ? "berita" : "profil";
  redirect(`/${locale}/${path}/${canonicalSlug}`);
}
