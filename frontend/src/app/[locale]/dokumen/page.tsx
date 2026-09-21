import type { Metadata } from "next";
import { getDocuments } from "@/lib/api";
import { Container, EmptyState, PageHeader } from "@/components/ui";
import { DokumenList } from "@/components/features-data";

export const metadata: Metadata = { title: "Dokumen & Unduhan" };
export const revalidate = 600;

export default async function DokumenPage({
  searchParams,
}: {
  searchParams: Promise<Record<string, string | string[] | undefined>>;
}) {
  const sp = await searchParams;
  const q = (typeof sp.q === "string" ? sp.q : "").trim();
  const docs = await getDocuments(q || undefined);

  return (
    <>
      <PageHeader
        eyebrow="Layanan Informasi"
        title="Dokumen & Unduhan"
        subtitle="Formulir, surat edaran, panduan, dan berkas resmi madrasah yang dapat diunduh."
        breadcrumb={[{ label: "Layanan" }, { label: "Dokumen" }]}
      />

      <Container className="section-y">
        <form action="/dokumen" className="mb-8 flex max-w-md gap-2">
          <input
            type="search"
            name="q"
            defaultValue={q}
            placeholder="Cari nama dokumen…"
            className="min-h-11 w-full rounded-xl border border-border bg-surface px-4 text-sm shadow-sm outline-none transition focus:border-brand"
          />
          <button className="btn-glow min-h-11 rounded-xl bg-brand px-5 text-sm font-semibold text-on-brand shadow-sm transition hover:bg-brand-dark">
            Cari
          </button>
        </form>

        {docs.data.length === 0 ? (
          <EmptyState>
            {q ? `Tidak ada dokumen yang cocok dengan "${q}".` : "Belum ada dokumen yang dipublikasikan."}
          </EmptyState>
        ) : (
          <DokumenList docs={docs.data} />
        )}
      </Container>
    </>
  );
}
