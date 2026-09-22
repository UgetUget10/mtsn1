/**
 * URL media dari backend memakai APP_URL publik (mis.
 * https://mtsn1kotamalang-new.sch.id/storage/...). Saat Next.js SENDIRI
 * memfetch untuk optimasi lewat /_next/image, permintaan itu berjalan di
 * server yang sama dan tidak bisa menjangkau domain publiknya sendiri lewat
 * DNS/HTTPS di luar kendali kita — persis masalah yang sama dengan fetch API
 * (lihat INTERNAL_API_URL di lib/api.ts).
 *
 * Tukar origin publik ke origin internal SEBELUM diberikan ke <Image> (lihat
 * components/media-image.tsx). Aman: browser tidak pernah melihat URL ini
 * langsung — ia hanya memanggil /_next/image?url=... di domain situs
 * sendiri, dan Next yang benar-benar mem-fetch `url` di sisi server saat
 * request itu masuk, bukan saat render awal.
 *
 * PAKAI var NEXT_PUBLIC_INTERNAL_MEDIA_ORIGIN (bukan INTERNAL_API_URL) —
 * fungsi ini juga dipanggil ulang di BROWSER saat komponen client (mis.
 * NewsSlider) hydrate. INTERNAL_API_URL tidak ter-inline ke bundle browser
 * (bukan NEXT_PUBLIC_*), jadi hasil swap-nya beda dari yang dihitung server
 * saat SSR → React menimpa src hasil SSR yang benar dengan versi browser
 * yang salah (URL publik, belum tentu resolve DNS) begitu hydrate selesai,
 * membuat gambar sempat tampil lalu hilang.
 */
function safeOrigin(url: string | undefined): string | null {
  if (!url) return null;
  try {
    return new URL(url).origin;
  } catch {
    return null;
  }
}

const PUBLIC_ORIGIN = safeOrigin(process.env.NEXT_PUBLIC_SITE_URL);
const INTERNAL_ORIGIN = safeOrigin(process.env.NEXT_PUBLIC_INTERNAL_MEDIA_ORIGIN);

export function toOptimizerSrc(src: string): string {
  if (!PUBLIC_ORIGIN || !INTERNAL_ORIGIN || !src.startsWith(PUBLIC_ORIGIN)) {
    return src;
  }

  return INTERNAL_ORIGIN + src.slice(PUBLIC_ORIGIN.length);
}
