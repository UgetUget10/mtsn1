export const locales = ["id", "en"] as const;
export type Locale = (typeof locales)[number];
export const defaultLocale: Locale = "id";

export const isLocale = (value: string): value is Locale =>
  (locales as readonly string[]).includes(value);

const siteUrl = process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000";

/**
 * `alternates` untuk `generateMetadata` — canonical + hreflang id/en +
 * x-default. `path` adalah path TANPA prefix locale (mis. "/berita/slug").
 * Skema URL: id tanpa prefix, en dengan `/en` (lihat frontend/src/proxy.ts).
 */
export function localeAlternates(locale: string, path: string) {
  const clean = path === "/" ? "" : `/${path.replace(/^\/+/, "")}`;
  const idUrl = `${siteUrl}${clean || "/"}`;
  const enUrl = `${siteUrl}/en${clean}`;

  return {
    canonical: locale === "en" ? enUrl : idUrl,
    languages: {
      id: idUrl,
      en: enUrl,
      "x-default": idUrl,
    },
  };
}

/** Origin backend (tanpa /api/v1) — untuk berkas non-API seperti /feed. */
export const backendOrigin = (
  process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8000/api/v1"
).replace(/\/api\/v1\/?$/, "");

/**
 * URL RSS arsip ala WordPress (`/category/x/feed/`, `/tag/x/feed/`,
 * `/author/x/feed/`). Di sini backend menyajikannya lewat query di `/feed`.
 */
export function archiveFeedUrl(
  kind: "category" | "tag" | "author",
  slug: string,
) {
  return `${backendOrigin}/feed?${kind}=${encodeURIComponent(slug)}`;
}

/** `alternates.types` untuk `generateMetadata` — autodiscovery RSS arsip. */
export function feedAlternate(
  kind: "category" | "tag" | "author",
  slug: string,
  title: string,
) {
  return {
    types: {
      "application/rss+xml": [{ url: archiveFeedUrl(kind, slug), title }],
    },
  };
}
