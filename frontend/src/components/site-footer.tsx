import Image from "@/components/media-image";
import Link from "next/link";
import type { ApiMenu, Block, Settings } from "@/lib/types";
import type { Dictionary } from "@/dictionaries";
import { buildNav, isNavGroup } from "@/lib/nav";
import { MotifStar } from "@/components/ornament";
import { InstallPWA } from "@/components/features-more";
import { BlockRenderer } from "@/components/blocks/block-renderer";

export function SiteFooter({
  settings,
  menu,
  dict,
  widgets,
}: {
  settings: Settings;
  menu: ApiMenu;
  dict: Dictionary;
  /** Isi zona widget "footer" (ala WordPress Appearance > Widgets) — diisi admin, kosong bila belum diatur. */
  widgets?: Block[];
}) {
  const year = new Date().getFullYear();
  const name = settings.site_name ?? "MTsN 1 Kota Malang";

  // Kolom footer memakai struktur navigasi yang sama dengan header (satu sumber).
  const cols = buildNav(menu)
    .filter(isNavGroup)
    .filter((g) => g.label !== "Zona Integritas")
    .map((g) => ({
      title: g.label,
      icon: g.icon,
      links: g.items.slice(0, 7).map((it) => [it.label, it.href] as [string, string]),
    }));

  const socialIcons: Record<string, string> = {
    Facebook: "M13.5 21v-8h2.7l.4-3.1h-3.1V7.9c0-.9.25-1.5 1.5-1.5h1.7V3.6C17.5 3.55 16.5 3.5 15.4 3.5c-2.3 0-3.9 1.4-3.9 4v2.9H8.8V14h2.7v7z",
    Instagram: "M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm5 5a5 5 0 1 0 0 10 5 5 0 0 0 0-10zm5.5-.5a1 1 0 1 0 0 2 1 1 0 0 0 0-2z",
    YouTube: "M22 8s-.2-1.5-.8-2.1c-.8-.8-1.6-.8-2-.9C16 4.7 12 4.7 12 4.7s-4 0-7.2.3c-.4.1-1.2.1-2 .9C2.2 6.5 2 8 2 8s-.2 1.7-.2 3.5v1c0 1.8.2 3.5.2 3.5s.2 1.5.8 2.1c.8.8 1.8.8 2.3.9 1.7.2 7 .3 7 .3s4 0 7.2-.3c.4-.1 1.2-.1 2-.9.6-.6.8-2.1.8-2.1s.2-1.7.2-3.5v-1c0-1.8-.2-3.5-.2-3.5zM10 14.5v-5l4.5 2.5-4.5 2.5z",
    "Twitter/X": "M4 4l7 9.5L4 20h2l6-6.5L17 20h3l-7.5-10L20 4h-2l-5.5 6L8 4z",
  };

  const socials = [
    ["Facebook", settings.facebook],
    ["Instagram", settings.instagram],
    ["YouTube", settings.youtube],
    ["Twitter/X", settings.twitter],
  ].filter(([, url]) => url) as [string, string][];

  return (
    <footer className="relative overflow-hidden bg-surface-muted">
      <div aria-hidden className="footer-bar h-1 w-full" />
      <MotifStar className="-bottom-20 -right-16 md:-right-4" />
      <div className="section-y relative mx-auto grid max-w-7xl gap-10 px-4 sm:px-6 lg:px-8">
        <div className="max-w-md">
          <div className="flex items-center gap-3">
            <span className="grid h-10 w-10 place-items-center overflow-hidden rounded-xl bg-brand text-on-brand">
              {settings.logo ? (
                <Image src={settings.logo} alt="" width={40} height={40} className="h-full w-full object-cover" />
              ) : (
                <span className="text-sm font-black">M1</span>
              )}
            </span>
            <span className="text-sm font-extrabold text-foreground">{name}</span>
          </div>
          <p className="mt-4 text-sm leading-relaxed text-ink-muted">
            {settings.site_tagline ?? dict.footer.tagline}
          </p>
          {socials.length > 0 && (
            <div className="mt-5 flex flex-wrap gap-2">
              {socials.map(([label, url]) => (
                <a
                  key={label}
                  href={url}
                  target="_blank"
                  rel="noreferrer"
                  aria-label={label}
                  className="social-ico grid h-9 w-9 place-items-center rounded-lg border border-border text-ink-muted hover:border-brand hover:text-brand"
                >
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                    <path d={socialIcons[label]} />
                  </svg>
                </a>
              ))}
            </div>
          )}
        </div>

        <div className="grid gap-8 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6">
        {cols.map((col) => (
          <div key={col.title}>
            <p className="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-foreground">
              <span className="grid h-6 w-6 place-items-center rounded-md bg-brand-light text-brand-dark">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round"><path d={col.icon} /></svg>
              </span>
              {col.title}
            </p>
            <ul className="mt-4 space-y-2 text-sm">
              {col.links.map(([label, href]) => {
                const ext = href.startsWith("http");
                const cls = "group/fl inline-flex items-center gap-1.5 text-ink-soft transition hover:text-brand";
                const inner = (
                  <>
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.8" strokeLinecap="round" strokeLinejoin="round" className="-ml-3 shrink-0 opacity-0 transition-all group-hover/fl:ml-0 group-hover/fl:opacity-100"><path d="M9 6l6 6-6 6" /></svg>
                    {label}
                    {ext && <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.8" strokeLinecap="round" strokeLinejoin="round" className="opacity-50"><path d="M7 17 17 7M8 7h9v9" /></svg>}
                  </>
                );
                return (
                  <li key={`${label}-${href}`}>
                    {ext ? (
                      <a href={href} target="_blank" rel="noreferrer" className={cls}>{inner}</a>
                    ) : (
                      <Link href={href} className={cls}>{inner}</Link>
                    )}
                  </li>
                );
              })}
            </ul>
          </div>
        ))}

        <div>
          <p className="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-foreground">
            <span className="grid h-6 w-6 place-items-center rounded-md bg-brand-light text-brand-dark">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.7A2 2 0 0 1 4.1 2H7a2 2 0 0 1 2 1.7c.1 1.2.4 2.4.8 3.5a2 2 0 0 1-.5 2.1L8 10.6a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c1.1.4 2.3.7 3.5.8A2 2 0 0 1 22 16.9z" /></svg>
            </span>
            {dict.footer.contact}
          </p>
          <address className="mt-4 space-y-1.5 text-sm not-italic text-ink-soft">
            {settings.address && <p>{settings.address}</p>}
            {settings.phone && <p>{settings.phone}</p>}
            {settings.email && <p>{settings.email}</p>}
          </address>
          <div className="mt-4 flex flex-wrap gap-2">
            {settings.ppdb_url && (
              <a
                href={settings.ppdb_url}
                target="_blank"
                rel="noreferrer"
                className="inline-flex rounded-lg border border-brand px-4 py-2 text-sm font-semibold text-brand transition hover:bg-brand hover:text-on-brand"
              >
                {dict.footer.infoPPDB}
              </a>
            )}
            <InstallPWA />
          </div>
        </div>
        </div>
      </div>

      {widgets && widgets.length > 0 && (
        <div className="border-t border-border">
          <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <BlockRenderer blocks={widgets} />
          </div>
        </div>
      )}

      <div className="border-t border-border">
        <div className="mx-auto flex max-w-7xl flex-col gap-2 px-4 py-5 text-xs text-ink-muted sm:flex-row sm:items-center sm:justify-between sm:px-6">
          <p>© {year} {name}. {dict.footer.allRightsReserved}</p>
          <div className="flex flex-wrap items-center gap-x-4 gap-y-1">
            <Link href="/peta-situs" className="transition hover:text-brand">{dict.nav.sitemap}</Link>
            <Link href="/kontak" className="transition hover:text-brand">{dict.footer.contact}</Link>
            <span>{dict.footer.developedWith}</span>
          </div>
        </div>
      </div>
    </footer>
  );
}
