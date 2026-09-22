"use client";

import Image from "@/components/media-image";
import Link from "next/link";
import { usePathname } from "next/navigation";
import { useEffect, useRef, useState } from "react";
import type { ApiMenu, Settings } from "@/lib/types";
import type { Dictionary } from "@/dictionaries";
import { buildNav, isNavGroup, type NavGroup } from "@/lib/nav";
import { SiteSearch } from "@/components/site-search";
import { ThemeToggle } from "@/components/theme-switcher";
import { LanguageSwitcher } from "@/components/language-switcher";

const isHttp = (s?: string) => !!s && /^https?:\/\//.test(s);
const LAPOR_URL = "https://www.lapor.go.id/";

/** Bagian path sebelum "#": "/akademik#kurikulum" -> "/akademik", "/#program" -> "/". */
const pathOf = (href: string) => href.split("#")[0] || "/";

function ExtIcon({ className = "" }: { className?: string }) {
  return (
    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" className={className}>
      <path d="M7 17 17 7M8 7h9v9" />
    </svg>
  );
}

export function SiteHeader({
  settings,
  menu,
  dict,
}: {
  settings: Settings;
  menu: ApiMenu;
  dict: Dictionary;
}) {
  const pathname = usePathname();
  const [drawer, setDrawer] = useState(false);
  const [openMenu, setOpenMenu] = useState<string | null>(null);
  const [drawerGroup, setDrawerGroup] = useState<string | null>(null);
  const [scrolled, setScrolled] = useState(false);
  const navRef = useRef<HTMLElement | null>(null);
  const closeTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  const nav = buildNav(menu);
  const name = settings.site_name ?? "MTsN 1 Kota Malang";

  const leafActive = (href: string) => {
    const p = pathOf(href);
    if (p === "/") return pathname === "/";
    return pathname === p || pathname.startsWith(p + "/");
  };
  const groupActive = (g: NavGroup) =>
    pathname.startsWith(g.base) ||
    g.items.some((i) => pathOf(i.href) !== "/" && leafActive(i.href));

  const openGroup = (label: string) => {
    if (closeTimer.current) clearTimeout(closeTimer.current);
    setOpenMenu(label);
  };
  const scheduleClose = (label: string) => {
    if (closeTimer.current) clearTimeout(closeTimer.current);
    closeTimer.current = setTimeout(() => {
      setOpenMenu((m) => (m === label ? null : m));
    }, 180);
  };

  // Navigasi papan ketik untuk dropdown desktop (roving focus antar menuitem).
  const onGroupKeyDown = (e: React.KeyboardEvent<HTMLDivElement>, label: string) => {
    const { key } = e;
    if (!["ArrowDown", "ArrowUp", "Home", "End"].includes(key)) return;
    const wrap = e.currentTarget;
    const onButton = (e.target as HTMLElement).tagName === "BUTTON";
    if (onButton && (key === "ArrowDown" || key === "ArrowUp")) {
      e.preventDefault();
      if (openMenu !== label) setOpenMenu(label);
      requestAnimationFrame(() => {
        const items = wrap.querySelectorAll<HTMLElement>('[role="menuitem"]');
        items[key === "ArrowUp" ? items.length - 1 : 0]?.focus();
      });
      return;
    }
    const items = Array.from(wrap.querySelectorAll<HTMLElement>('[role="menuitem"]'));
    if (!items.length) return;
    const idx = items.indexOf(document.activeElement as HTMLElement);
    e.preventDefault();
    if (key === "Home") items[0].focus();
    else if (key === "End") items[items.length - 1].focus();
    else if (key === "ArrowDown") items[(idx + 1) % items.length]?.focus();
    else if (key === "ArrowUp") items[(idx - 1 + items.length) % items.length]?.focus();
  };

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 8);
    // rAF menunda pembacaan awal ke luar body effect sinkron (aturan
    // react-hooks/set-state-in-effect) sekaligus menangkap posisi scroll
    // yang sudah ada saat mount (mis. navigasi mundur).
    const raf = requestAnimationFrame(onScroll);
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => {
      cancelAnimationFrame(raf);
      window.removeEventListener("scroll", onScroll);
    };
  }, []);

  // Tutup semua overlay saat pindah halaman. Path sebagai dependency; reset
  // dijadwalkan via microtask agar bukan setState sinkron di body effect.
  useEffect(() => {
    queueMicrotask(() => {
      setDrawer(false);
      setOpenMenu(null);
    });
  }, [pathname]);

  useEffect(() => () => {
    if (closeTimer.current) clearTimeout(closeTimer.current);
  }, []);

  // Kunci scroll saat drawer mobile terbuka; buka otomatis grup yang aktif.
  useEffect(() => {
    document.body.style.overflow = drawer ? "hidden" : "";
    if (drawer) {
      const activeGroup = nav.find(
        (e): e is NavGroup => isNavGroup(e) && groupActive(e),
      );
      queueMicrotask(() => setDrawerGroup(activeGroup?.label ?? null));
    }
    return () => {
      document.body.style.overflow = "";
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [drawer]);

  // Klik di luar / Escape menutup dropdown desktop.
  useEffect(() => {
    if (!openMenu) return;
    const onPointer = (e: PointerEvent) => {
      if (navRef.current && !navRef.current.contains(e.target as Node)) setOpenMenu(null);
    };
    const onKey = (e: KeyboardEvent) => e.key === "Escape" && setOpenMenu(null);
    document.addEventListener("pointerdown", onPointer);
    document.addEventListener("keydown", onKey);
    return () => {
      document.removeEventListener("pointerdown", onPointer);
      document.removeEventListener("keydown", onKey);
    };
  }, [openMenu]);

  const Logo = (
    <Link href="/" className="flex min-w-0 items-center gap-2.5" aria-label={name}>
      <span className="grid h-10 w-10 shrink-0 place-items-center overflow-hidden rounded-xl bg-brand text-on-brand ring-1 ring-black/5">
        {settings.logo ? (
          <Image src={settings.logo} alt="" width={40} height={40} className="h-full w-full object-cover" />
        ) : (
          <span className="text-sm font-black">M1</span>
        )}
      </span>
      <span className="min-w-0 leading-tight">
        <span className="block truncate text-[0.9rem] font-extrabold tracking-tight text-foreground sm:text-[0.95rem]">
          {name}
        </span>
        <span className="hidden truncate text-[0.7rem] font-semibold uppercase tracking-[0.16em] text-ink-muted sm:block">
          Madrasah Tsanawiyah Negeri
        </span>
      </span>
    </Link>
  );

  const utilityLinks = [
    { label: "SP4N-LAPOR!", href: LAPOR_URL },
    { label: "PPID", href: "/layanan#erepository" },
    isHttp(settings.sakip_url) ? { label: "SAKIP", href: settings.sakip_url! } : null,
    { label: "Zona Integritas", href: "/area-zi" },
    { label: "Peta Situs", href: "/peta-situs" },
  ].filter((x): x is { label: string; href: string } => x !== null);

  return (
    <>
      {/* ---- Utility bar (desktop) — menyusut saat di-scroll ---- */}
      <div
        className={`hidden overflow-hidden border-b border-border bg-brand-darker text-white/90 transition-all duration-300 lg:block ${
          scrolled ? "max-h-0 border-b-0 opacity-0" : "max-h-12 opacity-100"
        }`}
      >
        <div className="mx-auto flex h-9 w-full max-w-7xl items-center justify-between px-4 text-[0.7rem] sm:px-6 lg:px-8">
          <div className="flex items-center gap-3">
            {utilityLinks.map((u, i) => {
              const ext = u.href.startsWith("http");
              const cls = "font-semibold uppercase tracking-wide text-white/85 transition hover:text-white";
              return (
                <span key={u.label} className="flex items-center gap-3">
                  {i > 0 && <span aria-hidden className="h-1 w-1 rounded-full bg-white/25" />}
                  {ext ? (
                    <a href={u.href} target="_blank" rel="noreferrer" className={cls}>{u.label}</a>
                  ) : (
                    <Link href={u.href} className={cls}>{u.label}</Link>
                  )}
                </span>
              );
            })}
          </div>
          <div className="flex items-center gap-4 text-white/85">
            {settings.email && (
              <a href={`mailto:${settings.email}`} className="hidden transition hover:text-white xl:inline">
                {settings.email}
              </a>
            )}
            {(settings.whatsapp ?? settings.phone) && (
              <a
                href={
                  settings.whatsapp
                    ? `https://wa.me/${settings.whatsapp.replace(/[^0-9]/g, "")}`
                    : `tel:${settings.phone}`
                }
                target="_blank"
                rel="noreferrer"
                className="font-semibold transition hover:text-white"
              >
                WhatsApp: {settings.whatsapp ?? settings.phone}
              </a>
            )}
          </div>
        </div>
      </div>

      <header
        data-site-header
        data-scrolled={scrolled}
        className="sticky top-0 z-40 border-b border-border bg-surface/95 backdrop-blur-md supports-[backdrop-filter]:bg-surface/80"
      >
        <div className="mx-auto flex h-16 w-full max-w-7xl items-center justify-between gap-3 px-4 sm:gap-4 sm:px-6 lg:px-8">
          {Logo}

          <div className="flex shrink-0 items-center gap-1.5">
            {/* ---- Desktop nav ---- */}
            <nav ref={navRef} className="hidden items-center lg:flex">
              {nav.map((entry, i) => {
                // Panel grup di paruh kanan bar dibuka rata-kanan agar tak keluar layar.
                const alignRight = i >= Math.floor(nav.length / 2);
                if (!isNavGroup(entry)) {
                  if (entry.href === "/") return null; // "Beranda" diwakili logo di bar desktop
                  const ext = entry.href.startsWith("http");
                  const cls = `nav-link inline-flex items-center gap-1 whitespace-nowrap rounded-lg px-2.5 py-2 text-[0.85rem] font-semibold transition ${
                    leafActive(entry.href)
                      ? "bg-brand-light text-brand-dark"
                      : "text-ink-soft hover:bg-surface-muted hover:text-foreground"
                  }`;
                  return ext ? (
                    <a key={entry.href} href={entry.href} target="_blank" rel="noreferrer" className={cls}>
                      {entry.label}
                      <ExtIcon className="opacity-60" />
                    </a>
                  ) : (
                    <Link
                      key={entry.href}
                      href={entry.href}
                      aria-current={leafActive(entry.href) ? "page" : undefined}
                      data-active={leafActive(entry.href) || undefined}
                      className={cls}
                    >
                      {entry.label}
                    </Link>
                  );
                }
                const active = groupActive(entry);
                const isOpen = openMenu === entry.label;
                const twoCol = !!entry.sections || entry.items.length > 5;

                const menuItem = (it: (typeof entry.items)[number]) => {
                  const external = it.href.startsWith("http");
                  const itActive = it.href === pathname;
                  return (
                    <Link
                      key={`${it.label}-${it.href}`}
                      href={it.href}
                      role="menuitem"
                      target={external ? "_blank" : undefined}
                      rel={external ? "noreferrer" : undefined}
                      onClick={() => setOpenMenu(null)}
                      className={`group/mi relative flex items-center gap-2.5 rounded-lg py-2 pl-4 pr-3 text-sm transition ${
                        itActive
                          ? "bg-brand-light font-semibold text-brand-dark"
                          : "text-ink-soft hover:bg-surface-muted hover:text-foreground"
                      }`}
                    >
                      <span
                        className={`absolute left-1 top-1/2 h-4 w-1 -translate-y-1/2 rounded-full transition-all ${
                          itActive ? "bg-brand opacity-100" : "bg-brand opacity-0 group-hover/mi:opacity-60"
                        }`}
                      />
                      <span className="flex-1">
                        {it.label}
                        {it.desc && <span className="mt-0.5 block text-xs font-normal text-ink-muted">{it.desc}</span>}
                      </span>
                      {external ? (
                        <ExtIcon className="shrink-0 text-ink-muted" />
                      ) : (
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.6" strokeLinecap="round" strokeLinejoin="round" className={`shrink-0 -translate-x-1 text-brand opacity-0 transition-all group-hover/mi:translate-x-0 group-hover/mi:opacity-100 ${itActive ? "opacity-100" : ""}`}>
                          <path d="M9 6l6 6-6 6" />
                        </svg>
                      )}
                    </Link>
                  );
                };
                return (
                  <div
                    key={entry.label}
                    className="relative"
                    onMouseEnter={() => openGroup(entry.label)}
                    onMouseLeave={() => scheduleClose(entry.label)}
                    onKeyDown={(e) => onGroupKeyDown(e, entry.label)}
                  >
                    <button
                      type="button"
                      onClick={() => (isOpen ? setOpenMenu(null) : openGroup(entry.label))}
                      aria-expanded={isOpen}
                      aria-haspopup="menu"
                      data-active={active || isOpen || undefined}
                      className={`nav-link flex items-center gap-1 whitespace-nowrap rounded-lg px-2.5 py-2 text-[0.85rem] font-semibold transition ${
                        active || isOpen
                          ? "bg-brand-light text-brand-dark"
                          : "text-ink-soft hover:bg-surface-muted hover:text-foreground"
                      }`}
                    >
                      {entry.label}
                      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.6" strokeLinecap="round" strokeLinejoin="round" className={`transition ${isOpen ? "rotate-180" : ""}`}>
                        <path d="M6 9l6 6 6-6" />
                      </svg>
                    </button>
                    {isOpen && (
                      <div
                        className={`absolute top-full z-50 pt-2 ${alignRight ? "right-0" : "left-0"}`}
                        onMouseEnter={() => openGroup(entry.label)}
                        onMouseLeave={() => scheduleClose(entry.label)}
                      >
                        <div
                          role="menu"
                          aria-label={entry.label}
                          className={`pop-in relative overflow-hidden rounded-xl border border-border bg-surface shadow-[0_16px_48px_-16px_rgba(16,32,27,0.28)] ${
                            twoCol ? "w-[min(37rem,88vw)]" : "w-72"
                          }`}
                        >
                          <div className="flex items-center gap-2 bg-brand-light/60 px-4 py-2.5 text-[0.72rem] font-bold uppercase tracking-wide text-brand-dark">
                            <span className="grid h-6 w-6 place-items-center rounded-md bg-brand text-on-brand">
                              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                                <path d={entry.icon} />
                              </svg>
                            </span>
                            {entry.label}
                          </div>
                          <div className="p-2">
                            {entry.sections ? (
                              <div className="grid grid-cols-2 gap-x-3">
                                {entry.sections.map((sec, si) => (
                                  <div key={sec.title} className={si > 0 ? "border-l border-border pl-2" : ""}>
                                    <p className="flex items-center gap-1.5 px-3 pb-1 pt-1.5 text-[0.62rem] font-bold uppercase tracking-wider text-ink-muted">
                                      <span className="h-1 w-1 rounded-full bg-accent" />
                                      {sec.title}
                                    </p>
                                    {sec.items.map(menuItem)}
                                  </div>
                                ))}
                              </div>
                            ) : (
                              <div className={twoCol ? "grid grid-cols-2 gap-x-2" : ""}>
                                {entry.items.map(menuItem)}
                              </div>
                            )}
                            {entry.feature && (
                              <Link
                                href={entry.feature.href}
                                onClick={() => setOpenMenu(null)}
                                className="group/f mt-1.5 flex items-center gap-3 rounded-lg bg-brand-darker p-3.5 text-white transition hover:brightness-110"
                              >
                                <span className="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-white/10">
                                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round">
                                    <path d={entry.icon} />
                                  </svg>
                                </span>
                                <span className="min-w-0 flex-1">
                                  <span className="block text-sm font-bold">{entry.feature.title}</span>
                                  <span className="mt-0.5 block text-xs text-white/75">{entry.feature.text}</span>
                                </span>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.6" strokeLinecap="round" strokeLinejoin="round" className="shrink-0 text-white/90 transition-transform group-hover/f:translate-x-0.5">
                                  <path d="M9 6l6 6-6 6" />
                                </svg>
                              </Link>
                            )}
                          </div>
                        </div>
                      </div>
                    )}
                  </div>
                );
              })}
            </nav>

            <SiteSearch />
            <LanguageSwitcher className="hidden sm:inline-flex" />
            <ThemeToggle className="hidden sm:grid" />

            {settings.ppdb_url && (
              <a
                href={settings.ppdb_url}
                target="_blank"
                rel="noreferrer"
                className="btn-glow hidden min-h-10 items-center whitespace-nowrap rounded-lg bg-brand px-4 text-sm font-semibold text-on-brand shadow-sm transition hover:bg-brand-dark xl:inline-flex"
              >
                {dict.nav.ppdbOnline}
              </a>
            )}

            <button
              type="button"
              onClick={() => setDrawer((v) => !v)}
              className="grid h-10 w-10 place-items-center rounded-lg border border-border text-foreground transition hover:border-brand lg:hidden"
              aria-label={dict.common.menu}
              aria-expanded={drawer}
            >
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round">
                {drawer ? <path d="M6 6l12 12M18 6L6 18" /> : <path d="M4 7h16M4 12h16M4 17h16" />}
              </svg>
            </button>
          </div>
        </div>

        {/* ---- Mobile drawer ---- */}
        <div className={`lg:hidden ${drawer ? "pointer-events-auto" : "pointer-events-none"}`}>
          <div
            className={`fixed inset-0 z-40 bg-black/50 backdrop-blur-sm transition-opacity duration-300 ${drawer ? "opacity-100" : "opacity-0"}`}
            onClick={() => setDrawer(false)}
          />
          <div
            className={`fixed inset-y-0 right-0 z-40 flex w-[88%] max-w-sm flex-col overflow-y-auto rounded-l-2xl border-l border-border bg-surface p-4 shadow-2xl transition-transform duration-300 ${drawer ? "translate-x-0" : "translate-x-full"}`}
          >
            <div className="mb-4 flex items-center justify-between gap-3 border-b border-border pb-4">
              {Logo}
              <div className="flex items-center gap-2">
                <LanguageSwitcher />
                <ThemeToggle />
              </div>
            </div>
            <div className="mb-3">
              <SiteSearch variant="block" />
            </div>
            <nav className="flex flex-col gap-0.5">
              {nav.map((entry) => {
                if (!isNavGroup(entry)) {
                  const ext = entry.href.startsWith("http");
                  const cls = `flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-semibold transition ${
                    !ext && leafActive(entry.href)
                      ? "bg-brand-light text-brand-dark"
                      : "text-ink-soft hover:bg-surface-muted hover:text-foreground"
                  }`;
                  return ext ? (
                    <a key={entry.href} href={entry.href} target="_blank" rel="noreferrer" onClick={() => setDrawer(false)} className={cls}>
                      {entry.label}
                      <ExtIcon className="opacity-60" />
                    </a>
                  ) : (
                    <Link key={entry.href} href={entry.href} onClick={() => setDrawer(false)} aria-current={leafActive(entry.href) ? "page" : undefined} className={cls}>
                      {entry.label}
                    </Link>
                  );
                }
                const isOpen = drawerGroup === entry.label;
                const gActive = groupActive(entry);
                return (
                  <div key={entry.label}>
                    <button
                      type="button"
                      onClick={() => setDrawerGroup(isOpen ? null : entry.label)}
                      aria-expanded={isOpen}
                      className={`flex w-full items-center gap-2.5 rounded-lg px-3 py-2.5 text-sm font-semibold transition ${
                        gActive || isOpen ? "bg-brand-light text-brand-dark" : "text-ink-soft hover:bg-surface-muted"
                      }`}
                    >
                      <span className={`grid h-7 w-7 shrink-0 place-items-center rounded-md transition ${gActive || isOpen ? "bg-brand text-on-brand" : "bg-surface-muted text-ink-muted"}`}>
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round">
                          <path d={entry.icon} />
                        </svg>
                      </span>
                      <span className="flex-1 text-left">{entry.label}</span>
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.6" strokeLinecap="round" strokeLinejoin="round" className={`shrink-0 transition-transform ${isOpen ? "rotate-90" : ""}`}>
                        <path d="M9 6l6 6-6 6" />
                      </svg>
                    </button>
                    {isOpen && (
                      <div className="my-1 ml-6 flex flex-col gap-0.5 border-l-2 border-brand-light pl-3">
                        {(entry.sections
                          ? entry.sections
                          : [{ title: "", items: entry.items }]
                        ).map((sec) => (
                          <div key={sec.title || "flat"}>
                            {sec.title && (
                              <p className="flex items-center gap-1.5 px-3 pb-0.5 pt-2 text-[0.62rem] font-bold uppercase tracking-wider text-ink-muted">
                                <span className="h-1 w-1 rounded-full bg-accent" />
                                {sec.title}
                              </p>
                            )}
                            {sec.items.map((it) => {
                              const ext = it.href.startsWith("http");
                              const sActive = it.href === pathname;
                              const c = `flex items-center gap-2 rounded-md px-3 py-2 text-[0.85rem] transition ${sActive ? "font-semibold text-brand-dark" : "text-ink-soft hover:bg-surface-muted hover:text-foreground"}`;
                              const inner = (
                                <>
                                  <span className={`h-1.5 w-1.5 shrink-0 rounded-full ${sActive ? "bg-brand" : "bg-border-strong"}`} />
                                  <span className="flex-1">{it.label}</span>
                                  {ext && <ExtIcon className="opacity-60" />}
                                </>
                              );
                              return ext ? (
                                <a key={`${it.label}-${it.href}`} href={it.href} target="_blank" rel="noreferrer" onClick={() => setDrawer(false)} className={c}>
                                  {inner}
                                </a>
                              ) : (
                                <Link key={`${it.label}-${it.href}`} href={it.href} onClick={() => setDrawer(false)} aria-current={sActive ? "page" : undefined} className={c}>
                                  {inner}
                                </Link>
                              );
                            })}
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                );
              })}
            </nav>
            <Link
              href="/peta-situs"
              onClick={() => setDrawer(false)}
              className="mt-4 flex items-center gap-2 border-t border-border px-3 pt-4 text-xs font-semibold text-ink-muted transition hover:text-brand"
            >
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M9 20l-5.4 1.8L3 21V6l6-2 6 2 5.4-1.8L21 3v9M9 4v16M15 6v8" /></svg>
              {dict.nav.sitemapAll}
            </Link>
            {settings.ppdb_url && (
              <a
                href={settings.ppdb_url}
                target="_blank"
                rel="noreferrer"
                className="btn-glow mt-4 flex items-center justify-center gap-2 rounded-lg bg-brand px-4 py-3 text-sm font-semibold text-on-brand shadow-brand"
              >
                {dict.nav.ppdbOnline}
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round"><path d="M5 12h14M13 6l6 6-6 6" /></svg>
              </a>
            )}
          </div>
        </div>
      </header>
    </>
  );
}
