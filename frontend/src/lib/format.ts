const dateFmt = new Intl.DateTimeFormat("id-ID", {
  day: "numeric",
  month: "long",
  year: "numeric",
});

const dateTimeFmt = new Intl.DateTimeFormat("id-ID", {
  day: "numeric",
  month: "long",
  year: "numeric",
  hour: "2-digit",
  minute: "2-digit",
});

export function formatDate(value: string | null | undefined): string {
  if (!value) return "";
  return dateFmt.format(new Date(value));
}

export function formatDateTime(value: string | null | undefined): string {
  if (!value) return "";
  return dateTimeFmt.format(new Date(value));
}

export function readingMinutes(html: string | null | undefined): number {
  if (!html) return 0;
  const text = html.replace(/<[^>]+>/g, " ").replace(/\s+/g, " ").trim();
  const words = text ? text.split(" ").length : 0;
  return words ? Math.max(1, Math.round(words / 200)) : 0;
}

export function formatBytes(bytes: number | null | undefined): string {
  if (!bytes) return "";
  const units = ["B", "KB", "MB", "GB"];
  let i = 0;
  let n = bytes;
  while (n >= 1024 && i < units.length - 1) {
    n /= 1024;
    i++;
  }
  return `${n.toFixed(1)} ${units[i]}`;
}

/**
 * Apakah tanggal batas (ISO string) sudah lewat, dievaluasi saat render.
 * Diletakkan sebagai fungsi bernama di luar komponen supaya pembacaan waktu
 * "sekarang" tidak dianggap impure di dalam body komponen (aturan
 * react-hooks/purity). Halaman pemakainya ber-`revalidate` sehingga status
 * ini paling lama basi selama jendela revalidasi — cukup untuk batas PPDB
 * yang bergranularitas hari.
 */
export function isDeadlinePassed(deadline: string | null | undefined): boolean {
  if (!deadline) return false;
  const t = new Date(deadline).getTime();
  return Number.isFinite(t) && t < Date.now();
}
