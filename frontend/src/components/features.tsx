"use client";

import Link from "next/link";
import {
  useCallback,
  useEffect,
  useRef,
  useState,
  useSyncExternalStore,
  type ReactNode,
} from "react";

/* ============================================================
   Paket A — PMBM & pengumuman
   ============================================================ */

/* ---------- Banner pengumuman (bisa ditutup) ---------- */

export function AnnouncementBar({
  text,
  href,
}: {
  text?: string;
  href?: string;
}) {
  const [show, setShow] = useState(false);
  const key = text ? `mtsn1-annc:${hash(text)}` : "";

  useEffect(() => {
    if (!text) return;
    // Status dismiss pengumuman dibaca dari localStorage (per-perangkat).
    try {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setShow(localStorage.getItem(key) !== "1");
    } catch {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setShow(true);
    }
  }, [text, key]);

  if (!text || !show) return null;

  const Inner = (
    <span className="flex min-w-0 items-center gap-2">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="shrink-0">
        <path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z" />
      </svg>
      <span className="truncate">{text}</span>
      {href && <span className="hidden shrink-0 font-bold underline sm:inline">Selengkapnya →</span>}
    </span>
  );

  return (
    <div className="relative z-40 bg-accent text-on-accent print:hidden">
      <div className="mx-auto flex max-w-7xl items-center gap-3 px-4 py-2 text-xs font-semibold sm:px-6 lg:px-8">
        {href ? (
          <Link href={href} className="min-w-0 flex-1">
            {Inner}
          </Link>
        ) : (
          <div className="min-w-0 flex-1">{Inner}</div>
        )}
        <button
          type="button"
          aria-label="Tutup pengumuman"
          onClick={() => {
            setShow(false);
            try {
              localStorage.setItem(key, "1");
            } catch {
              /* abaikan */
            }
          }}
          className="grid h-6 w-6 shrink-0 place-items-center rounded-md transition hover:bg-black/10"
        >
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round">
            <path d="M6 6l12 12M18 6 6 18" />
          </svg>
        </button>
      </div>
    </div>
  );
}

function hash(s: string) {
  let h = 0;
  for (let i = 0; i < s.length; i++) h = (Math.imul(31, h) + s.charCodeAt(i)) | 0;
  return (h >>> 0).toString(36);
}

/* ---------- Dock aksi mengambang ---------- */

type DockAction = { label: string; href: string; external?: boolean; icon: ReactNode; tone: string };

export function QuickDock({
  whatsapp,
  ppdbUrl,
  phone,
  mapsUrl,
}: {
  whatsapp?: string;
  ppdbUrl?: string;
  phone?: string;
  mapsUrl?: string;
}) {
  const [open, setOpen] = useState(false);
  const rootRef = useRef<HTMLDivElement | null>(null);

  useEffect(() => {
    if (!open) return;
    const onKey = (e: KeyboardEvent) => e.key === "Escape" && setOpen(false);
    const onClick = (e: MouseEvent) => {
      if (rootRef.current && !rootRef.current.contains(e.target as Node)) setOpen(false);
    };
    document.addEventListener("keydown", onKey);
    document.addEventListener("pointerdown", onClick);
    return () => {
      document.removeEventListener("keydown", onKey);
      document.removeEventListener("pointerdown", onClick);
    };
  }, [open]);

  const wa = whatsapp ? whatsapp.replace(/[^0-9]/g, "") : "";
  const actions: DockAction[] = [];
  if (wa)
    actions.push({
      label: "WhatsApp",
      href: `https://wa.me/${wa}`,
      external: true,
      tone: "bg-[#25D366] text-white",
      icon: <path d="M12 2a10 10 0 0 0-8.5 15.3L2 22l4.8-1.5A10 10 0 1 0 12 2zm5.3 14.1c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .3-3.4-.7-2.9-1.2-4.7-4.2-4.8-4.4-.1-.2-1.2-1.6-1.2-3s.7-2.1 1-2.4c.3-.3.6-.4.8-.4h.6c.2 0 .5-.1.7.5l1 2.3c.1.2.1.4 0 .6l-.5.7c-.1.2-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.2 1 2.1 1.4 2.4 1.5.3.1.5.1.7-.1l1-1.1c.2-.3.4-.2.7-.1l2.2 1c.3.2.5.3.6.4.1.2.1.8-.1 1.4z" />,
    });
  if (ppdbUrl)
    actions.push({
      label: "PMBM Online",
      href: ppdbUrl,
      external: ppdbUrl.startsWith("http"),
      tone: "bg-brand text-on-brand",
      icon: <path d="M12 3 2 8l10 5 10-5-10-5zM4 10v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6" />,
    });
  actions.push({
    label: "Kontak",
    href: "/kontak",
    tone: "bg-surface text-foreground",
    icon: <path d="M4 4h16v16H4zM4 8l8 5 8-5" />,
  });
  if (phone || wa)
    actions.push({
      label: "Telepon",
      href: phone ? `tel:${phone}` : `https://wa.me/${wa}`,
      external: !phone,
      tone: "bg-surface text-foreground",
      icon: <path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.7A2 2 0 0 1 4.1 2H7a2 2 0 0 1 2 1.7c.1 1.2.4 2.4.8 3.5a2 2 0 0 1-.5 2.1L8 10.6a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c1.1.4 2.3.7 3.5.8A2 2 0 0 1 22 16.9z" />,
    });
  if (mapsUrl)
    actions.push({
      label: "Lihat Peta",
      href: mapsUrl,
      external: true,
      tone: "bg-surface text-foreground",
      icon: <path d="M12 21s-7-5.2-7-11a7 7 0 1 1 14 0c0 5.8-7 11-7 11zM12 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4z" />,
    });

  return (
    <div ref={rootRef} className="fixed bottom-5 right-5 z-50 flex flex-col items-end gap-2 print:hidden">
      <div
        className={`flex flex-col items-end gap-2 transition-all duration-300 ${
          open ? "pointer-events-auto translate-y-0 opacity-100" : "pointer-events-none translate-y-3 opacity-0"
        }`}
      >
        <button
          type="button"
          onClick={() => {
            setOpen(false);
            window.dispatchEvent(new Event("mtsn1:open-help"));
          }}
          style={{ transitionDelay: open ? "0ms" : "0ms" }}
          className="group flex items-center gap-2.5 rounded-full border border-border bg-surface py-1.5 pl-3 pr-1.5 text-sm font-semibold text-foreground shadow-md ring-1 ring-black/5 transition hover:-translate-x-0.5"
        >
          <span className="whitespace-nowrap">Asisten &amp; FAQ</span>
          <span className="grid h-8 w-8 place-items-center rounded-full bg-brand text-on-brand">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round">
              <path d="M8 10h.01M12 10h.01M16 10h.01M21 12a8 8 0 0 1-11.3 7.3L3 21l1.7-6.7A8 8 0 1 1 21 12z" />
            </svg>
          </span>
        </button>
        {actions.map((a, i) => {
          const cls =
            "group flex items-center gap-2.5 rounded-full border border-border py-1.5 pl-3 pr-1.5 text-sm font-semibold shadow-md ring-1 ring-black/5 transition hover:-translate-x-0.5";
          const inner = (
            <>
              <span className="whitespace-nowrap">{a.label}</span>
              <span className={`grid h-8 w-8 place-items-center rounded-full ${a.tone}`}>
                <svg width="16" height="16" viewBox="0 0 24 24" fill={a.label === "WhatsApp" ? "currentColor" : "none"} stroke={a.label === "WhatsApp" ? "none" : "currentColor"} strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round">
                  {a.icon}
                </svg>
              </span>
            </>
          );
          return a.external ? (
            <a key={i} href={a.href} target="_blank" rel="noreferrer" className={`${cls} bg-surface text-foreground`} style={{ transitionDelay: open ? `${i * 35}ms` : "0ms" }}>
              {inner}
            </a>
          ) : (
            <Link key={i} href={a.href} className={`${cls} bg-surface text-foreground`} style={{ transitionDelay: open ? `${i * 35}ms` : "0ms" }} onClick={() => setOpen(false)}>
              {inner}
            </Link>
          );
        })}
      </div>
      <button
        type="button"
        aria-label={open ? "Tutup menu cepat" : "Menu cepat"}
        aria-expanded={open}
        onClick={() => setOpen((v) => !v)}
        className="grid h-14 w-14 place-items-center rounded-full bg-brand text-on-brand shadow-lg ring-1 ring-black/10 transition hover:bg-brand-dark"
      >
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round" strokeLinejoin="round" className={`transition-transform duration-300 ${open ? "rotate-45" : ""}`}>
          {open ? <path d="M12 5v14M5 12h14" /> : <path d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h6v6h-6z" />}
        </svg>
      </button>
    </div>
  );
}

/* ---------- Hitung mundur PPDB ---------- */

function diff(target: number) {
  const t = Math.max(0, target - Date.now());
  return {
    d: Math.floor(t / 86400000),
    h: Math.floor((t / 3600000) % 24),
    m: Math.floor((t / 60000) % 60),
    s: Math.floor((t / 1000) % 60),
    done: t === 0,
  };
}

export function CountdownPPDB({
  deadline,
  href = "/ppdb",
  label = "Penerimaan Murid Baru Madrasah",
  badges = [],
}: {
  deadline?: string;
  href?: string;
  label?: string;
  badges?: string[];
}) {
  const target = deadline ? new Date(deadline).getTime() : NaN;
  const valid = Number.isFinite(target);
  const [t, setT] = useState(() => (valid ? diff(target) : null));

  useEffect(() => {
    if (!valid) return;
    // Hitung-mundur: satu tick awal lalu interval per detik. Nilai bergantung
    // pada waktu "sekarang" yang hanya bermakna di client.
    // eslint-disable-next-line react-hooks/set-state-in-effect
    setT(diff(target));
    const id = setInterval(() => setT(diff(target)), 1000);
    return () => clearInterval(id);
  }, [target, valid]);

  const cells = t && !t.done
    ? [
        { n: t.d, l: "Hari" },
        { n: t.h, l: "Jam" },
        { n: t.m, l: "Menit" },
        { n: t.s, l: "Detik" },
      ]
    : [];

  return (
    <section className="edge-gradient border-b border-border bg-surface-muted">
      <div className="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
        <div className="flex flex-col items-center gap-3 sm:flex-row sm:justify-between">
          <div className="flex items-center gap-3">
            <span className="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-light text-brand-dark">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                <path d="M8 2v4M16 2v4M3 10h18M5 6h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z" />
              </svg>
            </span>
            <div>
              <p className="text-[0.7rem] font-bold uppercase tracking-[0.16em] text-brand">Informasi PMBM</p>
              <p className="font-extrabold text-foreground">{label}</p>
            </div>
          </div>

          <div className="flex w-full flex-wrap items-center justify-center gap-2 sm:w-auto sm:flex-nowrap">
            {cells.length > 0 && (
              <div className="flex gap-1.5">
                {cells.map((c) => (
                  <div key={c.l} className="min-w-12 rounded-lg border border-border bg-surface px-2 py-1.5 text-center sm:min-w-14">
                    <div className="text-base font-extrabold tabular-nums leading-none text-foreground sm:text-lg">
                      {String(c.n).padStart(2, "0")}
                    </div>
                    <div className="mt-1 text-[0.58rem] font-semibold uppercase tracking-wide text-ink-muted">{c.l}</div>
                  </div>
                ))}
              </div>
            )}
            <Link
              href={href}
              className="btn-glow inline-flex shrink-0 items-center gap-1.5 rounded-xl bg-brand px-4 py-2.5 text-sm font-semibold text-on-brand shadow-brand transition hover:bg-brand-dark"
            >
              Info PMBM <span className="arrow-shift">→</span>
            </Link>
          </div>
        </div>

        {badges.length > 0 && (
          <div className="mt-3 flex flex-wrap items-center justify-center gap-x-5 gap-y-1.5 border-t border-border pt-3 sm:justify-start">
            <span className="text-[0.62rem] font-bold uppercase tracking-[0.16em] text-ink-muted">
              Diakui &amp; Bersertifikat
            </span>
            {badges.map((b) => (
              <span key={b} className="inline-flex items-center gap-1.5 text-xs font-semibold text-ink-soft">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.8" strokeLinecap="round" strokeLinejoin="round" className="text-brand">
                  <path d="M20 6 9 17l-5-5" />
                </svg>
                {b}
              </span>
            ))}
          </div>
        )}
      </div>
    </section>
  );
}

/* ============================================================
   Paket B — Interaksi konten
   ============================================================ */

/* ---------- Lightbox galeri ---------- */

type Photo = { url: string; caption: string; type?: "image" | "video" };

function ytId(url: string): string | null {
  const m = url.match(/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([\w-]{11})/);
  return m ? m[1] : null;
}
function thumbFor(p: Photo): string {
  if (p.type !== "video") return p.url;
  const id = ytId(p.url);
  return id ? `https://img.youtube.com/vi/${id}/hqdefault.jpg` : "";
}

export function GalleryLightbox({ photos }: { photos: Photo[] }) {
  const [idx, setIdx] = useState<number | null>(null);
  const open = idx !== null;

  const go = useCallback(
    (d: number) => setIdx((i) => (i === null ? i : (i + d + photos.length) % photos.length)),
    [photos.length],
  );

  useEffect(() => {
    if (!open) return;
    document.body.style.overflow = "hidden";
    const onKey = (e: KeyboardEvent) => {
      if (e.key === "Escape") setIdx(null);
      if (e.key === "ArrowRight") go(1);
      if (e.key === "ArrowLeft") go(-1);
    };
    document.addEventListener("keydown", onKey);
    return () => {
      document.body.style.overflow = "";
      document.removeEventListener("keydown", onKey);
    };
  }, [open, go]);

  return (
    <>
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
        {photos.map((p, i) => (
          <button
            key={`${p.url}-${i}`}
            type="button"
            onClick={() => setIdx(i)}
            className={`group card card-hover relative block h-full overflow-hidden ${
              i === 0 || i === 5 ? "col-span-2 row-span-2" : ""
            }`}
          >
            <div className={`relative ${i === 0 || i === 5 ? "aspect-square" : "aspect-4/3"}`}>
              {thumbFor(p) ? (
                // eslint-disable-next-line @next/next/no-img-element
                <img
                  src={thumbFor(p)}
                  alt={p.caption}
                  loading="lazy"
                  className="absolute inset-0 h-full w-full object-cover transition duration-700 group-hover:scale-105"
                />
              ) : (
                <div className="media-fallback absolute inset-0" />
              )}
              <span className="absolute inset-0 bg-linear-to-t from-black/60 to-transparent opacity-0 transition group-hover:opacity-100" />
              <span className="absolute inset-x-3 bottom-3 line-clamp-1 text-left text-xs font-semibold text-white opacity-0 transition group-hover:opacity-100">
                {p.caption}
              </span>
              {p.type === "video" ? (
                <span className="absolute inset-0 grid place-items-center">
                  <span className="grid h-12 w-12 place-items-center rounded-full bg-black/55 text-white backdrop-blur transition group-hover:scale-110">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z" /></svg>
                  </span>
                </span>
              ) : (
                <span className="absolute right-2 top-2 grid h-7 w-7 place-items-center rounded-full bg-black/45 text-white opacity-0 backdrop-blur transition group-hover:opacity-100">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.2" strokeLinecap="round">
                    <path d="M15 3h6v6M9 21H3v-6M21 3l-7 7M3 21l7-7" />
                  </svg>
                </span>
              )}
            </div>
          </button>
        ))}
      </div>

      {open && idx !== null && (
        <div
          className="fixed inset-0 z-[70] flex items-center justify-center bg-black/85 p-4 backdrop-blur-sm"
          onClick={() => setIdx(null)}
          role="dialog"
          aria-modal="true"
        >
          <button type="button" aria-label="Tutup" className="absolute right-4 top-4 grid h-11 w-11 place-items-center rounded-full bg-white/10 text-white transition hover:bg-white/20">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round"><path d="M6 6l12 12M18 6 6 18" /></svg>
          </button>
          <button
            type="button"
            aria-label="Sebelumnya"
            onClick={(e) => { e.stopPropagation(); go(-1); }}
            className="absolute left-3 grid h-11 w-11 place-items-center rounded-full bg-white/10 text-white transition hover:bg-white/20 sm:left-6"
          >
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round"><path d="M15 18l-6-6 6-6" /></svg>
          </button>
          <button
            type="button"
            aria-label="Berikutnya"
            onClick={(e) => { e.stopPropagation(); go(1); }}
            className="absolute right-3 grid h-11 w-11 place-items-center rounded-full bg-white/10 text-white transition hover:bg-white/20 sm:right-6"
          >
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round"><path d="M9 6l6 6-6 6" /></svg>
          </button>

          <figure className="max-h-full w-full max-w-4xl" onClick={(e) => e.stopPropagation()}>
            {photos[idx].type === "video" ? (
              ytId(photos[idx].url) ? (
                <div className="relative mx-auto aspect-video w-full overflow-hidden rounded-xl">
                  <iframe
                    src={`https://www.youtube.com/embed/${ytId(photos[idx].url)}?autoplay=1`}
                    title={photos[idx].caption}
                    allow="autoplay; encrypted-media; picture-in-picture"
                    allowFullScreen
                    className="absolute inset-0 h-full w-full"
                  />
                </div>
              ) : (
                <video src={photos[idx].url} controls autoPlay className="mx-auto max-h-[78vh] w-auto rounded-xl" />
              )
            ) : (
              // eslint-disable-next-line @next/next/no-img-element
              <img src={photos[idx].url} alt={photos[idx].caption} className="mx-auto max-h-[78vh] w-auto rounded-xl object-contain" />
            )}
            <figcaption className="mt-3 text-center text-sm text-white/80">
              {photos[idx].caption}
              <span className="ml-2 text-white/50">{idx + 1} / {photos.length}</span>
            </figcaption>
          </figure>
        </div>
      )}
    </>
  );
}

/* ---------- Tambah ke kalender (.ics) ---------- */

function icsDate(v: string) {
  const d = new Date(v);
  if (Number.isNaN(d.getTime())) return "";
  return d.toISOString().replace(/[-:]/g, "").replace(/\.\d{3}/, "");
}

export function AddToCalendar({
  title,
  start,
  end,
  location,
  description,
}: {
  title: string;
  start: string;
  end?: string | null;
  location?: string | null;
  description?: string | null;
}) {
  const dtStart = icsDate(start);
  if (!dtStart) return null;
  const dtEnd = end ? icsDate(end) : icsDate(new Date(new Date(start).getTime() + 3600000).toISOString());

  const ics = [
    "BEGIN:VCALENDAR",
    "VERSION:2.0",
    "PRODID:-//MTsN 1 Kota Malang//Agenda//ID",
    "BEGIN:VEVENT",
    `UID:${dtStart}-${hash(title)}@mtsn1`,
    `DTSTAMP:${icsDate(new Date().toISOString())}`,
    `DTSTART:${dtStart}`,
    `DTEND:${dtEnd}`,
    `SUMMARY:${esc(title)}`,
    location ? `LOCATION:${esc(location)}` : "",
    description ? `DESCRIPTION:${esc(description)}` : "",
    "END:VEVENT",
    "END:VCALENDAR",
  ]
    .filter(Boolean)
    .join("\r\n");

  const href = `data:text/calendar;charset=utf-8,${encodeURIComponent(ics)}`;
  const file = `${title.replace(/[^\w\s-]/g, "").trim().slice(0, 40) || "agenda"}.ics`;

  return (
    <a
      href={href}
      download={file}
      className="inline-flex shrink-0 items-center gap-1.5 rounded-lg border border-border bg-surface px-2.5 py-1.5 text-[0.7rem] font-semibold text-brand transition hover:border-brand hover:bg-brand-light"
      title="Tambahkan ke kalender"
    >
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M8 2v4M16 2v4M3 10h18M5 6h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2zM12 12v6M9 15h6" />
      </svg>
      Kalender
    </a>
  );
}

function esc(s: string) {
  return s.replace(/([,;\\])/g, "\\$1").replace(/\n/g, "\\n");
}

/* ---------- Tombol bagikan ---------- */

const subscribeToLocation = (onChange: () => void) => {
  window.addEventListener("popstate", onChange);
  return () => window.removeEventListener("popstate", onChange);
};

export function ShareButtons({ title }: { title: string }) {
  const [copied, setCopied] = useState(false);
  // Baca URL saat ini lewat store eksternal, bukan setState di useEffect —
  // aman untuk SSR (snapshot server "") dan bebas aturan set-state-in-effect.
  const url = useSyncExternalStore(
    subscribeToLocation,
    () => window.location.href,
    () => "",
  );

  const enc = encodeURIComponent(url);
  const encT = encodeURIComponent(title);

  const links = [
    { label: "WhatsApp", href: `https://wa.me/?text=${encT}%20${enc}`, path: "M12 2a10 10 0 0 0-8.5 15.3L2 22l4.8-1.5A10 10 0 1 0 12 2z", fill: true },
    { label: "Facebook", href: `https://www.facebook.com/sharer/sharer.php?u=${enc}`, path: "M13.5 21v-8h2.7l.4-3.1h-3.1V7.9c0-.9.25-1.5 1.5-1.5h1.7V3.6C17.5 3.55 16.5 3.5 15.4 3.5c-2.3 0-3.9 1.4-3.9 4v2.9H8.8V14h2.7v7z", fill: true },
    { label: "X", href: `https://twitter.com/intent/tweet?text=${encT}&url=${enc}`, path: "M4 4l7 9.5L4 20h2l6-6.5L17 20h3l-7.5-10L20 4h-2l-5.5 6L8 4z", fill: true },
  ];

  return (
    <div className="flex flex-wrap items-center gap-2">
      <span className="text-xs font-bold uppercase tracking-wide text-ink-muted">Bagikan</span>
      {links.map((l) => (
        <a
          key={l.label}
          href={l.href}
          target="_blank"
          rel="noreferrer"
          aria-label={`Bagikan ke ${l.label}`}
          className="grid h-9 w-9 place-items-center rounded-lg border border-border text-ink-muted transition hover:-translate-y-0.5 hover:border-brand hover:text-brand"
        >
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d={l.path} /></svg>
        </a>
      ))}
      <button
        type="button"
        onClick={async () => {
          try {
            await navigator.clipboard.writeText(url);
            setCopied(true);
            setTimeout(() => setCopied(false), 1800);
          } catch {
            /* abaikan */
          }
        }}
        className="inline-flex items-center gap-1.5 rounded-lg border border-border px-3 py-2 text-xs font-semibold text-ink-soft transition hover:border-brand hover:text-brand"
      >
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
          {copied ? <path d="M20 6 9 17l-5-5" /> : <path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1" />}
        </svg>
        {copied ? "Tersalin!" : "Salin tautan"}
      </button>
    </div>
  );
}

/* ============================================================
   Paket D — Visual & animasi
   ============================================================ */

/* ---------- Pembatas gelombang ---------- */

export function WaveDivider({
  tone = "background",
}: {
  tone?: "background" | "surface" | "surface-muted";
}) {
  const fill =
    tone === "surface"
      ? "var(--surface)"
      : tone === "surface-muted"
        ? "var(--surface-muted)"
        : "var(--background)";
  return (
    <span aria-hidden className="pointer-events-none absolute inset-x-0 top-0 z-[2] block leading-[0]">
      <svg viewBox="0 0 1440 60" preserveAspectRatio="none" className="h-[38px] w-full sm:h-[56px]">
        <path
          fill={fill}
          d="M0 0h1440v18c-140 26-320 32-520 16C700 34 520 8 320 10 190 11 84 22 0 34V0z"
        />
      </svg>
    </span>
  );
}

/* ---------- Glow mengikuti kursor (hero) ---------- */

export function PointerGlow({ className = "" }: { className?: string }) {
  const ref = useRef<HTMLSpanElement | null>(null);

  useEffect(() => {
    const el = ref.current;
    if (!el) return;
    const parent = el.parentElement;
    if (!parent) return;
    if (window.matchMedia("(pointer: coarse)").matches) return;

    let raf = 0;
    const onMove = (e: MouseEvent) => {
      const r = parent.getBoundingClientRect();
      const x = ((e.clientX - r.left) / r.width) * 100;
      const y = ((e.clientY - r.top) / r.height) * 100;
      if (!raf)
        raf = requestAnimationFrame(() => {
          raf = 0;
          el.style.setProperty("--gx", `${x}%`);
          el.style.setProperty("--gy", `${y}%`);
          el.style.opacity = "1";
        });
    };
    const onLeave = () => (el.style.opacity = "0");
    parent.addEventListener("mousemove", onMove);
    parent.addEventListener("mouseleave", onLeave);
    return () => {
      parent.removeEventListener("mousemove", onMove);
      parent.removeEventListener("mouseleave", onLeave);
      if (raf) cancelAnimationFrame(raf);
    };
  }, []);

  return <span ref={ref} aria-hidden className={`pointer-glow ${className}`} />;
}

/* ---------- Tombol magnetik ---------- */

export function Magnetic({
  children,
  strength = 14,
  className = "",
}: {
  children: ReactNode;
  strength?: number;
  className?: string;
}) {
  const ref = useRef<HTMLSpanElement | null>(null);

  function onMove(e: React.MouseEvent<HTMLSpanElement>) {
    const el = ref.current;
    if (!el) return;
    if (window.matchMedia("(pointer: coarse)").matches) return;
    const r = el.getBoundingClientRect();
    const x = (e.clientX - r.left - r.width / 2) / (r.width / 2);
    const y = (e.clientY - r.top - r.height / 2) / (r.height / 2);
    el.style.transform = `translate(${x * strength}px, ${y * strength}px)`;
  }
  function reset() {
    if (ref.current) ref.current.style.transform = "translate(0,0)";
  }

  return (
    <span
      ref={ref}
      onMouseMove={onMove}
      onMouseLeave={reset}
      className={`inline-block transition-transform duration-300 ease-out will-change-transform ${className}`}
    >
      {children}
    </span>
  );
}

/* ---------- Angka gaya odometer ---------- */

export function Odometer({ value, className = "" }: { value: string; className?: string }) {
  const ref = useRef<HTMLSpanElement | null>(null);
  const [run, setRun] = useState(false);

  useEffect(() => {
    const el = ref.current;
    // Jalankan animasi tanpa observer bila API tak ada atau user minta
    // reduced-motion — keduanya hanya diketahui di client.
    if (!el || !("IntersectionObserver" in window)) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setRun(true);
      return;
    }
    if (window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setRun(true);
      return;
    }
    const io = new IntersectionObserver((ents) => {
      if (ents[0].isIntersecting) {
        setRun(true);
        io.disconnect();
      }
    });
    io.observe(el);
    return () => io.disconnect();
  }, []);

  const chars = value.split("");

  return (
    <span ref={ref} className={`odometer ${className}`} aria-label={value}>
      {chars.map((c, i) => {
        if (!/[0-9]/.test(c)) {
          return (
            <span key={i} aria-hidden className="odometer-sep">
              {c}
            </span>
          );
        }
        const n = Number(c);
        return (
          <span key={i} aria-hidden className="odometer-digit">
            <span
              className="odometer-track"
              style={{
                transform: run ? `translateY(-${n * 10}%)` : "translateY(0)",
                transitionDelay: `${i * 90}ms`,
              }}
            >
              {Array.from({ length: 10 }, (_, d) => (
                <span key={d}>{d}</span>
              ))}
            </span>
          </span>
        );
      })}
    </span>
  );
}
