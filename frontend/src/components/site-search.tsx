"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { readRecentSearches, pushRecentSearch } from "@/components/features-more";

const POPULAR = ["PMBM", "Ekstrakurikuler", "Prestasi", "Jadwal", "Pramuka", "Beasiswa"];

/** Bentuk hasil dari endpoint /search (lihat backend SearchController). */
type SearchHit = { title: string; excerpt: string | null; href: string; meta: string | null };
type SearchGroup = { label: string; href: string; items: SearchHit[] };

const BASE = process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8000/api/v1";

/** Halaman & layanan statis yang ikut dicari (tanpa perlu API). */
const QUICK_LINKS: { label: string; href: string; hint: string }[] = [
  { label: "Profil Madrasah", href: "/profil", hint: "profil sejarah visi misi struktur organisasi akreditasi sambutan kepala" },
  { label: "Guru & Tenaga Kependidikan", href: "/guru", hint: "guru tendik pendidik staf pegawai civitas akademik" },
  { label: "Prestasi Madrasah", href: "/prestasi", hint: "prestasi juara lomba penghargaan olimpiade" },
  { label: "Berita & Pengumuman", href: "/berita", hint: "berita kabar pengumuman informasi akademik" },
  { label: "Agenda Kegiatan", href: "/agenda", hint: "agenda jadwal acara kalender kegiatan" },
  { label: "Ekstrakurikuler", href: "/ekstrakurikuler", hint: "ekstrakurikuler ekskul pramuka pmr osis" },
  { label: "Galeri Foto", href: "/galeri", hint: "galeri foto video dokumentasi album" },
  { label: "Akademik", href: "/akademik", hint: "akademik kurikulum penilaian rapor rdm bimbingan konseling bk pembelajaran" },
  { label: "Program Ma'had (Asrama)", href: "/mahad", hint: "mahad asrama tahfidz pembinaan santri musyrif jadwal pesantren berma'had" },
  { label: "Layanan Publik", href: "/layanan", hint: "layanan standar sop ptsp maklumat pelayanan e-repository ppid" },
  { label: "Dokumen & Unduhan", href: "/dokumen", hint: "dokumen unduhan formulir berkas panduan surat edaran" },
  { label: "Area Zona Integritas", href: "/area-zi", hint: "zona integritas wbk wbbm reformasi birokrasi gratifikasi wbs anti korupsi" },
  { label: "PMBM", href: "/ppdb", hint: "ppdb pmbm pendaftaran daftar masuk peserta didik baru murid" },
  { label: "Kontak & Pengaduan", href: "/kontak", hint: "kontak alamat telepon email lokasi hubungi pengaduan survei kepuasan" },
];

export function SiteSearch({ variant = "icon" }: { variant?: "icon" | "block" }) {
  const [open, setOpen] = useState(false);
  const [q, setQ] = useState("");
  const [groups, setGroups] = useState<SearchGroup[]>([]);
  const [loading, setLoading] = useState(false);
  const router = useRouter();
  const inputRef = useRef<HTMLInputElement | null>(null);
  const abortRef = useRef<AbortController | null>(null);

  const close = useCallback(() => {
    setOpen(false);
    setQ("");
    setGroups([]);
  }, []);

  // Buka lewat keyboard: "/" atau Ctrl/Cmd-K.
  useEffect(() => {
    const onKey = (e: KeyboardEvent) => {
      const t = e.target as HTMLElement | null;
      const typing = t && (t.tagName === "INPUT" || t.tagName === "TEXTAREA" || t.isContentEditable);
      if ((e.key === "k" && (e.metaKey || e.ctrlKey)) || (e.key === "/" && !typing)) {
        e.preventDefault();
        setOpen(true);
      }
      if (e.key === "Escape") close();
    };
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, [close]);

  const [recent, setRecent] = useState<string[]>([]);

  useEffect(() => {
    if (!open) return;
    // Riwayat pencarian dibaca dari localStorage saat modal dibuka.
    // eslint-disable-next-line react-hooks/set-state-in-effect
    setRecent(readRecentSearches());
    document.body.style.overflow = "hidden";
    const id = window.setTimeout(() => inputRef.current?.focus(), 40);
    return () => {
      document.body.style.overflow = "";
      window.clearTimeout(id);
    };
  }, [open]);

  // Ambil hasil menyeluruh yang cocok (debounce) — lintas tipe konten.
  useEffect(() => {
    const term = q.trim();
    const id = window.setTimeout(async () => {
      if (term.length < 2) {
        setGroups([]);
        setLoading(false);
        return;
      }
      setLoading(true);
      abortRef.current?.abort();
      const ac = new AbortController();
      abortRef.current = ac;
      try {
        const url = new URL(`${BASE}/search`);
        url.searchParams.set("q", term);
        const res = await fetch(url, { signal: ac.signal });
        const body = await res.json();
        setGroups(Array.isArray(body?.groups) ? body.groups : []);
      } catch {
        /* dibatalkan / gagal */
      } finally {
        setLoading(false);
      }
    }, 250);
    return () => window.clearTimeout(id);
  }, [q]);

  const links = useMemo(() => {
    const term = q.trim().toLowerCase();
    if (!term) return QUICK_LINKS.slice(0, 6);
    return QUICK_LINKS.filter(
      (l) => l.label.toLowerCase().includes(term) || l.hint.includes(term),
    ).slice(0, 6);
  }, [q]);

  function submit(e: React.FormEvent) {
    e.preventDefault();
    const term = q.trim();
    if (!term) return;
    pushRecentSearch(term);
    close();
    router.push(`/pencarian?q=${encodeURIComponent(term)}`);
  }

  function runTerm(term: string) {
    pushRecentSearch(term);
    close();
    router.push(`/pencarian?q=${encodeURIComponent(term)}`);
  }

  const Trigger =
    variant === "block" ? (
      <button
        type="button"
        onClick={() => setOpen(true)}
        className="flex w-full items-center gap-3 rounded-xl border border-border bg-surface px-4 py-3 text-left text-sm text-ink-muted transition hover:border-brand"
      >
        <SearchIcon />
        <span className="flex-1">Cari berita, halaman, dokumen…</span>
        <kbd className="hidden rounded border border-border px-1.5 py-0.5 text-[0.65rem] font-semibold sm:inline">/</kbd>
      </button>
    ) : (
      <button
        type="button"
        onClick={() => setOpen(true)}
        aria-label="Cari"
        className="grid h-10 w-10 place-items-center rounded-lg border border-border text-ink-soft transition hover:border-brand hover:text-brand"
      >
        <SearchIcon />
      </button>
    );

  return (
    <>
      {Trigger}

      {open && (
        <div className="fixed inset-0 z-[80]">
          <div
            className="absolute inset-0 bg-black/45 backdrop-blur-sm"
            onClick={close}
            aria-hidden
          />
          <div
            role="dialog"
            aria-modal="true"
            aria-label="Pencarian situs"
            className="pop-in absolute inset-x-0 top-0 mx-auto mt-[8vh] w-[92%] max-w-xl overflow-hidden rounded-2xl border border-border bg-surface shadow-lg"
          >
            <form onSubmit={submit} className="flex items-center gap-3 border-b border-border px-4">
              <SearchIcon />
              <input
                ref={inputRef}
                type="search"
                value={q}
                onChange={(e) => setQ(e.target.value)}
                placeholder="Cari berita, halaman, dokumen…"
                aria-label="Kata kunci pencarian"
                className="min-h-14 flex-1 bg-transparent text-sm outline-none"
              />
              <button type="button" onClick={close} className="text-xs font-semibold text-ink-muted">
                Esc
              </button>
            </form>

            <div className="max-h-[60vh] overflow-y-auto p-2">
              {!q.trim() && (recent.length > 0 || POPULAR.length > 0) && (
                <div className="px-3 pb-2 pt-2">
                  {recent.length > 0 && (
                    <>
                      <p className="pb-1.5 text-[0.7rem] font-bold uppercase tracking-wide text-ink-muted">
                        Terakhir dicari
                      </p>
                      <div className="mb-3 flex flex-wrap gap-1.5">
                        {recent.map((r) => (
                          <button
                            key={r}
                            type="button"
                            onClick={() => runTerm(r)}
                            className="inline-flex items-center gap-1.5 rounded-full border border-border bg-surface px-3 py-1 text-xs font-medium text-ink-soft transition hover:border-brand hover:text-brand"
                          >
                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round"><path d="M12 8v4l3 2M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z" /></svg>
                            {r}
                          </button>
                        ))}
                      </div>
                    </>
                  )}
                  <p className="pb-1.5 text-[0.7rem] font-bold uppercase tracking-wide text-ink-muted">
                    Pencarian populer
                  </p>
                  <div className="flex flex-wrap gap-1.5">
                    {POPULAR.map((p) => (
                      <button
                        key={p}
                        type="button"
                        onClick={() => runTerm(p)}
                        className="rounded-full bg-brand-light px-3 py-1 text-xs font-semibold text-brand-dark transition hover:bg-brand hover:text-on-brand"
                      >
                        {p}
                      </button>
                    ))}
                  </div>
                </div>
              )}
              {links.length > 0 && (
                <>
                  <p className="px-3 pb-1 pt-2 text-[0.7rem] font-bold uppercase tracking-wide text-ink-muted">
                    Halaman &amp; layanan
                  </p>
                  {links.map((l) => (
                    <Link
                      key={l.href}
                      href={l.href}
                      onClick={close}
                      className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-ink-soft transition hover:bg-surface-muted hover:text-foreground"
                    >
                      <span className="h-1.5 w-1.5 rounded-full bg-brand" />
                      {l.label}
                    </Link>
                  ))}
                </>
              )}

              {q.trim().length >= 2 && (
                <>
                  {loading && (
                    <div className="space-y-2 px-3 py-3">
                      {[0, 1, 2].map((i) => (
                        <div key={i} className="h-4 w-3/4 animate-pulse rounded bg-surface-muted" />
                      ))}
                    </div>
                  )}
                  {!loading && groups.length === 0 && (
                    <p className="px-3 py-3 text-sm text-ink-muted">Tidak ada hasil yang cocok.</p>
                  )}
                  {!loading &&
                    groups.map((group) => (
                      <div key={group.label}>
                        <p className="px-3 pb-1 pt-3 text-[0.7rem] font-bold uppercase tracking-wide text-ink-muted">
                          {group.label}
                        </p>
                        {group.items.slice(0, 4).map((hit, i) => (
                          <Link
                            key={`${hit.href}-${i}`}
                            href={hit.href}
                            onClick={close}
                            className="block rounded-lg px-3 py-2.5 text-sm transition hover:bg-surface-muted"
                          >
                            <span className="font-semibold text-foreground">{hit.title}</span>
                            {hit.meta && (
                              <span className="ml-2 text-[0.7rem] font-semibold uppercase tracking-wide text-brand">
                                {hit.meta}
                              </span>
                            )}
                          </Link>
                        ))}
                      </div>
                    ))}
                  {!loading && groups.length > 0 && (
                    <button
                      onClick={submit}
                      className="mt-1 flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-bold text-brand transition hover:bg-brand-light"
                    >
                      Lihat semua hasil untuk &ldquo;{q.trim()}&rdquo; →
                    </button>
                  )}
                </>
              )}
            </div>
          </div>
        </div>
      )}
    </>
  );
}

function SearchIcon() {
  return (
    <svg
      width="18"
      height="18"
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="2"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden
    >
      <circle cx="11" cy="11" r="7" />
      <path d="m21 21-4.3-4.3" />
    </svg>
  );
}
