import Link from "next/link";
import type { Metadata } from "next";
import { notFound, permanentRedirect } from "next/navigation";
import {
  getCategories,
  getCategory,
  getPosts,
  getPostsPerPage,
  resolveRedirect,
} from "@/lib/api";
import type { CategoryDetail } from "@/lib/types";
import { Container, EmptyState, PageHeader, Pagination, PostCard } from "@/components/ui";
import { Reveal } from "@/components/motion";
import { archiveFeedUrl, feedAlternate, localeAlternates } from "@/lib/i18n";

export const revalidate = 300;

export async function generateStaticParams() {
  const cats = await getCategories().catch(() => []);
  return cats.map((c) => ({ slug: c.slug }));
}

export async function generateMetadata({
  params,
}: PageProps<"/[locale]/berita/kategori/[slug]">): Promise<Metadata> {
  const { locale, slug } = await params;
  try {
    const cat = await getCategory(slug);
    return {
      title: `Kategori: ${cat.name}`,
      description: cat.description ?? `Kumpulan berita dalam kategori ${cat.name}.`,
      alternates: {
        ...localeAlternates(locale, `/berita/kategori/${cat.slug}`),
        ...feedAlternate("category", cat.slug, `RSS — Kategori: ${cat.name}`),
      },
    };
  } catch {
    return { title: "Kategori Berita" };
  }
}

export default async function KategoriBeritaPage({
  params,
  searchParams,
}: PageProps<"/[locale]/berita/kategori/[slug]">) {
  const { locale, slug } = await params;
  const sp = await searchParams;
  const page = Number(sp.page ?? 1) || 1;

  let category: CategoryDetail;
  try {
    category = await getCategory(slug);
  } catch {
    // Slug kategori mungkin berubah — cek redirect 301 backend dulu.
    const hit = await resolveRedirect(`/berita/kategori/${slug}`);
    if (hit) permanentRedirect(locale === "id" ? hit.to : `/${locale}${hit.to}`);
    notFound();
  }

  const posts = await getPosts({
    page,
    per_page: await getPostsPerPage(),
    category: slug,
  });
  const { current_page, last_page } = posts.meta;

  const qs = (p: number) => `/berita/kategori/${slug}?page=${p}`;

  return (
    <>
      <PageHeader
        eyebrow="Kategori Berita"
        title={category.name}
        subtitle={category.description ?? `Semua berita dalam kategori ${category.name}.`}
        breadcrumb={[
          { label: "Berita", href: "/berita" },
          // Sisipkan rantai kategori induk (wp: hierarchical category breadcrumb).
          ...category.ancestors.map((a) => ({
            label: a.name,
            href: `/berita/kategori/${a.slug}`,
          })),
          { label: category.name },
        ]}
      />

      <Container className="section-y">
        <div className="mb-8 flex flex-wrap items-center gap-2">
          <Link
            href={
              category.parent ? `/berita/kategori/${category.parent}` : "/berita"
            }
            className="btn-chip btn-chip--accent"
          >
            ← {category.parent ? "Kategori induk" : "Semua berita"}
          </Link>
          <a
            href={archiveFeedUrl("category", category.slug)}
            className="btn-chip"
            title={`Langganan RSS kategori ${category.name}`}
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
              <path d="M4 11a9 9 0 0 1 9 9h2.5A11.5 11.5 0 0 0 4 8.5V11zm0 5a3 3 0 1 0 0 6 3 3 0 0 0 0-6zm0-9a16 16 0 0 1 16 16h2.5A18.5 18.5 0 0 0 4 4.5V7z" />
            </svg>
            RSS
          </a>
        </div>

        {category.children.length > 0 && (
          <div className="mb-8">
            <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-muted">
              Sub-kategori
            </p>
            <ul className="flex flex-wrap gap-2">
              {category.children.map((c) => (
                <li key={c.slug}>
                  <Link
                    href={`/berita/kategori/${c.slug}`}
                    className="inline-flex rounded-full border border-border bg-surface-muted px-3 py-1 text-xs font-semibold text-ink-soft transition hover:border-brand hover:text-brand-dark"
                  >
                    {c.name}
                  </Link>
                </li>
              ))}
            </ul>
          </div>
        )}

        {posts.data.length === 0 ? (
          <EmptyState>Belum ada berita dalam kategori ini.</EmptyState>
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
