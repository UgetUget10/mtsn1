"use client";

import { usePathname } from "next/navigation";

/**
 * Tombol keluar pratinjau. Client Component hanya untuk membaca path aktif
 * (`usePathname`) dan meneruskannya sebagai ?redirect= supaya /api/preview/exit
 * mengembalikan editor ke halaman yang sedang dilihat. Form method POST tidak
 * di-prefetch, jadi cookie draft tidak terhapus sebelum diklik.
 */
export function PreviewExitButton() {
  const pathname = usePathname();

  return (
    <form action={`/api/preview/exit?redirect=${encodeURIComponent(pathname)}`} method="POST">
      <button
        type="submit"
        className="rounded-md bg-amber-950/10 px-3 py-1 text-xs font-bold uppercase tracking-wide transition hover:bg-amber-950/20"
      >
        Keluar pratinjau
      </button>
    </form>
  );
}
