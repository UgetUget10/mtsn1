import "server-only";
import { locale } from "next/root-params";
import { defaultLocale, isLocale, type Locale } from "@/lib/i18n";
import id from "./id.json";
import en from "./en.json";

/**
 * Kamus string UI (chrome situs). Konten CMS diterjemahkan di backend
 * (spatie/laravel-translatable); ini khusus teks statis frontend seperti
 * label nav, tombol umum, halaman error.
 *
 * Bentuk kedua kamus dijaga identik oleh tipe `Dictionary` (diturunkan dari
 * id.json) — menambah key di id.json tapi lupa di en.json akan jadi error
 * TypeScript di sini.
 */
export type Dictionary = typeof id;

const dictionaries: Record<Locale, Dictionary> = {
  id,
  en: en as Dictionary,
};

/**
 * Ambil kamus untuk locale route aktif (`[locale]` root param). Dipanggil
 * tanpa argumen dari Server Component mana pun — locale diresolusi internal.
 * Di luar pohon `[locale]` (mis. sitemap.ts) jatuh ke locale default.
 */
export async function getDictionary(): Promise<Dictionary> {
  const loc = await locale().catch(() => undefined);
  const key = loc && isLocale(loc) ? loc : defaultLocale;

  return dictionaries[key];
}
