import Link from "next/link";
import type { Metadata } from "next";
import { search } from "@/lib/api";
import { Container, EmptyState, PageHeader } from "@/components/ui";
import { Reveal } from "@/components/motion";
import { SiteSearch } from "@/components/site-search";

export const metadata: Metadata = { title: "Pencarian", robots: { index: false } };
export const revalidate = 120;

export default async function PencarianPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const sp = await searchParams;
  const q = (typeof sp.q === "string" ? sp.q : "").trim();

  const result = q ? await search(q) : null;

  return (
    <>
      <PageHeader
        eyebrow="Pencarian"
        title={q ? `Hasil untuk “${q}”` : "Cari di situs ini"}
        subtitle={
          result
            ? `${result.total} hasil ditemukan di ${result.groups.length} kategori.`
            : "Ketik kata kunci untuk mencari berita, halaman, agenda, dokumen, dan lainnya."
        }
        breadcrumb={[{ label: "Pencarian" }]}
      />

      <Container className="section-y">
        <div className="mx-auto mb-10 max-w-xl">
          <SiteSearch variant="block" />
        </div>

        {!q && <EmptyState>Mulai dengan mengetik kata kunci di kolom pencarian.</EmptyState>}

        {result && result.total === 0 && (
          <EmptyState>
            Tidak ada hasil untuk “{q}”. Coba kata kunci lain atau lihat{" "}
            <Link href="/berita" className="font-semibold text-brand">
              semua berita
            </Link>
            .
          </EmptyState>
        )}

        <div className="space-y-12">
          {result?.groups.map((group) => (
            <section key={group.label}>
              <div className="mb-5 flex items-baseline justify-between gap-3">
                <h2 className="text-h2">{group.label}</h2>
                <Link
                  href={group.href}
                  className="text-sm font-semibold text-brand hover:underline"
                >
                  Lihat semua
                </Link>
              </div>
              <ul className="divide-y divide-border overflow-hidden rounded-xl border border-border bg-surface">
                {group.items.map((hit, i) => (
                  <Reveal key={`${hit.href}-${i}`} as="li" delay={(i % 4) * 40} direction="up">
                    <Link
                      href={hit.href}
                      className="block p-5 transition hover:bg-surface-muted"
                    >
                      <span className="flex items-start gap-3">
                        <span className="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-brand" />
                        <span className="min-w-0">
                          <span className="block font-bold text-foreground">{hit.title}</span>
                          {hit.excerpt && (
                            <span className="mt-0.5 line-clamp-2 block text-sm text-ink-soft">
                              {hit.excerpt}
                            </span>
                          )}
                          {hit.meta && (
                            <span className="mt-1 block text-xs text-ink-muted">{hit.meta}</span>
                          )}
                        </span>
                      </span>
                    </Link>
                  </Reveal>
                ))}
              </ul>
            </section>
          ))}
        </div>
      </Container>
    </>
  );
}
