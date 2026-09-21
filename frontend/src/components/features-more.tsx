"use client";

import Link from "next/link";
import { useEffect, useRef, useState } from "react";
import type { Testimonial } from "@/lib/types";

/* ============================================================
   Slider berita 5 terbaru
   ============================================================ */

type SlidePost = {
  title: string;
  slug: string;
  cover: string | null;
  category?: { name: string } | null;
  published_at: string | null;
};

export function NewsSlider({ posts }: { posts: SlidePost[] }) {
  const [i, setI] = useState(0);
  const [paused, setPaused] = useState(false);
  const n = posts.length;

  useEffect(() => {
    if (n < 2 || paused) return;
    const id = setInterval(() => setI((v) => (v + 1) % n), 5500);
    return () => clearInterval(id);
  }, [n, paused]);

  if (n === 0) return null;

  return (
    <div
      className="group relative aspect-4/3 overflow-hidden rounded-2xl border border-border bg-surface-muted shadow-brand"
      onMouseEnter={() => setPaused(true)}
      onMouseLeave={() => setPaused(false)}
    >
      {posts.map((p, idx) => (
        <Link
          key={p.slug}
          href={`/berita/${p.slug}`}
          aria-hidden={idx !== i}
          tabIndex={idx === i ? 0 : -1}
          className="absolute inset-0 transition-opacity duration-700"
          style={{ opacity: idx === i ? 1 : 0, pointerEvents: idx === i ? "auto" : "none" }}
        >
          <div className="media-fallback absolute inset-0" />
          {p.cover ? (
            // eslint-disable-next-line @next/next/no-img-element
            <img
              src={p.cover}
              alt=""
              className="absolute inset-0 h-full w-full object-cover transition-transform duration-[6000ms] ease-out"
              style={{ transform: idx === i ? "scale(1.06)" : "scale(1)" }}
            />
          ) : (
            <div className="grid h-full place-items-center text-3xl font-black text-brand/70">MTsN 1</div>
          )}
          <div className="absolute inset-x-0 bottom-0 bg-linear-to-t from-black/90 via-black/45 to-transparent p-5 pt-20">
            {p.category && (
              <span className="inline-flex rounded-md bg-surface/95 px-2 py-0.5 text-[0.62rem] font-bold uppercase tracking-wide text-brand-dark">
                {p.category.name}
              </span>
            )}
            <p className="mt-2 line-clamp-2 text-sm font-bold text-white sm:text-base">{p.title}</p>
          </div>
        </Link>
      ))}

      <div className="absolute inset-x-0 top-3 z-10 flex justify-center gap-1.5">
        {posts.map((_, idx) => (
          <button
            key={idx}
            type="button"
            aria-label={`Berita ${idx + 1}`}
            onClick={() => setI(idx)}
            className={`h-1.5 rounded-full transition-all ${idx === i ? "w-6 bg-white" : "w-1.5 bg-white/50 hover:bg-white/80"}`}
          />
        ))}
      </div>
      {n > 1 && (
        <>
          <button
            type="button"
            aria-label="Sebelumnya"
            onClick={() => setI((v) => (v - 1 + n) % n)}
            className="absolute left-2 top-1/2 z-10 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-full bg-black/35 text-white opacity-0 backdrop-blur transition group-hover:opacity-100"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round"><path d="M15 18l-6-6 6-6" /></svg>
          </button>
          <button
            type="button"
            aria-label="Berikutnya"
            onClick={() => setI((v) => (v + 1) % n)}
            className="absolute right-2 top-1/2 z-10 grid h-9 w-9 -translate-y-1/2 place-items-center rounded-full bg-black/35 text-white opacity-0 backdrop-blur transition group-hover:opacity-100"
          >
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round"><path d="M9 6l6 6-6 6" /></svg>
          </button>
        </>
      )}
    </div>
  );
}

/* ============================================================
   Bundle E — Testimoni + progres baca
   ============================================================ */

export function Testimonials({ items }: { items: Testimonial[] }) {
  const [i, setI] = useState(0);
  const n = items.length;

  useEffect(() => {
    if (n < 2) return;
    const id = setInterval(() => setI((v) => (v + 1) % n), 7000);
    return () => clearInterval(id);
  }, [n]);

  if (n === 0) return null;
  const t = items[i];

  return (
    <div className="relative mx-auto max-w-3xl text-center">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="currentColor" className="mx-auto text-brand/25" aria-hidden>
        <path d="M9 7H5a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2v2a2 2 0 0 1-2 2H4v2h1a4 4 0 0 0 4-4V7zm10 0h-4a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h2v2a2 2 0 0 1-2 2h-1v2h1a4 4 0 0 0 4-4V7z" />
      </svg>
      <blockquote
        key={i}
        className="mt-3 text-lg font-medium leading-relaxed text-foreground sm:text-xl"
        style={{ animation: "page-enter .5s ease" }}
      >
        {t.quote}
      </blockquote>
      <p className="mt-4 font-extrabold text-foreground">{t.name}</p>
      <p className="text-sm text-ink-muted">{t.role}</p>
      {n > 1 && (
        <div className="mt-6 flex justify-center gap-2">
          {items.map((_, idx) => (
            <button
              key={idx}
              type="button"
              aria-label={`Testimoni ${idx + 1}`}
              onClick={() => setI(idx)}
              className={`h-2 rounded-full transition-all ${idx === i ? "w-6 bg-brand" : "w-2 bg-border-strong hover:bg-brand/50"}`}
            />
          ))}
        </div>
      )}
    </div>
  );
}

export function ArticleProgress() {
  const ref = useRef<HTMLDivElement | null>(null);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    const article =
      document.querySelector<HTMLElement>(".prose-content") ??
      document.querySelector<HTMLElement>("article");
    if (!article) return;

    let raf = 0;
    const update = () => {
      raf = 0;
      const r = article.getBoundingClientRect();
      const total = r.height - window.innerHeight;
      const passed = Math.min(Math.max(-r.top, 0), Math.max(total, 1));
      el.style.transform = `scaleX(${total > 0 ? passed / total : 0})`;
    };
    const onScroll = () => {
      if (!raf) raf = requestAnimationFrame(update);
    };
    update();
    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("resize", onScroll);
    return () => {
      window.removeEventListener("scroll", onScroll);
      window.removeEventListener("resize", onScroll);
    };
  }, []);

  return (
    <div className="sticky top-16 z-30 -mx-4 mb-6 h-0.5 bg-border sm:-mx-6">
      <div ref={ref} className="h-full origin-left bg-brand" style={{ transform: "scaleX(0)" }} />
    </div>
  );
}

/* ============================================================
   Bundle G — Riwayat pencarian + mini kalender
   ============================================================ */

const SEARCH_KEY = "mtsn1-recent-search";

export function readRecentSearches(): string[] {
  try {
    const raw = JSON.parse(localStorage.getItem(SEARCH_KEY) || "[]");
    return Array.isArray(raw) ? raw.slice(0, 6) : [];
  } catch {
    return [];
  }
}
export function pushRecentSearch(q: string) {
  const t = q.trim();
  if (t.length < 2) return;
  try {
    const prev = readRecentSearches().filter((x) => x.toLowerCase() !== t.toLowerCase());
    localStorage.setItem(SEARCH_KEY, JSON.stringify([t, ...prev].slice(0, 6)));
  } catch {
    /* abaikan */
  }
}

export function MiniCalendar({ dates }: { dates: string[] }) {
  const [cursor, setCursor] = useState(() => {
    const d = new Date();
    return { y: d.getFullYear(), m: d.getMonth() };
  });

  const marked = new Set(
    dates
      .map((s) => new Date(s))
      .filter((d) => !Number.isNaN(d.getTime()))
      .map((d) => `${d.getFullYear()}-${d.getMonth()}-${d.getDate()}`),
  );

  const first = new Date(cursor.y, cursor.m, 1);
  const startDow = (first.getDay() + 6) % 7;
  const daysInMonth = new Date(cursor.y, cursor.m + 1, 0).getDate();
  const today = new Date();
  const isToday = (d: number) =>
    today.getFullYear() === cursor.y && today.getMonth() === cursor.m && today.getDate() === d;

  const monthName = first.toLocaleDateString("id-ID", { month: "long", year: "numeric" });
  const cells: (number | null)[] = [
    ...Array(startDow).fill(null),
    ...Array.from({ length: daysInMonth }, (_, k) => k + 1),
  ];

  return (
    <div className="card p-4 sm:p-5">
      <div className="mb-3 flex items-center justify-between">
        <button
          type="button"
          aria-label="Bulan sebelumnya"
          onClick={() => setCursor((c) => ({ y: c.m === 0 ? c.y - 1 : c.y, m: (c.m + 11) % 12 }))}
          className="grid h-8 w-8 place-items-center rounded-lg border border-border transition hover:border-brand"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round"><path d="M15 18l-6-6 6-6" /></svg>
        </button>
        <p className="text-sm font-bold capitalize text-foreground">{monthName}</p>
        <button
          type="button"
          aria-label="Bulan berikutnya"
          onClick={() => setCursor((c) => ({ y: c.m === 11 ? c.y + 1 : c.y, m: (c.m + 1) % 12 }))}
          className="grid h-8 w-8 place-items-center rounded-lg border border-border transition hover:border-brand"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round"><path d="M9 6l6 6-6 6" /></svg>
        </button>
      </div>
      <div className="grid grid-cols-7 gap-1 text-center text-[0.65rem] font-bold uppercase text-ink-muted">
        {["Sen", "Sel", "Rab", "Kam", "Jum", "Sab", "Min"].map((d) => (
          <div key={d} className="py-1">{d}</div>
        ))}
      </div>
      <div className="mt-1 grid grid-cols-7 gap-1">
        {cells.map((d, idx) => {
          if (d === null) return <div key={idx} />;
          const has = marked.has(`${cursor.y}-${cursor.m}-${d}`);
          return (
            <div
              key={idx}
              className={`relative grid aspect-square place-items-center rounded-lg text-sm ${
                isToday(d)
                  ? "bg-brand font-bold text-on-brand"
                  : has
                    ? "bg-brand-light font-semibold text-brand-dark"
                    : "text-ink-soft"
              }`}
            >
              {d}
              {has && !isToday(d) && <span className="absolute bottom-1 h-1 w-1 rounded-full bg-accent" />}
            </div>
          );
        })}
      </div>
      <p className="mt-3 flex items-center gap-1.5 text-[0.7rem] text-ink-muted">
        <span className="h-1.5 w-1.5 rounded-full bg-accent" /> Ada kegiatan
      </p>
    </div>
  );
}

/* ============================================================
   Bundle H — Aksesibilitas + PWA install
   ============================================================ */

const A11Y_KEY = "mtsn1-a11y";

export function A11yToolbar() {
  const [open, setOpen] = useState(false);
  const [scale, setScale] = useState(1);
  const [contrast, setContrast] = useState(false);

  useEffect(() => {
    // Preferensi aksesibilitas (skala teks, kontras) dibaca dari localStorage.
    try {
      const s = JSON.parse(localStorage.getItem(A11Y_KEY) || "{}");
      // eslint-disable-next-line react-hooks/set-state-in-effect
      if (s.scale) setScale(s.scale);
      // eslint-disable-next-line react-hooks/set-state-in-effect
      if (s.contrast) setContrast(true);
    } catch {
      /* abaikan */
    }
  }, []);

  useEffect(() => {
    document.documentElement.style.setProperty("--font-scale", String(scale));
    document.documentElement.toggleAttribute("data-contrast", contrast);
    try {
      localStorage.setItem(A11Y_KEY, JSON.stringify({ scale, contrast }));
    } catch {
      /* abaikan */
    }
  }, [scale, contrast]);

  return (
    <div className="fixed bottom-5 left-5 z-50 print:hidden">
      {open && (
        <div className="pop-in mb-2 w-56 rounded-xl border border-border bg-surface p-3 shadow-lg">
          <p className="mb-2 text-xs font-bold uppercase tracking-wide text-ink-muted">Aksesibilitas</p>
          <div className="flex items-center justify-between gap-2">
            <span className="text-sm font-semibold">Ukuran teks</span>
            <div className="flex items-center gap-1">
              <button type="button" aria-label="Perkecil teks" onClick={() => setScale((s) => Math.max(0.9, +(s - 0.1).toFixed(2)))} className="grid h-7 w-7 place-items-center rounded-md border border-border text-sm font-bold hover:border-brand">A-</button>
              <button type="button" aria-label="Ukuran teks normal" onClick={() => setScale(1)} className="grid h-7 w-7 place-items-center rounded-md border border-border text-xs font-bold hover:border-brand">A</button>
              <button type="button" aria-label="Perbesar teks" onClick={() => setScale((s) => Math.min(1.4, +(s + 0.1).toFixed(2)))} className="grid h-7 w-7 place-items-center rounded-md border border-border text-base font-bold hover:border-brand">A+</button>
            </div>
          </div>
          <label className="mt-3 flex cursor-pointer items-center justify-between">
            <span className="text-sm font-semibold">Kontras tinggi</span>
            <input type="checkbox" checked={contrast} onChange={(e) => setContrast(e.target.checked)} className="h-4 w-4 accent-[var(--brand)]" />
          </label>
        </div>
      )}
      <button
        type="button"
        aria-label="Opsi aksesibilitas"
        aria-expanded={open}
        onClick={() => setOpen((v) => !v)}
        className="grid h-11 w-11 place-items-center rounded-full border border-border bg-surface text-foreground shadow-md transition hover:border-brand"
      >
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round">
          <circle cx="12" cy="4" r="1.6" />
          <path d="M5 8h14M12 8v7M12 15l-3 5M12 15l3 5" />
        </svg>
      </button>
    </div>
  );
}

export function InstallPWA() {
  const [ev, setEv] = useState<Event | null>(null);
  const [hidden, setHidden] = useState(false);

  useEffect(() => {
    const onPrompt = (e: Event) => {
      e.preventDefault();
      setEv(e);
    };
    const onInstalled = () => setHidden(true);
    window.addEventListener("beforeinstallprompt", onPrompt);
    window.addEventListener("appinstalled", onInstalled);
    return () => {
      window.removeEventListener("beforeinstallprompt", onPrompt);
      window.removeEventListener("appinstalled", onInstalled);
    };
  }, []);

  if (!ev || hidden) return null;

  return (
    <button
      type="button"
      onClick={async () => {
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        const p = ev as any;
        p.prompt?.();
        try {
          await p.userChoice;
        } catch {
          /* abaikan */
        }
        setHidden(true);
      }}
      className="inline-flex items-center gap-2 rounded-xl border border-brand bg-brand-light px-4 py-2 text-sm font-semibold text-brand-dark transition hover:bg-brand hover:text-on-brand"
    >
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M12 3v12M8 11l4 4 4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" />
      </svg>
      Pasang aplikasi
    </button>
  );
}
