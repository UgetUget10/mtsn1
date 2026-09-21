import type { MetadataRoute } from "next";

const base = process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000";

export default function robots(): MetadataRoute.Robots {
  return {
    rules: {
      userAgent: "*",
      allow: "/",
      // Halaman pencarian tak perlu di-crawl (noindex juga sudah di metadata-nya).
      disallow: ["/pencarian", "/en/pencarian", "/api/"],
    },
    sitemap: `${base}/sitemap.xml`,
    host: base,
  };
}
