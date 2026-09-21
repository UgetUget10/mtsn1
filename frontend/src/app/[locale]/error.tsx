"use client";

import { usePathname } from "next/navigation";
import { Container } from "@/components/ui";

// error.tsx wajib Client Component, jadi tidak bisa pakai getDictionary
// (server-only). Untuk dua string ini, pilih bahasa dari prefix path.
const strings = {
  id: {
    title: "Terjadi kesalahan",
    description: "Konten gagal dimuat. Pastikan server API berjalan, lalu coba lagi.",
    retry: "Muat ulang",
  },
  en: {
    title: "Something went wrong",
    description: "The content failed to load. Make sure the API server is running, then try again.",
    retry: "Reload",
  },
};

export default function Error({ reset }: { error: Error; reset: () => void }) {
  const pathname = usePathname();
  const t = pathname === "/en" || pathname.startsWith("/en/") ? strings.en : strings.id;

  return (
    <Container className="flex min-h-[62vh] flex-col items-center justify-center py-24 text-center">
      <span className="grid h-14 w-14 place-items-center rounded-2xl bg-danger-soft text-danger">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <path d="M12 9v4M12 17h.01M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z" />
        </svg>
      </span>
      <h1 className="text-h2 mt-6">{t.title}</h1>
      <p className="mt-3 max-w-md text-lead">{t.description}</p>
      <button
        onClick={reset}
        className="mt-8 inline-flex min-h-12 items-center rounded-xl bg-brand px-7 text-sm font-semibold text-on-brand shadow-sm transition hover:bg-brand-dark"
      >
        {t.retry}
      </button>
    </Container>
  );
}
