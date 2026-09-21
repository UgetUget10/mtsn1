import type { Metadata } from "next";
import Link from "next/link";
import { Plus_Jakarta_Sans } from "next/font/google";
import "./globals.css";

/**
 * 404 global untuk URL yang tidak cocok rute apa pun. Wajib mengembalikan
 * dokumen HTML lengkap (bypass root layout). Bilingual sederhana: default
 * Indonesia, karena URL tak dikenal tidak membawa konteks locale.
 */

const jakarta = Plus_Jakarta_Sans({ variable: "--font-sans", subsets: ["latin"] });

export const metadata: Metadata = {
  title: "404 — Halaman tidak ditemukan",
  description: "Halaman yang Anda cari tidak tersedia.",
};

export default function GlobalNotFound() {
  return (
    <html lang="id" className={`${jakarta.variable} antialiased`}>
      <body className="min-h-dvh">
        <main className="mx-auto flex min-h-dvh max-w-lg flex-col items-center justify-center px-6 py-24 text-center">
          <span className="grid h-14 w-14 place-items-center rounded-2xl bg-brand-light text-brand">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
              <circle cx="11" cy="11" r="7" />
              <path d="m21 21-4.3-4.3M8 11h6" />
            </svg>
          </span>
          <p className="mt-6 text-xs font-bold uppercase tracking-[0.2em] text-brand">Error 404</p>
          <h1 className="mt-2 text-2xl font-extrabold text-foreground sm:text-3xl">
            Halaman tidak ditemukan
          </h1>
          <p className="mt-2 text-sm text-ink-muted">Page not found</p>
          <p className="mt-4 max-w-md text-ink-soft">
            Maaf, halaman yang Anda cari tidak tersedia atau telah dipindahkan.
          </p>
          <div className="mt-8 flex flex-wrap justify-center gap-3">
            <Link
              href="/"
              className="inline-flex min-h-12 items-center rounded-xl bg-brand px-7 text-sm font-semibold text-on-brand shadow-sm transition hover:bg-brand-dark"
            >
              Kembali ke Beranda
            </Link>
            <Link
              href="/en"
              className="inline-flex min-h-12 items-center rounded-xl border border-border-strong bg-surface px-7 text-sm font-semibold text-foreground transition hover:border-brand hover:bg-brand-light"
            >
              English
            </Link>
          </div>
        </main>
      </body>
    </html>
  );
}
