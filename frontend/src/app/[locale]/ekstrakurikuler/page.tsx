import Image from "@/components/media-image";
import type { Metadata } from "next";
import { getExtracurriculars } from "@/lib/api";
import { Container, EmptyState, PageHeader } from "@/components/ui";
import { Reveal } from "@/components/motion";

export const metadata: Metadata = { title: "Ekstrakurikuler" };
export const dynamic = "force-dynamic";

export default async function EkskulPage() {
  const items = await getExtracurriculars();

  return (
    <>
      <PageHeader
        eyebrow="Bakat & Minat"
        title="Ekstrakurikuler"
        subtitle="Wadah bagi siswa mengembangkan minat, bakat, dan kepemimpinan."
        breadcrumb={[{ label: "Ekstrakurikuler" }]}
      />
      <Container className="section-y">
        {items.length === 0 ? (
          <EmptyState>Data belum tersedia.</EmptyState>
        ) : (
          <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {items.map((e, i) => (
              <Reveal key={e.slug} delay={(i % 3) * 70} direction="up">
                <article className="group card card-hover flex h-full flex-col overflow-hidden">
                  <div className="relative aspect-16/10 overflow-hidden bg-surface-muted">
                    {e.image ? (
                      <Image
                        src={e.image}
                        alt={e.name}
                        fill
                        className="object-cover transition duration-500 group-hover:scale-105"
                        sizes="33vw"
                      />
                    ) : (
                      <div className="flex h-full items-center justify-center bg-linear-to-br from-brand via-brand-dark to-brand-darker text-3xl font-black text-white/90">
                        {e.name.charAt(0)}
                      </div>
                    )}
                  </div>
                  <div className="flex flex-1 flex-col p-5">
                    <h3 className="font-bold text-foreground">{e.name}</h3>
                    {e.schedule && (
                      <p className="mt-1 flex items-center gap-1.5 text-sm font-medium text-brand">
                        <span className="h-1.5 w-1.5 rounded-full bg-accent" />
                        {e.schedule}
                      </p>
                    )}
                    {e.coach && (
                      <p className="mt-0.5 text-xs text-ink-muted">Pembina: {e.coach}</p>
                    )}
                    {e.description && (
                      <p className="mt-2 flex-1 text-sm text-ink-soft">{e.description}</p>
                    )}
                  </div>
                </article>
              </Reveal>
            ))}
          </div>
        )}
      </Container>
    </>
  );
}
