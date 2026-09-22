import NextImage, { type ImageProps } from "next/image";
import { toOptimizerSrc } from "@/lib/media";

/**
 * Pengganti next/image untuk gambar dari backend sendiri (semua foto/berkas
 * di panel admin) — lihat lib/media.ts#toOptimizerSrc untuk alasannya.
 * Komponen lain yang menampilkan foto dari API HARUS mengimpor dari sini,
 * bukan langsung dari "next/image".
 */
export default function Image({ src, ...props }: ImageProps) {
  return <NextImage src={typeof src === "string" ? toOptimizerSrc(src) : src} {...props} />;
}
