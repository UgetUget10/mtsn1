import type { Metadata } from "next";
import { getSettings } from "@/lib/api";
import { Badge, Button, HubGrid, PageBody, PageHeader, type HubItem } from "@/components/ui";
import { SectionNav } from "@/components/section-nav";
import { ppdbMenu } from "@/lib/section-menus";
import { formatDate, isDeadlinePassed } from "@/lib/format";

export const metadata: Metadata = { title: "PMBM — Penerimaan Murid Baru Madrasah" };
export const dynamic = "force-dynamic";

const items: HubItem[] = [
  { title: "Alur Pendaftaran", desc: "Empat tahap dari pembuatan akun hingga pengumuman.", href: "/ppdb/alur", icon: "M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" },
  { title: "Berkas Persyaratan", desc: "Dokumen yang perlu disiapkan calon peserta didik.", href: "/ppdb/berkas", icon: "M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zM9 13h6M9 17h6" },
  { title: "Jadwal & Pengumuman", desc: "Periode pendaftaran dan tanggal penting.", href: "/ppdb/jadwal", icon: "M8 2v4M16 2v4M3 10h18M5 6h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z" },
];

export default async function PpdbPage() {
  const settings = await getSettings().catch(() => ({}) as Record<string, string>);
  const deadline = settings.ppdb_deadline ? new Date(settings.ppdb_deadline) : null;
  const deadlinePassed = isDeadlinePassed(settings.ppdb_deadline);
  const isOpen = Boolean(settings.ppdb_url) && !deadlinePassed;

  return (
    <>
      <PageHeader
        eyebrow="Pendaftaran"
        title="Penerimaan Murid Baru Madrasah"
        subtitle="Informasi alur, persyaratan, jadwal, dan tautan pendaftaran murid baru."
        breadcrumb={[{ label: "PMBM" }]}
      />
      <PageBody>
        <SectionNav items={ppdbMenu} current="/ppdb" className="mb-8" />

        <div className="mb-6 flex flex-wrap items-center gap-3">
          {isOpen ? (
            <Badge tone="success">Pendaftaran Dibuka</Badge>
          ) : (
            <Badge tone="warning">{deadlinePassed ? "Pendaftaran Ditutup" : "Segera Dibuka"}</Badge>
          )}
          {deadline && !deadlinePassed && (
            <span className="text-sm text-ink-muted">
              Batas pendaftaran:{" "}
              <span className="tabular font-semibold text-foreground">{formatDate(settings.ppdb_deadline)}</span>
            </span>
          )}
        </div>

        <HubGrid items={items} numbered className="sm:grid-cols-3" />

        {settings.ppdb_url && (
          <div className="mt-8">
            <Button href={settings.ppdb_url} variant="accent" size="lg">
              Buka Portal Pendaftaran
            </Button>
          </div>
        )}
      </PageBody>
    </>
  );
}
