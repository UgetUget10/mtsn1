"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";
import { locales, type Locale } from "@/lib/i18n";

/**
 * Peralih bahasa. Skema URL di address bar: id (default) tanpa prefix (mis.
 * /berita), en dengan prefix eksplisit (mis. /en/berita) — lihat
 * frontend/src/proxy.ts untuk rewrite-nya.
 *
 * PENTING: `usePathname()` di App Router mengembalikan path HASIL REWRITE
 * proxy — untuk locale id itu berarti /id/... (bukan path tanpa prefix apa
 * adanya di address bar). Jadi untuk mendapatkan path "telanjang" (tanpa
 * prefix locale sama sekali), kita harus melepas prefix /id ATAUPUN /en,
 * baru pasang balik prefix sesuai locale tujuan.
 */
const labels: Record<Locale, string> = { id: "ID", en: "EN" };

function stripLocalePrefix(pathname: string): string {
  for (const locale of locales) {
    if (pathname === `/${locale}`) return "/";
    if (pathname.startsWith(`/${locale}/`)) return pathname.slice(locale.length + 1);
  }
  return pathname;
}

export function LanguageSwitcher({ className = "" }: { className?: string }) {
  const pathname = usePathname();

  const active: Locale = pathname === "/en" || pathname.startsWith("/en/") ? "en" : "id";
  const bare = stripLocalePrefix(pathname);

  const hrefFor = (locale: Locale) => (locale === "id" ? bare : `/en${bare === "/" ? "" : bare}`);

  return (
    <div className={`inline-flex items-center gap-0.5 rounded-lg border border-border bg-surface p-0.5 ${className}`}>
      {locales.map((locale) => (
        <Link
          key={locale}
          href={hrefFor(locale)}
          aria-current={active === locale ? "true" : undefined}
          className={`rounded-md px-2 py-1 text-xs font-bold uppercase tracking-wide transition ${
            active === locale
              ? "bg-brand text-on-brand"
              : "text-ink-muted hover:bg-surface-muted hover:text-foreground"
          }`}
        >
          {labels[locale]}
        </Link>
      ))}
    </div>
  );
}
