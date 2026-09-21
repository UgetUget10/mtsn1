import Link from "next/link";
import type { PageWithBlocks } from "@/lib/types";
import { Container, PageHeader } from "@/components/ui";
import { TableOfContents } from "@/components/features-data";
import { BlockRenderer } from "@/components/blocks/block-renderer";
import { formatDate } from "@/lib/format";

/**
 * Merender halaman "Profil" sesuai template ala WordPress
 * (Page Attributes → Template). Empat varian:
 *
 * - default      : lebar teks terbatas + sidebar daftar isi (TOC).
 * - full-width   : lebar penuh, tanpa sidebar.
 * - sidebar-nav  : sidebar berisi navigasi sub-halaman (bukan TOC).
 * - landing      : tanpa PageHeader/breadcrumb — blok tampil penuh dari atas.
 */
export function PageTemplateRenderer({
  page,
  siblingPages = [],
}: {
  page: PageWithBlocks;
  /** Halaman lain dengan induk yang sama (bukan anak sendiri) — dipakai
   * hanya bila halaman ini tak punya anak, supaya pengunjung tetap bisa
   * meloncat ke bagian lain dalam grup yang sama (mis. dari "Visi dan Misi"
   * ke "Sejarah" / "Struktur Organisasi" di grup "Profil Madrasah"). */
  siblingPages?: { slug: string; title: string }[];
}) {
  const template = page.template ?? "default";

  const updatedNote = page.updated_at ? (
    <p className="mb-6 text-xs font-semibold uppercase tracking-wide text-ink-muted">
      Terakhir diperbarui: {formatDate(page.updated_at)}
    </p>
  ) : null;

  const relatedPages =
    page.children.length > 0 ? (
      <nav className="mt-12 border-t border-border pt-6">
        <p className="mb-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">
          Halaman terkait
        </p>
        <ul className="grid gap-3 sm:grid-cols-2">
          {page.children.map((c) => (
            <li key={c.slug}>
              <Link
                href={`/profil/${c.slug}`}
                className="group flex items-center gap-2 rounded-lg border border-border bg-surface px-4 py-3 text-sm font-semibold text-brand-dark transition hover:border-brand hover:bg-brand-light"
              >
                <span className="flex-1">{c.title}</span>
                <span className="arrow-shift">→</span>
              </Link>
            </li>
          ))}
        </ul>
      </nav>
    ) : siblingPages.length > 0 ? (
      <nav className="mt-12 border-t border-border pt-6">
        <p className="mb-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">
          Lihat juga di {page.ancestors.at(-1)?.title ?? "grup ini"}
        </p>
        <ul className="grid gap-3 sm:grid-cols-2">
          {siblingPages.map((s) => (
            <li key={s.slug}>
              <Link
                href={`/profil/${s.slug}`}
                className="group flex items-center gap-2 rounded-lg border border-border bg-surface px-4 py-3 text-sm font-semibold text-brand-dark transition hover:border-brand hover:bg-brand-light"
              >
                <span className="flex-1">{s.title}</span>
                <span className="arrow-shift">→</span>
              </Link>
            </li>
          ))}
        </ul>
      </nav>
    ) : null;

  const backLink = (
    <div className="mt-10 border-t border-border pt-6">
      <Link
        href={page.parent ? `/profil/${page.parent}` : "/profil"}
        className="inline-flex items-center gap-1.5 rounded-lg border border-border bg-surface px-4 py-2 text-sm font-semibold text-brand-dark transition hover:border-brand hover:bg-brand-light"
      >
        ← {page.parent ? "Halaman induk" : "Semua halaman profil"}
      </Link>
    </div>
  );

  const header =
    template === "landing" ? null : (
      <PageHeader
        eyebrow="Profil Madrasah"
        title={page.title}
        subtitle={page.meta_description ?? undefined}
        breadcrumb={[
          { label: "Profil", href: "/profil" },
          ...page.ancestors.map((a) => ({ label: a.title, href: `/profil/${a.slug}` })),
          { label: page.title },
        ]}
      />
    );

  /* ---------- landing: blok penuh, tanpa kerangka artikel ---------- */
  if (template === "landing") {
    return (
      <article>
        <BlockRenderer blocks={page.blocks} />
        <Container className="section-y">
          {relatedPages}
          {backLink}
        </Container>
      </article>
    );
  }

  /* ---------- full-width: satu kolom lebar, tanpa sidebar ---------- */
  if (template === "full-width") {
    return (
      <>
        {header}
        <Container className="section-y">
          <article className="min-w-0">
            {updatedNote}
            <BlockRenderer blocks={page.blocks} />
            {relatedPages}
            {backLink}
          </article>
        </Container>
      </>
    );
  }

  /* ---------- sidebar-nav: sidebar = menu sub-halaman ---------- */
  if (template === "sidebar-nav") {
    const navItems = page.children.length > 0 ? page.children : page.ancestors;
    return (
      <>
        {header}
        <Container className="grid gap-10 section-y lg:grid-cols-[15rem_1fr] lg:items-start">
          <aside className="hidden lg:sticky lg:top-24 lg:block">
            <p className="mb-3 text-xs font-semibold uppercase tracking-wide text-ink-muted">
              Di bagian ini
            </p>
            <ul className="space-y-1 border-l border-border">
              {[{ title: page.title, slug: page.slug }, ...navItems].map((item) => (
                <li key={item.slug}>
                  <Link
                    href={`/profil/${item.slug}`}
                    aria-current={item.slug === page.slug ? "page" : undefined}
                    className={`-ml-px block border-l-2 py-1.5 pl-4 text-sm transition ${
                      item.slug === page.slug
                        ? "border-brand font-semibold text-brand-dark"
                        : "border-transparent text-ink-muted hover:border-brand/40 hover:text-brand-dark"
                    }`}
                  >
                    {item.title}
                  </Link>
                </li>
              ))}
            </ul>
          </aside>
          <article className="min-w-0 max-w-3xl">
            {updatedNote}
            <BlockRenderer blocks={page.blocks} />
            {backLink}
          </article>
        </Container>
      </>
    );
  }

  /* ---------- default: teks terbatas + TOC ---------- */
  return (
    <>
      {header}
      <Container className="grid gap-10 section-y lg:grid-cols-[1fr_15rem] lg:items-start">
        <article className="min-w-0 max-w-3xl">
          {updatedNote}
          <BlockRenderer blocks={page.blocks} />
          {relatedPages}
          {backLink}
        </article>
        <aside className="hidden lg:sticky lg:top-24 lg:block">
          <TableOfContents />
        </aside>
      </Container>
    </>
  );
}
