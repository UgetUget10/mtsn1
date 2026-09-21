import type { Metadata } from "next";
import { Button, Container } from "@/components/ui";

export const metadata: Metadata = {
  title: "Tidak ada koneksi",
  robots: { index: false },
};

export default function OfflinePage() {
  return (
    <Container className="flex min-h-[60vh] flex-col items-center justify-center py-20 text-center">
      <span className="grid h-14 w-14 place-items-center rounded-2xl bg-brand-light text-brand">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <path d="M1 1l22 22M16.72 11.06A10.94 10.94 0 0 1 19 12.55M5 12.55a10.94 10.94 0 0 1 5.17-2.39M10.71 5.05A16 16 0 0 1 22.58 9M1.42 9a15.91 15.91 0 0 1 4.7-2.88M8.53 16.11a6 6 0 0 1 6.95 0M12 20h.01" />
        </svg>
      </span>
      <h1 className="text-h2 mt-6">Anda sedang offline</h1>
      <p className="mt-3 max-w-md text-lead">
        Halaman ini belum tersimpan di perangkat. Periksa koneksi internet Anda,
        lalu coba lagi. Halaman yang pernah dibuka tetap bisa diakses tanpa koneksi.
      </p>
      <div className="mt-8">
        <Button href="/">Kembali ke beranda</Button>
      </div>
    </Container>
  );
}
