"use client";

import Image from "@/components/media-image";
import { useEffect, useMemo, useRef, useState } from "react";
import { Reveal } from "@/components/motion";
import type { Achievement, DocumentItem, Teacher } from "@/lib/types";

/* ============================================================
   Bundle L — Prestasi: filter tahun / tingkat + ringkasan
   ============================================================ */

export function PrestasiExplorer({ items }: { items: Achievement[] }) {
  const [level, setLevel] = useState<string>("");
  const [year, setYear] = useState<string>("");

  const levels = useMemo(
    () => Array.from(new Set(items.map((a) => a.level).filter(Boolean) as string[])),
    [items],
  );
  const years = useMemo(
    () =>
      Array.from(new Set(items.map((a) => a.year).filter(Boolean) as number[])).sort(
        (a, b) => b - a,
      ),
    [items],
  );

  const filtered = items.filter(
    (a) => (!level || a.level === level) && (!year || String(a.year) === year),
  );

  const chip = (active: boolean) =>
    `rounded-full border px-3.5 py-1.5 text-sm font-semibold transition ${
      active
        ? "border-brand bg-brand text-on-brand"
        : "border-border bg-surface text-ink-soft hover:border-brand hover:text-brand"
    }`;

  return (
    <div>
      <div className="mb-6 grid gap-4 rounded-2xl border border-border bg-surface-muted p-4 sm:grid-cols-[auto_1fr] sm:p-5">
        <div className="flex items-center gap-4">
          <div>
            <p className="text-2xl font-extrabold tabular-nums text-brand">{filtered.length}</p>
            <p className="text-[0.65rem] font-bold uppercase tracking-wide text-ink-muted">Prestasi</p>
          </div>
          {levels.slice(0, 4).map((l) => (
            <div key={l} className="hidden sm:block">
              <p className="text-lg font-extrabold tabular-nums text-foreground">
                {items.filter((a) => a.level === l).length}
              </p>
              <p className="text-[0.6rem] font-semibold uppercase tracking-wide text-ink-muted">{l}</p>
            </div>
          ))}
        </div>
        <div className="flex flex-col gap-3 sm:items-end">
          {levels.length > 0 && (
            <div className="flex flex-wrap gap-1.5">
              <button type="button" className={chip(!level)} onClick={() => setLevel("")}>Semua tingkat</button>
              {levels.map((l) => (
                <button key={l} type="button" className={chip(level === l)} onClick={() => setLevel(l)}>
                  {l}
                </button>
              ))}
            </div>
          )}
          {years.length > 0 && (
            <div className="flex flex-wrap gap-1.5">
              <button type="button" className={chip(!year)} onClick={() => setYear("")}>Semua tahun</button>
              {years.map((y) => (
                <button key={y} type="button" className={chip(year === String(y))} onClick={() => setYear(String(y))}>
                  {y}
                </button>
              ))}
            </div>
          )}
        </div>
      </div>

      {filtered.length === 0 ? (
        <p className="rounded-xl border border-dashed border-border-strong bg-surface p-10 text-center text-sm text-ink-muted">
          Tidak ada prestasi pada filter ini.
        </p>
      ) : (
        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {filtered.map((a, i) => (
            <Reveal key={i} delay={(i % 3) * 60} direction="up">
              <article className="group card card-hover flex h-full flex-col overflow-hidden">
                {a.image ? (
                  <div className="relative aspect-[16/10] overflow-hidden bg-surface-muted">
                    <Image src={a.image} alt={a.title} fill className="object-cover transition duration-700 group-hover:scale-105" sizes="33vw" />
                  </div>
                ) : (
                  <div className="grid aspect-[16/10] place-items-center media-fallback text-3xl font-black">
                    {a.title.charAt(0)}
                  </div>
                )}
                <div className="flex flex-1 flex-col p-5">
                  <div className="flex flex-wrap gap-2">
                    {a.level && (
                      <span className="inline-flex items-center rounded-md border border-accent/25 bg-accent-soft px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-[0.06em] text-accent">
                        {a.level}
                      </span>
                    )}
                    {a.year && (
                      <span className="inline-flex items-center rounded-md border border-border-strong bg-surface-muted px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-[0.06em] text-ink-muted">
                        {a.year}
                      </span>
                    )}
                  </div>
                  <h3 className="mt-3 font-bold text-foreground">{a.title}</h3>
                  {a.student_name && <p className="mt-1 text-sm text-ink-muted">{a.student_name}</p>}
                  {a.description && <p className="mt-2 flex-1 text-sm text-ink-soft">{a.description}</p>}
                </div>
              </article>
            </Reveal>
          ))}
        </div>
      )}
    </div>
  );
}

/* ============================================================
   Bundle L — Guru: pencarian + filter jabatan / mapel
   ============================================================ */

export function GuruDirectory({
  teachers,
  groupLabels,
}: {
  teachers: Teacher[];
  groupLabels: Record<string, string>;
}) {
  const [q, setQ] = useState("");
  const [subject, setSubject] = useState("");

  const subjects = useMemo(
    () => Array.from(new Set(teachers.map((t) => t.subject).filter(Boolean) as string[])).sort(),
    [teachers],
  );

  const term = q.trim().toLowerCase();
  const match = (t: Teacher) =>
    (!term ||
      t.name.toLowerCase().includes(term) ||
      (t.position ?? "").toLowerCase().includes(term) ||
      (t.subject ?? "").toLowerCase().includes(term)) &&
    (!subject || t.subject === subject);

  const filtered = teachers.filter(match);
  const groups = ["pimpinan", "guru", "tendik"].filter((g) => filtered.some((t) => t.group === g));

  return (
    <div>
      <div className="mb-8 flex flex-col gap-3">
        <input
          type="search"
          value={q}
          onChange={(e) => setQ(e.target.value)}
          placeholder="Cari nama, jabatan, atau mata pelajaran…"
          className="min-h-11 w-full max-w-md rounded-xl border border-border bg-surface px-4 text-sm shadow-sm outline-none transition focus:border-brand"
        />
        {subjects.length > 1 && (
          <div className="flex flex-wrap gap-1.5">
            <button
              type="button"
              onClick={() => setSubject("")}
              className={`rounded-full border px-3 py-1 text-xs font-semibold transition ${
                !subject ? "border-brand bg-brand text-on-brand" : "border-border text-ink-soft hover:border-brand"
              }`}
            >
              Semua mapel
            </button>
            {subjects.map((s) => (
              <button
                key={s}
                type="button"
                onClick={() => setSubject(s === subject ? "" : s)}
                className={`rounded-full border px-3 py-1 text-xs font-semibold transition ${
                  subject === s ? "border-brand bg-brand text-on-brand" : "border-border text-ink-soft hover:border-brand"
                }`}
              >
                {s}
              </button>
            ))}
          </div>
        )}
      </div>

      {filtered.length === 0 && (
        <p className="rounded-xl border border-dashed border-border-strong bg-surface p-10 text-center text-sm text-ink-muted">
          Tidak ada yang cocok.
        </p>
      )}

      {groups.map((g) => (
        <section key={g} className="mb-12">
          <h2 className="mb-5 flex items-center gap-3 text-lg font-bold text-brand-dark">
            <span className="h-px w-8 bg-brand/40" />
            {groupLabels[g]}
            <span className="text-sm font-semibold text-ink-muted">
              ({filtered.filter((t) => t.group === g).length})
            </span>
          </h2>
          <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {filtered
              .filter((t) => t.group === g)
              .map((t, i) => (
                <Reveal key={i} delay={(i % 4) * 50} direction="up">
                  <div className="group card card-hover h-full p-5 text-center">
                    <div className="mx-auto grid h-40 w-40 place-items-center overflow-hidden rounded-full bg-brand-light text-2xl font-black text-brand ring-4 ring-brand-light/50 transition group-hover:ring-brand/20">
                      {t.photo ? (
                        /*
                          Bingkai lingkaran memangkas 4 sudut foto persegi.
                          `object-center` (default) memusatkan secara geometris,
                          padahal pada pas foto kepala berada di BAGIAN ATAS —
                          sehingga rambut/kepala ikut terpotong. `object-top`
                          menahan sisi atas foto; yang dipangkas kini bagian
                          bawah (dada) yang tidak penting.

                          Ukuran juga dinaikkan ke 160 (bukan 96) supaya cocok
                          dengan wadah h-40 w-40 = 160px — sebelumnya varian 96px
                          diregangkan 1,7x dan tampak buram.
                        */
                        <Image
                          src={t.photo}
                          alt={t.name}
                          width={160}
                          height={160}
                          sizes="160px"
                          quality={90}
                          className="h-full w-full object-cover object-top"
                        />
                      ) : (
                        t.name.charAt(0)
                      )}
                    </div>
                    <p className="mt-3 font-bold text-foreground">{t.name}</p>
                    {t.position && <p className="text-sm text-brand">{t.position}</p>}
                    {t.subject && <p className="text-xs text-ink-muted">{t.subject}</p>}
                  </div>
                </Reveal>
              ))}
          </div>
        </section>
      ))}
    </div>
  );
}

/* ============================================================
   Bundle L — Dokumen: filter kategori + pratinjau PDF
   ============================================================ */

function ext(url: string) {
  const m = url.split("?")[0].match(/\.([a-z0-9]{2,5})$/i);
  return (m?.[1] ?? "file").toUpperCase();
}

export function DokumenList({ docs }: { docs: DocumentItem[] }) {
  const [cat, setCat] = useState("");
  const [preview, setPreview] = useState<DocumentItem | null>(null);

  const cats = useMemo(
    () => Array.from(new Set(docs.map((d) => d.category).filter(Boolean) as string[])),
    [docs],
  );
  const shown = cat ? docs.filter((d) => d.category === cat) : docs;

  useEffect(() => {
    if (!preview) return;
    document.body.style.overflow = "hidden";
    const onKey = (e: KeyboardEvent) => e.key === "Escape" && setPreview(null);
    document.addEventListener("keydown", onKey);
    return () => {
      document.body.style.overflow = "";
      document.removeEventListener("keydown", onKey);
    };
  }, [preview]);

  return (
    <>
      {cats.length > 1 && (
        <div className="mb-6 flex flex-wrap gap-2">
          <button
            type="button"
            onClick={() => setCat("")}
            className={`rounded-full border px-3.5 py-1.5 text-sm font-semibold transition ${
              !cat ? "border-brand bg-brand text-on-brand" : "border-border bg-surface text-ink-soft hover:border-brand"
            }`}
          >
            Semua
          </button>
          {cats.map((c) => (
            <button
              key={c}
              type="button"
              onClick={() => setCat(c === cat ? "" : c)}
              className={`rounded-full border px-3.5 py-1.5 text-sm font-semibold transition ${
                cat === c ? "border-brand bg-brand text-on-brand" : "border-border bg-surface text-ink-soft hover:border-brand"
              }`}
            >
              {c}
            </button>
          ))}
        </div>
      )}

      <ul className="grid gap-4 sm:grid-cols-2">
        {shown.map((d, i) => {
          const isPdf = ext(d.url) === "PDF";
          return (
            <Reveal key={`${d.url}-${i}`} as="li" delay={(i % 2) * 60} direction="up">
              <div className="group card card-hover flex items-center gap-4 p-4">
                <span className="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-brand-light text-[0.6rem] font-black tracking-tight text-brand-dark transition group-hover:bg-brand group-hover:text-on-brand">
                  {ext(d.url)}
                </span>
                <span className="min-w-0 flex-1">
                  <span className="block truncate font-bold text-foreground">{d.title}</span>
                  <span className="mt-0.5 block text-xs text-ink-muted">
                    {[d.category, d.size, d.downloads ? `${d.downloads}× diunduh` : null]
                      .filter(Boolean)
                      .join(" · ")}
                  </span>
                </span>
                <div className="flex shrink-0 items-center gap-1">
                  {isPdf && (
                    <button
                      type="button"
                      onClick={() => setPreview(d)}
                      aria-label="Pratinjau dokumen"
                      className="grid h-9 w-9 place-items-center rounded-lg border border-border text-ink-muted transition hover:border-brand hover:text-brand"
                    >
                      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z" /><circle cx="12" cy="12" r="3" /></svg>
                    </button>
                  )}
                  <a
                    href={d.download_url}
                    target="_blank"
                    rel="noreferrer"
                    aria-label="Unduh dokumen"
                    className="grid h-9 w-9 place-items-center rounded-lg border border-border text-brand transition hover:border-brand hover:bg-brand-light"
                  >
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 3v12M7 10l5 5 5-5M5 21h14" /></svg>
                  </a>
                </div>
              </div>
            </Reveal>
          );
        })}
      </ul>

      {preview && (
        <div className="fixed inset-0 z-[70] flex flex-col bg-black/85 p-3 backdrop-blur-sm sm:p-6" role="dialog" aria-modal="true">
          <div className="mb-2 flex items-center justify-between gap-3 text-white">
            <p className="truncate text-sm font-semibold">{preview.title}</p>
            <div className="flex shrink-0 gap-2">
              <a href={preview.download_url} target="_blank" rel="noreferrer" className="rounded-lg bg-white/10 px-3 py-1.5 text-xs font-semibold transition hover:bg-white/20">
                Unduh
              </a>
              <button type="button" onClick={() => setPreview(null)} className="rounded-lg bg-white/10 px-3 py-1.5 text-xs font-semibold transition hover:bg-white/20">
                Tutup
              </button>
            </div>
          </div>
          <iframe src={preview.url} title={preview.title} className="flex-1 rounded-lg bg-white" />
        </div>
      )}
    </>
  );
}

/* ============================================================
   Bundle M — Daftar isi otomatis (TOC) dengan scroll-spy
   ============================================================ */

export function TableOfContents({ selector = ".prose-content" }: { selector?: string }) {
  const [items, setItems] = useState<{ id: string; text: string; level: number }[]>([]);
  const [active, setActive] = useState("");

  useEffect(() => {
    const root = document.querySelector<HTMLElement>(selector);
    if (!root) return;
    const heads = Array.from(root.querySelectorAll<HTMLElement>("h2, h3"));
    const list = heads.map((h, i) => {
      if (!h.id) h.id = `sec-${i}-${(h.textContent ?? "").toLowerCase().replace(/[^a-z0-9]+/g, "-").slice(0, 40)}`;
      h.style.scrollMarginTop = "6rem";
      return { id: h.id, text: h.textContent ?? `Bagian ${i + 1}`, level: h.tagName === "H3" ? 2 : 1 };
    });
    // Daftar heading dipindai langsung dari DOM artikel yang sudah ter-render.
    // eslint-disable-next-line react-hooks/set-state-in-effect
    setItems(list);
    if (list[0]) setActive(list[0].id);

    if (!("IntersectionObserver" in window)) return;
    const io = new IntersectionObserver(
      (ents) => ents.forEach((e) => e.isIntersecting && setActive((e.target as HTMLElement).id)),
      { rootMargin: "-25% 0px -65% 0px" },
    );
    heads.forEach((h) => io.observe(h));
    return () => io.disconnect();
  }, [selector]);

  if (items.length < 3) return null;

  return (
    <nav className="toc rounded-xl border border-border bg-surface p-4 text-sm">
      <p className="mb-2 text-[0.7rem] font-bold uppercase tracking-wide text-ink-muted">Daftar Isi</p>
      <ul className="space-y-1">
        {items.map((it) => (
          <li key={it.id} className={it.level === 2 ? "pl-3" : ""}>
            <a
              href={`#${it.id}`}
              className={`block rounded-md px-2 py-1 transition ${
                active === it.id
                  ? "bg-brand-light font-semibold text-brand-dark"
                  : "text-ink-muted hover:text-foreground"
              }`}
            >
              {it.text}
            </a>
          </li>
        ))}
      </ul>
    </nav>
  );
}

/* ============================================================
   Bundle O — Statistik bar animasi
   ============================================================ */

export function StatBars({
  items,
}: {
  items: { label: string; display: string; ratio: number }[];
}) {
  const ref = useRef<HTMLDivElement | null>(null);
  const [run, setRun] = useState(false);

  useEffect(() => {
    const el = ref.current;
    if (!el || !("IntersectionObserver" in window)) {
      setRun(true);
      return;
    }
    const io = new IntersectionObserver((e) => {
      if (e[0].isIntersecting) {
        setRun(true);
        io.disconnect();
      }
    });
    io.observe(el);
    return () => io.disconnect();
  }, []);

  return (
    <div ref={ref} className="grid gap-6 sm:grid-cols-2">
      {items.map((it, i) => (
        <div key={it.label} className="border-t border-white/15 pt-5">
          <span className="block text-[0.7rem] font-bold uppercase tracking-[0.14em] text-white/55">
            {it.label}
          </span>
          <span className="num-xl mt-1 block">{it.display}</span>
          <div className="mt-3 h-2.5 overflow-hidden rounded-full bg-white/15">
            <div
              className="h-full rounded-full shadow-[0_0_12px_rgba(255,255,255,0.25)]"
              style={{
                width: run ? `${Math.max(8, Math.round(it.ratio * 100))}%` : "0%",
                transition: `width 1.2s cubic-bezier(.16,1,.3,1) ${i * 120}ms`,
                backgroundImage:
                  "linear-gradient(90deg, color-mix(in oklab, var(--brand) 70%, #fff), var(--accent))",
              }}
            />
          </div>
        </div>
      ))}
    </div>
  );
}
