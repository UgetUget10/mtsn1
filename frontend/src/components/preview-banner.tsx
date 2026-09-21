import { draftMode } from "next/headers";
import { PreviewExitButton } from "./preview-exit-button";

/**
 * Pita "Mode Pratinjau" — tampil hanya saat Draft Mode aktif (editor sedang
 * meninjau draft). Server Component; dirender di root layout supaya muncul di
 * semua halaman pratinjau. Tombol keluar (client) memakai <form method="POST">
 * dengan ?redirect= ke path saat ini agar editor kembali ke halaman yang sama,
 * bukan ke beranda.
 */
export async function PreviewBanner() {
  const { isEnabled } = await draftMode().catch(() => ({ isEnabled: false }));
  if (!isEnabled) return null;

  return (
    <div
      role="status"
      className="sticky top-0 z-80 flex flex-wrap items-center justify-center gap-3 bg-amber-500 px-4 py-2 text-center text-sm font-semibold text-amber-950"
    >
      <span>
        Mode pratinjau aktif — Anda melihat versi draft yang belum dipublikasikan.
      </span>
      <PreviewExitButton />
    </div>
  );
}
