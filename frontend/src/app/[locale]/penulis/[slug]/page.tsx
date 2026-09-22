import Image from "@/components/media-image";
import Link from "next/link";
import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { getAuthor, getAuthors, getPosts } from "@/lib/api";
import type { AuthorProfile } from "@/lib/types";
import { Container, EmptyState, PageHeader, Pagination, PostCard } from "@/components/ui";
import { Reveal } from "@/components/motion";
import { archiveFeedUrl, feedAlternate, localeAlternates } from "@/lib/i18n";

export const revalidate = 600;

const SOCIAL_LABELS: Record<string, string> = {
  website: "Situs web",
  instagram: "Instagram",
  twitter: "X / Twitter",
  linkedin: "LinkedIn",
  scholar: "Google Scholar",
};

export async function generateStaticParams() {
  const authors = await getAuthors();
  return authors.filter((a) => a.slug).map((a) => ({ slug: a.slug as string }));
}

export async function generateMetadata({
  params,
}: PageProps<"/[locale]/penulis/[slug]">): Promise<Metadata> {
  const { locale, slug } = await params;
  try {
    const author = await getAuthor(slug);
    return {
      title: `${author.name}${author.job_title ? ` — ${author.job_title}` : ""}`,
      description:
        author.bio ?? `Kumpulan tulisan oleh ${author.name} di situs MTsN 1.`,
      alternates: {
        ...localeAlternates(locale, `/penulis/${author.slug}`),
        ...feedAlternate("author", author.slug, `RSS — ${author.name}`),
      },
    };
  } catch {
    return { title: "Penulis" };
  }
}

export default async function PenulisPage({
  params,
  searchParams,
}: PageProps<"/[locale]/penulis/[slug]">) {
  const { slug } = await params;
  const sp = await searchParams;
  const page = Number(sp.page ?? 1) || 1;

  let author: AuthorProfile;
  try {
    author = await getAuthor(slug);
  } catch {
    notFound();
  }

  const posts = await getPosts({ page, per_page: 12, author: slug });
  const { current_page, last_page } = posts.meta;
  const qs = (p: number) => `/penulis/${slug}?page=${p}`;
  const socials = Object.entries(author.social ?? {}).filter(([, v]) => v);

  return (
    <>
      <PageHeader
        eyebrow="Penulis"
        title={author.name}
        subtitle={author.job_title ?? undefined}
        breadcrumb={[
          { label: "Berita", href: "/berita" },
          { label: author.name },
        ]}
      />

      <Container className="section-y">
        <div className="mb-10 flex flex-col gap-5 rounded-2xl border border-border bg-surface-muted p-6 sm:flex-row sm:items-start">
          {author.avatar ? (
            <Image
              src={author.avatar}
              alt={author.name}
              width={96}
              height={96}
              className="h-24 w-24 shrink-0 rounded-full object-cover"
            />
          ) : (
            <span className="grid h-24 w-24 shrink-0 place-items-center rounded-full bg-brand-light text-3xl font-bold text-brand-dark">
              {author.name.slice(0, 1)}
            </span>
          )}
          <div className="min-w-0">
            {author.bio && (
              <p className="text-sm leading-relaxed text-ink-soft">{author.bio}</p>
            )}
            <p className="mt-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">
              {author.posts_count} tulisan
            </p>
            {socials.length > 0 && (
              <ul className="mt-3 flex flex-wrap gap-2">
                {socials.map(([key, url]) => (
                  <li key={key}>
                    <a
                      href={url}
                      target="_blank"
                      rel="nofollow noopener noreferrer"
                      className="inline-flex rounded-full border border-border bg-surface px-3 py-1 text-xs font-semibold text-ink-soft transition hover:border-brand hover:text-brand-dark"
                    >
                      {SOCIAL_LABELS[key] ?? key}
                    </a>
                  </li>
                ))}
              </ul>
            )}
          </div>
        </div>

        <div className="mb-8 flex flex-wrap items-center gap-2">
          <Link href="/berita" className="btn-chip btn-chip--accent">
            ← Semua berita
          </Link>
          <a
            href={archiveFeedUrl("author", author.slug)}
            className="btn-chip"
            title={`Langganan RSS tulisan ${author.name}`}
          >
            <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden>
              <path d="M4 11a9 9 0 0 1 9 9h2.5A11.5 11.5 0 0 0 4 8.5V11zm0 5a3 3 0 1 0 0 6 3 3 0 0 0 0-6zm0-9a16 16 0 0 1 16 16h2.5A18.5 18.5 0 0 0 4 4.5V7z" />
            </svg>
            RSS
          </a>
        </div>

        {posts.data.length === 0 ? (
          <EmptyState>Penulis ini belum punya berita terbit.</EmptyState>
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
