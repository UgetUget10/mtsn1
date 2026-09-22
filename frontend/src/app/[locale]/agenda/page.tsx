import type { Metadata } from "next";
import { getAgendas } from "@/lib/api";
import { Container, EmptyState, PageHeader } from "@/components/ui";
import { Reveal } from "@/components/motion";
import { MiniCalendar } from "@/components/features-more";
import { formatDateTime } from "@/lib/format";

export const metadata: Metadata = { title: "Agenda Kegiatan" };
export const dynamic = "force-dynamic";

/** Origin backend (tanpa /api/v1) — untuk berkas non-API seperti /agenda.ics. */
const BACKEND_ORIGIN = (
  process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8000/api/v1"
).replace(/\/api\/v1\/?$/, "");

export default async function AgendaPage() {
  const agendas = await getAgendas();

  return (
    <>
      <PageHeader
        eyebrow="Kalender"
        title="Agenda Kegiatan"
        subtitle="Jadwal kegiatan dan acara madrasah mendatang."
        breadcrumb={[{ label: "Agenda" }]}
      />
      <Container className="grid gap-10 section-y lg:grid-cols-[1fr_20rem] lg:items-start">
        <div>
        <div className="mb-6 flex justify-end">
          <a
            href={`${BACKEND_ORIGIN}/agenda.ics`}
            className="inline-flex items-center gap-2 rounded-lg border border-border bg-surface px-4 py-2 text-sm font-semibold text-brand-dark transition hover:border-brand hover:bg-brand-light"
          >
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
              <rect x="3" y="4" width="18" height="18" rx="2" />
              <path d="M16 2v4M8 2v4M3 10h18" />
            </svg>
            Langganan kalender (.ics)
          </a>
        </div>
        {agendas.length === 0 ? (
          <EmptyState>Belum ada agenda mendatang.</EmptyState>
        ) : (
          <ol className="relative space-y-4 border-l-2 border-brand-light pl-6 sm:pl-8">
            {agendas.map((a, i) => (
              <Reveal key={a.slug} as="li" delay={i * 60} direction="right" className="relative">
                <span className="absolute -left-[33px] top-5 h-4 w-4 rounded-full border-2 border-brand bg-surface sm:-left-[41px]" />
                <div className="card card-hover p-5">
                  <p className="flex items-center gap-2 text-sm font-semibold text-brand">
                    <span className="h-1.5 w-1.5 rounded-full bg-accent" />
                    {formatDateTime(a.start_at)}
                  </p>
                  <h3 className="mt-1.5 text-lg font-bold text-foreground">{a.title}</h3>
                  {a.location && (
                    <p className="mt-0.5 text-sm text-ink-muted">📍 {a.location}</p>
                  )}
                  {a.description && (
                    <p className="mt-2 text-sm text-ink-soft">{a.description}</p>
                  )}
                </div>
              </Reveal>
            ))}
          </ol>
        )}
        </div>
        <aside className="lg:sticky lg:top-24">
          <MiniCalendar dates={agendas.map((a) => a.start_at)} />
        </aside>
      </Container>
    </>
  );
}
