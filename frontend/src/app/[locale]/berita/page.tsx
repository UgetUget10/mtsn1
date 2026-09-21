import Link from "next/link";
import type { Metadata } from "next";
import { getArchives, getPosts, getPostsPerPage } from "@/lib/api";
import { Container, EmptyState, PageHeader, Pagination, PostCard } from "@/components/ui";
import { Reveal } from "@/components/motion";

export const metadata: Metadata = { title: "Berita & Pengumuman" };
export const revalidate = 300;

export default async function BeritaPage({ searchParams }: PageProps<"/[locale]/berita">) {
  const sp = await searchParams;
  const page = Number(sp.page ?? 1) || 1;
  const category = typeof sp.category === "string" ? sp.category : undefined;
  const tag = typeof sp.tag === "string" ? sp.tag : undefined;
  const search = typeof sp.q === "string" ? sp.q : undefined;

  // wp: Settings → Reading. Admin mengatur ini di Pengaturan Situs → Membaca.
  const perPage = await getPostsPerPage();

  const [posts, catSource, archives] = await Promise.all([
    getPosts({ page, per_page: perPage, category, tag, search }),
    getPosts({ per_page: 50 }),
    getArchives(),
  ]);
  const { current_page, last_page } = posts.meta;

  const categories = Array.from(
    new Map(
      catSource.data
        .filter((p) => p.category)
        .map((p) => [p.category!.slug, p.category!]),
    ).values(),
  );
  const popular = [...catSource.data]
    .sort((a, b) => (b.views ?? 0) - (a.views ?? 0))
    .slice(0, 5)
    .filter((p) => (p.views ?? 0) > 0);
  const catHref = (slug?: string) => {
    const params = new URLSearchParams();
    if (search) params.set("q", search);
    if (slug) params.set("category", slug);
    const s = params.toString();
    return s ? `/berita?${s}` : "/berita";
  };

  const qs = (p: number) => {
    const params = new URLSearchParams();
    if (category) params.set("category", category);
    if (tag) params.set("tag", tag);
    if (search) params.set("q", search);
    params.set("page", String(p));
    return `/berita?${params}`;
  };

  return (
    <>
      <PageHeader
        eyebrow="Informasi"
        title="Berita & Pengumuman"
        subtitle="Kabar, kegiatan, dan pengumuman terbaru dari madrasah."
        breadcrumb={[{ label: "Berita" }]}
      />
      <Container className="grid gap-10 section-y lg:grid-cols-[1fr_16rem] lg:items-start">
        <div className="min-w-0">
        <form className="mb-8 flex gap-2" action="/berita">
          <input
            type="search"
            name="q"
            defaultValue={search}
            placeholder="Cari berita…"
            className="min-h-11 w-full max-w-sm rounded-xl border border-border bg-surface px-4 text-sm shadow-sm outline-none focus:border-brand"
          />
          <button className="min-h-11 rounded-xl bg-brand px-5 text-sm font-semibold text-on-brand">
            Cari
          </button>
        </form>

        {categories.length > 0 && (
          <div className="mb-8 flex flex-wrap gap-2">
            <Link
              href={catHref()}
              className={`rounded-full border px-3.5 py-1.5 text-sm font-semibold transition ${
                !category
                  ? "border-brand bg-brand text-on-brand"
                  : "border-border bg-surface text-ink-soft hover:border-brand hover:text-brand"
              }`}
            >
              Semua
            </Link>
            {categories.map((c) => (
              <Link
                key={c.slug}
                href={catHref(c.slug)}
                className={`rounded-full border px-3.5 py-1.5 text-sm font-semibold transition ${
                  category === c.slug
                    ? "border-brand bg-brand text-on-brand"
                    : "border-border bg-surface text-ink-soft hover:border-brand hover:text-brand"
                }`}
              >
                {c.name}
              </Link>
            ))}
          </div>
        )}

        {tag && (
          <p className="mb-6 flex items-center gap-2 text-sm text-ink-soft">
            <span>Menampilkan berita bertag</span>
            <Link
              href={`/berita/tag/${encodeURIComponent(tag)}`}
              className="rounded-full border border-brand bg-brand-light px-3 py-1 text-xs font-bold text-brand-dark hover:underline"
            >
              #{tag}
            </Link>
            <Link href="/berita" className="text-xs font-semibold text-brand hover:underline">
              hapus filter
            </Link>
          </p>
        )}

        {posts.data.length === 0 ? (
          <EmptyState>Belum ada berita yang cocok.</EmptyState>
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
        </div>

        {(popular.length >= 3 || archives.length > 0) && (
          <aside className="space-y-6 lg:sticky lg:top-24">
            {popular.length >= 3 && (
            <div className="card p-5">
              <p className="mb-3 flex items-center gap-2 text-[0.7rem] font-bold uppercase tracking-wide text-ink-muted">
                <span className="h-1.5 w-1.5 rounded-full bg-accent" />
                Paling dibaca
              </p>
              <ol className="space-y-3">
                {popular.map((p, i) => (
                  <li key={p.id}>
                    <Link href={`/berita/${p.slug}`} className="group flex gap-3">
                      <span className="text-lg font-extrabold tabular-nums text-brand/40">
                        {i + 1}
                      </span>
                      <span className="min-w-0">
                        <span className="line-clamp-2 text-sm font-semibold text-foreground transition group-hover:text-brand">
                          {p.title}
                        </span>
                        <span className="mt-0.5 block text-xs text-ink-muted">
                          {(p.views ?? 0).toLocaleString("id-ID")}× dibaca
                        </span>
                      </span>
                    </Link>
                  </li>
                ))}
              </ol>
            </div>
            )}

            {/* Widget "Archives" ala WordPress — arsip berita per bulan. */}
            {archives.length > 0 && (
              <div className="card p-5">
                <p className="mb-3 flex items-center gap-2 text-[0.7rem] font-bold uppercase tracking-wide text-ink-muted">
                  <span className="h-1.5 w-1.5 rounded-full bg-brand" />
                  Arsip
                </p>
                <ul className="space-y-1">
                  {archives.slice(0, 12).map((a) => (
                    <li key={`${a.year}-${a.month}`}>
                      <Link
                        href={`/berita/arsip/${a.year}/${String(a.month).padStart(2, "0")}`}
                        className="group flex items-center justify-between gap-2 rounded-md px-2 py-1.5 text-sm transition hover:bg-brand-light"
                      >
                        <span className="font-medium text-foreground transition group-hover:text-brand">
                          {a.label}
                        </span>
                        <span className="shrink-0 rounded-full bg-surface-muted px-2 py-0.5 text-xs tabular-nums text-ink-muted">
                          {a.posts_count}
                        </span>
                      </Link>
                    </li>
                  ))}
                </ul>
              </div>
            )}
          </aside>
        )}
      </Container>
    </>
  );
}
