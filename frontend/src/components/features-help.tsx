"use client";

import Link from "next/link";
import { useEffect, useRef, useState } from "react";

/* ============================================================
   Chatbot FAQ mengambang (rule-based, tanpa backend)
   ============================================================ */

type QA = { q: string; a: string; href?: string; hrefLabel?: string };

type BotProps = {
  ppdbUrl?: string;
  whatsapp?: string;
  phone?: string;
  address?: string;
};

export function HelpBot({ ppdbUrl = "/ppdb", whatsapp, phone, address }: BotProps) {
  const [open, setOpen] = useState(false);
  const [log, setLog] = useState<{ from: "bot" | "user"; text: string; href?: string; hrefLabel?: string }[]>([]);
  const bodyRef = useRef<HTMLDivElement | null>(null);
  const wa = whatsapp ? whatsapp.replace(/[^0-9]/g, "") : "";

  const faqs: QA[] = [
    {
      q: "Kapan PMBM dibuka?",
      a: "Jadwal PMBM mengikuti kalender resmi Kementerian Agama. Tanggal, jalur, dan kuota diumumkan di halaman PMBM serta media sosial madrasah.",
      href: ppdbUrl,
      hrefLabel: "Buka halaman PMBM",
    },
    {
      q: "Bagaimana cara mendaftar?",
      a: "Pendaftaran dilakukan daring melalui portal PMBM. Siapkan kartu keluarga, akta kelahiran, rapor, pas foto, dan dokumen prestasi (bila ada).",
      href: ppdbUrl,
      hrefLabel: "Mulai pendaftaran",
    },
    {
      q: "Apakah ada jalur prestasi?",
      a: "Ya, tersedia jalur prestasi akademik dan non-akademik. Persyaratan mengikuti petunjuk teknis PMBM tahun berjalan.",
      href: ppdbUrl,
    },
    {
      q: "Di mana lokasi madrasah?",
      a: address
        ? `Alamat kami: ${address}.`
        : "Madrasah berlokasi di Kota Malang, Jawa Timur.",
      href: `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(address || "MTsN 1 Kota Malang")}`,
      hrefLabel: "Lihat di Google Maps",
    },
    {
      q: "Bagaimana menghubungi madrasah?",
      a: wa
        ? "Anda bisa menghubungi kami lewat WhatsApp, telepon, atau halaman Kontak pada jam kerja (Senin–Jumat)."
        : "Silakan hubungi kami melalui halaman Kontak atau telepon pada jam kerja (Senin–Jumat).",
      href: wa ? `https://wa.me/${wa}` : phone ? `tel:${phone}` : "/kontak",
      hrefLabel: wa ? "Chat WhatsApp" : "Halaman Kontak",
    },
    {
      q: "Apa saja program unggulannya?",
      a: "Kelas Tahfidz, Kelas Olimpiade/Sains, Kelas Bilingual (Arab–Inggris), dan Kelas CBI (akselerasi). Ada juga program ma'had (asrama).",
      href: "/#program",
      hrefLabel: "Lihat program",
    },
    {
      q: "Ekstrakurikulernya apa saja?",
      a: "Pramuka, PMR, Robotik, Paduan Suara, Futsal, Tahfidz, dan banyak lagi wadah minat-bakat lainnya.",
      href: "/ekstrakurikuler",
      hrefLabel: "Daftar ekstrakurikuler",
    },
  ];

  useEffect(() => {
    if (open && log.length === 0) {
      // Seed sapaan bot saat panel pertama kali dibuka.
      // eslint-disable-next-line react-hooks/set-state-in-effect
      setLog([{ from: "bot", text: "Halo! 👋 Ada yang bisa dibantu? Pilih salah satu pertanyaan di bawah." }]);
    }
  }, [open, log.length]);

  useEffect(() => {
    const openHandler = () => setOpen(true);
    const toggleHandler = () => setOpen((v) => !v);
    window.addEventListener("mtsn1:open-help", openHandler);
    window.addEventListener("mtsn1:toggle-help", toggleHandler);
    return () => {
      window.removeEventListener("mtsn1:open-help", openHandler);
      window.removeEventListener("mtsn1:toggle-help", toggleHandler);
    };
  }, []);

  useEffect(() => {
    bodyRef.current?.scrollTo({ top: bodyRef.current.scrollHeight, behavior: "smooth" });
  }, [log]);

  function ask(item: QA) {
    setLog((l) => [
      ...l,
      { from: "user", text: item.q },
      { from: "bot", text: item.a, href: item.href, hrefLabel: item.hrefLabel },
    ]);
  }

  if (!open) return null;

  return (
    <div className="fixed bottom-24 right-5 z-50 flex flex-col items-end print:hidden">
      {open && (
        <div className="pop-in flex h-[28rem] w-[min(22rem,calc(100vw-2.5rem))] flex-col overflow-hidden rounded-2xl border border-border bg-surface shadow-lg">
          <div className="flex items-center justify-between gap-2 border-b border-border bg-brand px-4 py-3 text-on-brand">
            <div className="flex items-center gap-2">
              <span className="grid h-8 w-8 place-items-center rounded-full bg-white/15">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" /></svg>
              </span>
              <div className="leading-tight">
                <p className="text-sm font-bold">Asisten Madrasah</p>
                <p className="text-[0.7rem] text-on-brand/70">Bantuan cepat · FAQ</p>
              </div>
            </div>
            <button type="button" aria-label="Tutup" onClick={() => setOpen(false)} className="grid h-7 w-7 place-items-center rounded-md transition hover:bg-white/15">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round"><path d="M6 6l12 12M18 6 6 18" /></svg>
            </button>
          </div>

          <div ref={bodyRef} className="flex-1 space-y-3 overflow-y-auto p-3">
            {log.map((m, i) => (
              <div key={i} className={m.from === "user" ? "flex justify-end" : "flex justify-start"}>
                <div
                  className={`max-w-[85%] rounded-2xl px-3.5 py-2 text-sm ${
                    m.from === "user"
                      ? "rounded-br-sm bg-brand text-on-brand"
                      : "rounded-bl-sm bg-surface-muted text-ink-soft"
                  }`}
                >
                  {m.text}
                  {m.href && (
                    <Link
                      href={m.href}
                      target={m.href.startsWith("http") ? "_blank" : undefined}
                      rel={m.href.startsWith("http") ? "noreferrer" : undefined}
                      onClick={() => setOpen(false)}
                      className="mt-1.5 block font-bold text-brand underline"
                    >
                      {m.hrefLabel ?? "Selengkapnya"} →
                    </Link>
                  )}
                </div>
              </div>
            ))}
          </div>

          <div className="max-h-40 shrink-0 space-y-1.5 overflow-y-auto border-t border-border p-2.5">
            {faqs.map((f) => (
              <button
                key={f.q}
                type="button"
                onClick={() => ask(f)}
                className="block w-full rounded-lg border border-border px-3 py-2 text-left text-xs font-semibold text-ink-soft transition hover:border-brand hover:bg-brand-light hover:text-brand-dark"
              >
                {f.q}
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

/* ============================================================
   Mode fokus baca (halaman artikel)
   ============================================================ */

export function ReadingMode() {
  const [on, setOn] = useState(false);

  useEffect(() => {
    document.documentElement.toggleAttribute("data-reading", on);
    return () => document.documentElement.removeAttribute("data-reading");
  }, [on]);

  return (
    <button
      type="button"
      onClick={() => setOn((v) => !v)}
      aria-pressed={on}
      className={`inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-semibold transition ${
        on
          ? "border-brand bg-brand text-on-brand"
          : "border-border text-ink-soft hover:border-brand hover:text-brand"
      }`}
    >
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2zM22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z" />
      </svg>
      {on ? "Mode fokus aktif" : "Mode fokus baca"}
    </button>
  );
}
