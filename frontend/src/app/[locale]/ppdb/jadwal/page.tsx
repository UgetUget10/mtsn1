import type { Metadata } from "next";
import { getSettings } from "@/lib/api";
import { Badge, Button, PageBody, PageHeader } from "@/components/ui";
import { SectionNav } from "@/components/section-nav";
import { ppdbMenu } from "@/lib/section-menus";
import { formatDate, isDeadlinePassed } from "@/lib/format";

export const metadata: Metadata = { title: "Jadwal & Pengumuman PMBM" };
export const revalidate = 600;

export default async function JadwalPage() {
  const settings = await getSettings().catch(() => ({}) as Record<string, string>);
  const deadline = settings.ppdb_deadline ? new Date(settings.ppdb_deadline) : null;
  const deadlinePassed = isDeadlinePassed(settings.ppdb_deadline);
  const isOpen = Boolean(settings.ppdb_url) && !deadlinePassed;

  return (
    <>
      <PageHeader
        eyebrow="PMBM"
        title="Jadwal & Pengumuman"
        subtitle="Periode pendaftaran dan tanggal penting PMBM tahun berjalan."
        breadcrumb={[{ label: "PMBM", href: "/ppdb" }, { label: "Jadwal & Pengumuman" }]}
      />
      <PageBody>
        <SectionNav items={ppdbMenu} current="/ppdb/jadwal" className="mb-8" />
        <div className="card p-6 sm:p-8">
          <div className="flex flex-wrap items-center gap-3">
            {isOpen ? (
              <Badge tone="success">Pendaftaran Dibuka</Badge>
            ) : (
              <Badge tone="warning">{deadlinePassed ? "Pendaftaran Ditutup" : "Segera Dibuka"}</Badge>
            )}
            {deadline && (
              <span className="text-sm text-ink-muted">
                Batas pendaftaran:{" "}
                <span className="tabular font-semibold text-foreground">{formatDate(settings.ppdb_deadline)}</span>
              </span>
            )}
          </div>
          <p className="measure mt-4 text-sm text-ink-soft">
            Jadwal PMBM mengikuti kalender resmi Kementerian Agama. Tanggal pendaftaran, seleksi,
            pengumuman, dan daftar ulang diumumkan melalui halaman ini, portal pendaftaran, dan media
            sosial resmi madrasah.
          </p>
          {settings.ppdb_url && (
            <Button href={settings.ppdb_url} variant="accent" className="mt-6">
              Buka Portal Pendaftaran
            </Button>
          )}
        </div>
      </PageBody>
    </>
  );
}
