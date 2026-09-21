import Link from "next/link";
import type { Metadata } from "next";
import { notFound, permanentRedirect } from "next/navigation";
import { getPosts, getPostsPerPage, getTag, resolveRedirect } from "@/lib/api";
import type { TagDetail } from "@/lib/types";
import { Container, EmptyState, PageHeader, Pagination, PostCard } from "@/components/ui";
import { Reveal } from "@/components/motion";
import { archiveFeedUrl, feedAlternate, localeAlternates } from "@/lib/i18n";

// Halaman membaca ?page= (searchParams) → harus dirender saat request. Tanpa ini
// Next 16 kadang mengklasifikasikannya SSG dan render produksi melempar
// DYNAMIC_SERVER_USAGE (500). Fetch-level revalidate di lib/api.ts tetap aktif,
// jadi data tetap ter-cache 5-10 menit meski shell dirender per-request.
export const dynamic = "force-dynamic";

export async function generateMetadata({
  params,
}: PageProps<"/[locale]/berita/tag/[slug]">): Promise<Metadata> {
  const { locale, slug } = await params;
  try {
    const tag = await getTag(slug);
    return {
      title: `Tag: ${tag.name}`,
      description: tag.description ?? `Kumpulan berita dengan tag ${tag.name}.`,
      alternates: {
        ...localeAlternates(locale, `/berita/tag/${tag.slug}`),
        ...feedAlternate("tag", tag.slug, `RSS — Tag: ${tag.name}`),
      },
    };
  } catch {
    return { title: "Tag Berita" };
  }
}

export default async function TagBeritaPage({
  params,
  searchParams,
}: PageProps<"/[locale]/berita/tag/[slug]">) {
  const { locale, slug } = await params;
  const sp = await searchParams;
  const page = Number(sp.page ?? 1) || 1;

  let tag: TagDetail;
  try {
    tag = await getTag(slug);
  } catch {
    // Slug tag mungkin berubah — cek redirect 301 backend dulu.
    const hit = await resolveRedirect(`/berita/tag/${slug}`);
    if (hit) permanentRedirect(locale === "id" ? hit.to : `/${locale}${hit.to}`);
    notFound();
  }

  const posts = await getPosts({ page, per_page: await getPostsPerPage(), tag: slug });
  const { current_page, last_page } = posts.meta;
  const qs = (p: number) => `/berita/tag/${slug}?page=${p}`;

  return (
    <>
      <PageHeader
        eyebrow="Tag Berita"
        title={`#${tag.name}`}
        subtitle={tag.description ?? `Semua berita dengan tag ${tag.name}.`}
        breadcrumb={[
          { label: "Berita", href: "/berita" },
          { label: `#${tag.name}` },
        ]}
      />

      <Container className="section-y">
        <div className="mb-8 flex flex-wrap items-center gap-2">
          <Link href="/berita" className="btn-chip btn-chip--accent">
            ← Semua berita
          </Link>
          <a
            href={archiveFeedUrl("tag", tag.slug)}
            className="btn-chip"
            title={`Langganan RSS tag ${tag.name}`}
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
              <path d="M4 11a9 9 0 0 1 9 9h2.5A11.5 11.5 0 0 0 4 8.5V11zm0 5a3 3 0 1 0 0 6 3 3 0 0 0 0-6zm0-9a16 16 0 0 1 16 16h2.5A18.5 18.5 0 0 0 4 4.5V7z" />
            </svg>
            RSS
          </a>
        </div>

        {posts.data.length === 0 ? (
          <EmptyState>Belum ada berita dengan tag ini.</EmptyState>
        ) : (
          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {posts.data.map((post, i) => (
              <Reveal key={post.id} delay={(i % 3) * 70} direction="up">
                <PostCard post={post} />
              </Reveal>
            ))}
          </div>
        )}

        <Pagination
          currentPage={current_page}
          lastPage={last_page}
          hrefFor={qs}
        />
      </Container>
    </>
  );
}
