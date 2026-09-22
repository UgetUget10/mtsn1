import Image from "@/components/media-image";
import type { Metadata } from "next";
import { getGalleries } from "@/lib/api";
import { Container, EmptyState, PageHeader } from "@/components/ui";
import { Reveal } from "@/components/motion";
import { formatDate } from "@/lib/format";

export const metadata: Metadata = { title: "Galeri" };
export const dynamic = "force-dynamic";

export default async function GaleriPage() {
  const galleries = await getGalleries();

  return (
    <>
      <PageHeader
        eyebrow="Dokumentasi"
        title="Galeri"
        subtitle="Dokumentasi kegiatan, prestasi, dan momen di madrasah."
        breadcrumb={[{ label: "Galeri" }]}
      />
      <Container className="section-y">
        {galleries.data.length === 0 ? (
          <EmptyState>Belum ada album galeri.</EmptyState>
        ) : (
          <div className="space-y-14">
            {galleries.data.map((g) => (
              <section key={g.slug}>
                <div className="mb-5 flex items-end gap-3">
                  <span className="h-px w-8 bg-brand/40" />
                  <div>
                    <h2 className="text-h2">{g.title}</h2>
                    {g.taken_on && (
                      <p className="text-sm text-ink-muted">{formatDate(g.taken_on)}</p>
                    )}
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                  {g.items.map((item, i) =>
                    item.type === "video" && item.url ? (
                      <Reveal key={i} delay={(i % 4) * 50} direction="scale">
                        <a
                          href={item.url}
                          target="_blank"
                          rel="noreferrer"
                          className="flex aspect-square items-center justify-center rounded-2xl bg-linear-to-br from-brand via-brand-dark to-brand-darker text-sm font-bold text-white transition hover:brightness-110"
                        >
                          <span className="flex items-center gap-2">
                            <span className="grid h-8 w-8 place-items-center rounded-full bg-white/20">▶</span>
                            Video
                          </span>
                        </a>
                      </Reveal>
                    ) : item.url ? (
                      <Reveal key={i} delay={(i % 4) * 50} direction="scale">
                        <div className="group relative aspect-square overflow-hidden rounded-2xl bg-surface-muted">
                          <Image
                            src={item.url}
                            alt={item.caption ?? g.title}
                            fill
                            className="object-cover transition duration-500 group-hover:scale-105"
                            sizes="25vw"
                          />
                        </div>
                      </Reveal>
                    ) : null,
                  )}
                </div>
              </section>
            ))}
          </div>
        )}
      </Container>
    </>
  );
}
