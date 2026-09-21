import type { Metadata } from "next";
import { notFound, permanentRedirect } from "next/navigation";
import { getNavPages, getPage, resolveRedirect } from "@/lib/api";
import type { PageWithBlocks } from "@/lib/types";
import { PageTemplateRenderer } from "@/components/page-template";
import { localeAlternates } from "@/lib/i18n";

export const revalidate = 600;

export async function generateStaticParams() {
  const pages = await getNavPages().catch(() => []);
  return pages.map((p) => ({ slug: p.slug }));
}

export async function generateMetadata({
  params,
}: PageProps<"/[locale]/profil/[slug]">): Promise<Metadata> {
  const { locale, slug } = await params;
  try {
    const page = await getPage(slug);
    const seo = page.seo;
    const alt = localeAlternates(locale, `/profil/${page.slug}`);
    const title = seo?.title ?? page.title;
    const description = seo?.description ?? page.meta_description ?? undefined;

    return {
      title,
      description,
      alternates: seo?.canonical ? { ...alt, canonical: seo.canonical } : alt,
      robots: seo?.noindex ? { index: false, follow: false } : undefined,
      // Kartu share (wp: og:image dari metabox SEO). Sebelumnya halaman profil
      // tidak punya blok openGraph sama sekali.
      openGraph: {
        title,
        description,
        type: "article",
        images: seo?.og_image ? [seo.og_image] : undefined,
      },
    };
  } catch {
    return { title: "Profil" };
  }
}

export default async function ProfilPage({ params }: PageProps<"/[locale]/profil/[slug]">) {
  const { locale, slug } = await params;

  let page: PageWithBlocks;
  try {
    page = await getPage(slug);
  } catch {
    const hit = await resolveRedirect(`/profil/${slug}`);
    if (hit) permanentRedirect(locale === "id" ? hit.to : `/${locale}${hit.to}`);
    notFound();
  }

  // Halaman saudara (sama induk, wp: bukan child sendiri) — mis. "Visi dan
  // Misi" tak punya anak, tapi pengunjung wajar ingin loncat ke "Sejarah"
  // atau "Struktur Organisasi" yang satu grup "Profil Madrasah".
  const siblingPages = page.parent
    ? (await getNavPages().catch(() => []))
        .filter((p) => p.parent === page.parent && p.slug !== page.slug)
        .map((p) => ({ slug: p.slug, title: p.title }))
    : [];

  return <PageTemplateRenderer page={page} siblingPages={siblingPages} />;
}
