import type { MetadataRoute } from "next";
import {
  getArchives,
  getAuthors,
  getCategories,
  getNavPages,
  getPosts,
  getTags,
} from "@/lib/api";

const base = process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000";

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const [pages, posts, tags, categories, authors, archives] = await Promise.all([
    getNavPages().catch(() => []),
    getPosts({ per_page: 100 }),
    getTags(),
    getCategories(),
    getAuthors(),
    getArchives(),
  ]);

  const staticRoutes = [
    "",
    "/profil",
    "/berita",
    "/dokumen",
    "/guru",
    "/agenda",
    "/galeri",
    "/ekstrakurikuler",
    "/prestasi",
    "/ppdb",
    "/kontak",
    "/layanan",
    "/akademik",
    "/mahad",
    "/area-zi",
  ].map((path) => ({ url: `${base}${path}`, lastModified: new Date() }));

  const idEntries = [
    ...staticRoutes,
    ...pages.map((p) => ({ url: `${base}/profil/${p.slug}`, lastModified: new Date() })),
    ...posts.data.map((p) => ({
      url: `${base}/berita/${p.slug}`,
      lastModified: p.published_at ? new Date(p.published_at) : new Date(),
    })),
    ...categories.map((c) => ({
      url: `${base}/berita/kategori/${c.slug}`,
      lastModified: new Date(),
    })),
    ...tags.map((t) => ({
      url: `${base}/berita/tag/${t.slug}`,
      lastModified: new Date(),
    })),
    ...authors
      .filter((a) => a.slug)
      .map((a) => ({ url: `${base}/penulis/${a.slug}`, lastModified: new Date() })),
    // Arsip tanggal ala WordPress (/berita/arsip/{tahun}/{bulan}).
    ...archives.map((a) => ({
      url: `${base}/berita/arsip/${a.year}/${String(a.month).padStart(2, "0")}`,
      lastModified: new Date(),
    })),
  ];

  // Locale id tanpa prefix (URL utama); en dengan prefix /en — lihat
  // frontend/src/proxy.ts untuk skema rewrite-nya.
  const enEntries = idEntries.map((entry) => ({
    ...entry,
    url: entry.url === base ? `${base}/en` : entry.url.replace(base, `${base}/en`),
  }));

  return [...idEntries, ...enEntries];
}
