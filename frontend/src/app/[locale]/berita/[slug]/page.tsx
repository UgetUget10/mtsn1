import Image from "@/components/media-image";
import Link from "next/link";
import type { Metadata } from "next";
import { notFound, permanentRedirect } from "next/navigation";
import { getComments, getPost, getPosts, getWidgetArea, resolveRedirect } from "@/lib/api";
import { BlockRenderer } from "@/components/blocks/block-renderer";
import { Comments } from "@/components/comments";
import { PostPasswordForm } from "@/components/post-password-form";
import { RichContent } from "@/components/rich-content";
import type { Post } from "@/lib/types";
import { Container, PageHeader, PostCard } from "@/components/ui";
import { ShareButtons } from "@/components/features";
import { ArticleProgress } from "@/components/features-more";
import { ReadingMode } from "@/components/features-help";
import { TableOfContents } from "@/components/features-data";
import { formatDate, readingMinutes } from "@/lib/format";
import { localeAlternates } from "@/lib/i18n";

export const revalidate = 120;

export async function generateStaticParams() {
  const posts = await getPosts({ per_page: 30 });
  return posts.data.map((p) => ({ slug: p.slug }));
}

export async function generateMetadata({
  params,
}: PageProps<"/[locale]/berita/[slug]">): Promise<Metadata> {
  const { locale, slug } = await params;
  try {
    const { data } = await getPost(slug);
    const seo = data.seo;
    const title = seo?.title ?? data.title;
    const description = seo?.description ?? data.excerpt ?? undefined;
    const image = seo?.og_image ?? data.cover;
    const alt = localeAlternates(locale, `/berita/${data.slug}`);
    return {
      title,
      description,
      alternates: seo?.canonical ? { ...alt, canonical: seo.canonical } : alt,
      // Post terproteksi/privat tidak boleh diindeks (wp: noindex otomatis).
      robots:
        seo?.noindex || data.protected
          ? { index: false, follow: false }
          : undefined,
      openGraph: {
        title,
        description,
        type: "article",
        images: image ? [image] : undefined,
      },
    };
  } catch {
    return { title: "Berita" };
  }
}

export default async function BeritaDetail({ params }: PageProps<"/[locale]/berita/[slug]">) {
  const { locale, slug } = await params;

  let post: Post;
  try {
    post = (await getPost(slug)).data;
  } catch {
    // Slug mungkin berubah — cek tabel redirect backend sebelum menyerah (301).
    const hit = await resolveRedirect(`/berita/${slug}`);
    if (hit) permanentRedirect(locale === "id" ? hit.to : `/${locale}${hit.to}`);
    notFound();
  }

  // Rekomendasi: utamakan berita satu kategori; jika kurang dari 3, lengkapi
  // dengan berita yang berbagi salah satu tag; terakhir jatuh ke berita terbaru.
  const relatedPools = await Promise.all([
    post.category?.slug
      ? getPosts({ per_page: 4, category: post.category.slug })
      : Promise.resolve(null),
    post.tags?.[0]?.slug
      ? getPosts({ per_page: 4, tag: post.tags[0].slug })
      : Promise.resolve(null),
    getPosts({ per_page: 4 }),
  ]);

  const seen = new Set([post.slug]);
  const related = relatedPools
    .flatMap((pool) => pool?.data ?? [])
    .filter((p) => !seen.has(p.slug) && seen.add(p.slug))
    .slice(0, 3);

  const sidebarWidgets = await getWidgetArea("sidebar-berita");

  const mins = readingMinutes(post.body);
  const comments = await getComments(slug);

  return (
    <>
      <PageHeader
        eyebrow={post.category?.name ?? "Berita"}
        title={post.title}
        subtitle={
          [
            formatDate(post.published_at),
            post.author?.name || null,
            mins ? `± ${mins} menit baca` : null,
            comments.count > 0 ? `${comments.count} komentar` : null,
          ]
            .filter(Boolean)
            .join(" · ")
        }
        breadcrumb={[{ label: "Berita", href: "/berita" }, { label: "Artikel" }]}
      />
      <Container className="grid gap-10 section-y lg:grid-cols-[minmax(0,1fr)_15rem] lg:items-start">
        <div className="min-w-0">
          <div className="mb-4 flex items-center justify-end">
            <ReadingMode />
          </div>
          <ArticleProgress />
          <article>
            {post.cover && (
              <figure className="mb-8">
                <div className="relative aspect-16/9 overflow-hidden rounded-2xl bg-surface-muted shadow-sm">
                  <Image
                    src={post.cover}
                    alt={post.cover_alt ?? post.title}
                    fill
                    priority
                    sizes="(max-width: 1024px) 100vw, 720px"
                    className="object-cover"
                  />
                </div>
                {(post.cover_caption || post.cover_credit) && (
                  <figcaption className="mt-2.5 text-center text-[0.85rem] leading-snug text-ink-muted">
                    {post.cover_caption}
                    {post.cover_caption && post.cover_credit ? " — " : null}
                    {post.cover_credit ? (
                      <span className="italic">Foto: {post.cover_credit}</span>
                    ) : null}
                  </figcaption>
                )}
              </figure>
            )}

            {post.protected && !post.unlocked ? (
              <PostPasswordForm slug={post.slug} />
            ) : (
              <>
                {/* wp: "Last updated on …" — hanya bila artikel memang
                    disunting setelah terbit (backend yang memutuskan). */}
                {post.updated_at && (
                  <p className="mb-6 text-xs font-semibold uppercase tracking-wide text-ink-muted">
                    Terakhir diperbarui: {formatDate(post.updated_at)}
                  </p>
                )}
                <RichContent
                  html={post.body ?? ""}
                  className="max-w-[68ch] text-ink-soft"
                />
              </>
            )}

            {(!post.protected || post.unlocked) && post.tags && post.tags.length > 0 && (
              <ul className="mt-8 flex flex-wrap items-center gap-2">
                <li className="mr-1 text-xs font-bold uppercase tracking-wide text-ink-muted">
                  Topik
                </li>
                {post.tags.map((t) => (
                  <li key={t.slug}>
                    <Link
                      href={`/berita/tag/${encodeURIComponent(t.slug)}`}
                      className="inline-flex items-center rounded-full border border-border bg-surface-muted px-3 py-1 text-xs font-semibold text-ink-soft transition hover:border-brand hover:bg-brand-light hover:text-brand-dark"
                    >
                      <span className="mr-0.5 text-brand">#</span>
                      {t.name}
                    </Link>
                  </li>
                ))}
              </ul>
            )}

            {post.author && (
              <div className="mt-10 flex items-start gap-4 rounded-2xl border border-border bg-surface-muted p-5">
                {post.author.avatar ? (
                  <Image
                    src={post.author.avatar}
                    alt={post.author.name}
                    width={56}
                    height={56}
                    className="h-14 w-14 shrink-0 rounded-full object-cover"
                  />
                ) : (
                  <span className="grid h-14 w-14 shrink-0 place-items-center rounded-full bg-brand-light text-lg font-bold text-brand-dark">
                    {post.author.name.slice(0, 1)}
                  </span>
                )}
                <div className="min-w-0">
                  <p className="text-xs font-semibold uppercase tracking-wide text-ink-muted">
                    Ditulis oleh
                  </p>
                  <p className="text-base font-bold text-ink">
                    {post.author.slug ? (
                      <Link href={`/penulis/${post.author.slug}`} className="hover:text-brand-dark">
                        {post.author.name}
                      </Link>
                    ) : (
                      post.author.name
                    )}
                  </p>
                  {post.author.job_title && (
                    <p className="text-sm text-ink-muted">{post.author.job_title}</p>
                  )}
                </div>
              </div>
            )}

            {post.custom_fields && Object.keys(post.custom_fields).length > 0 && (
              <dl className="mt-10 grid gap-x-6 gap-y-2 border-t border-border pt-6 text-sm sm:grid-cols-2">
                {Object.entries(post.custom_fields).map(([key, value]) => (
                  <div key={key} className="flex gap-2">
                    <dt className="font-semibold text-ink-muted">{key}:</dt>
                    <dd className="text-ink">{value}</dd>
                  </div>
                ))}
              </dl>
            )}

            <div className="mt-10 flex flex-wrap items-center justify-between gap-4 border-t border-border pt-6">
              <Link
                href="/berita"
                className="inline-flex items-center gap-1.5 rounded-lg border border-border bg-surface px-4 py-2 text-sm font-semibold text-brand-dark transition hover:border-brand hover:bg-brand-light"
              >
                ← Kembali ke berita
              </Link>
              <ShareButtons title={post.title} />
            </div>

            <script
              type="application/ld+json"
              dangerouslySetInnerHTML={{
                __html: JSON.stringify({
                  "@context": "https://schema.org",
                  "@type": "NewsArticle",
                  headline: post.title,
                  datePublished: post.published_at,
                  // wp: post_modified — sinyal kesegaran untuk mesin pencari.
                  dateModified: post.updated_at ?? post.published_at,
                  author: post.author
                    ? { "@type": "Person", name: post.author.name }
                    : undefined,
                }),
              }}
            />
          </article>

          {(!post.protected || post.unlocked) && post.adjacent && (
            <nav
              aria-label="Navigasi artikel"
              className="mt-12 grid gap-3 border-t border-border pt-8 sm:grid-cols-2"
            >
              {post.adjacent.previous ? (
                <Link
                  href={`/berita/${post.adjacent.previous.slug}`}
                  rel="prev"
                  className="group flex flex-col gap-1 rounded-xl border border-border bg-surface px-4 py-3 transition hover:border-brand hover:bg-brand-light"
                >
                  <span className="text-xs font-semibold uppercase tracking-wide text-ink-muted">
                    ← Artikel sebelumnya
                  </span>
                  <span className="font-semibold text-brand-dark line-clamp-2">
                    {post.adjacent.previous.title}
                  </span>
                </Link>
              ) : (
                <span />
              )}
              {post.adjacent.next ? (
                <Link
                  href={`/berita/${post.adjacent.next.slug}`}
                  rel="next"
                  className="group flex flex-col gap-1 rounded-xl border border-border bg-surface px-4 py-3 text-right transition hover:border-brand hover:bg-brand-light sm:items-end"
                >
                  <span className="text-xs font-semibold uppercase tracking-wide text-ink-muted">
                    Artikel berikutnya →
                  </span>
                  <span className="font-semibold text-brand-dark line-clamp-2">
                    {post.adjacent.next.title}
                  </span>
                </Link>
              ) : (
                <span />
              )}
            </nav>
          )}

          {(!post.protected || post.unlocked) && (
            <div className="mt-12 border-t border-border pt-10">
              <Comments slug={post.slug} thread={comments} />
            </div>
          )}
        </div>

        <aside className="reading-dim hidden lg:sticky lg:top-24 lg:block lg:space-y-8">
          <TableOfContents />
          {sidebarWidgets.blocks.length > 0 && (
            <div className="space-y-6 border-t border-border pt-6">
              <BlockRenderer blocks={sidebarWidgets.blocks} />
            </div>
          )}
        </aside>
      </Container>

      {related.length > 0 && (
        <section className="reading-dim border-t border-border bg-surface-muted section-y">
          <Container>
            <h2 className="text-h2 heading-rule mb-6">Baca juga</h2>
            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
              {related.map((p) => (
                <PostCard key={p.id} post={p} />
              ))}
            </div>
          </Container>
        </section>
      )}
    </>
  );
}
