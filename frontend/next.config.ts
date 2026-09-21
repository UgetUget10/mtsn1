import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Build produksi Docker (frontend/Dockerfile) menyalin .next/standalone
  // sebagai runtime image — tanpa ini, folder itu tidak ter-generate.
  output: "standalone",
  // API lokal (php artisan serve) bersifat single-thread & lambat (~1-2 dtk/req);
  // prerender paralel bisa menembus batas default 60 dtk. Naikkan agar build stabil.
  staticPageGenerationTimeout: 240,
  // - globalNotFound: root segment adalah [locale], jadi not-found.tsx per-segmen
  //   TIDAK menangkap URL yang tak cocok rute apa pun — hanya notFound() eksplisit.
  //   global-not-found.tsx menangani URL tak dikenal di seluruh aplikasi.
  // - cpus: mesin dev RAM 16 GB sering hampir penuh; 15 worker prerender paralel
  //   bikin build macet di ~40/160 karena thrashing. Batasi jumlah worker.
  experimental: {
    globalNotFound: true,
    cpus: 3,
  },
  // Situs diakses lewat proxy Apache di http://mtsn1.test (dev server ada di
  // localhost:3000). Tanpa ini, Next 16 memblokir resource /_next/* lintas
  // origin sehingga JavaScript client tidak ter-hydrate.
  allowedDevOrigins: ["mtsn1.test", "*.mtsn1.test", "192.168.1.71"],
  images: {
    // Backend & frontend berjalan di satu mesin; domain `mtsn1.test` resolve
    // ke 127.0.0.1 (hosts Laragon). Next memblokir optimasi gambar dari IP
    // privat sebagai proteksi SSRF — aman diaktifkan di setup lokal ini,
    // baik mode dev maupun produksi (pm2 next start).
    dangerouslyAllowLocalIP: true,
    // Next 16 menolak `q` yang tidak terdaftar ("q parameter of 95 is not
    // allowed" → HTTP 400). 95 dipakai foto potret besar (sambutan kepala
    // madrasah); 75 tetap default untuk sisanya.
    qualities: [75, 90, 95],
    // `imageSizes` dipakai untuk slot yang lebih kecil dari 640px. Default Next
    // tidak punya 512, padahal kolom foto sambutan tepat ±512px — tanpa ini
    // browser melompat ke 640w.
    //
    // Untuk DPR 2 (Retina/HiDPI) slot 512px CSS butuh 1024 piksel FISIK. Next
    // menyusun srcset dari deviceSizes+imageSizes, jadi 1024 harus benar-benar
    // terdaftar — kalau tidak, browser mengambil 512w lalu meregangkannya 2x
    // dan foto tampak lembek berapa pun `quality`-nya.
    imageSizes: [16, 32, 48, 64, 96, 128, 256, 384, 512, 1024],
    remotePatterns: [
      { protocol: "http", hostname: "mtsn1.test" },
      { protocol: "http", hostname: "127.0.0.1", port: "8000" },
      { protocol: "http", hostname: "localhost", port: "8000" },
      { protocol: "https", hostname: "**" },
    ],
  },
};

export default nextConfig;
