import type { Metadata } from "next";
import { getPage, getSettings } from "@/lib/api";
import { Button, PageBody, PageHeader } from "@/components/ui";
import { SectionNav } from "@/components/section-nav";
import { BlockRenderer } from "@/components/blocks/block-renderer";
import { ppdbMenu } from "@/lib/section-menus";
import type { Block } from "@/lib/types";

export const metadata: Metadata = { title: "Berkas Persyaratan PMBM" };
export const revalidate = 600;

const fallbackBlocks: Block[] = [
  {
    type: "checklist",
    data: {
      heading: null,
      items: [
        { text: "Kartu Keluarga" },
        { text: "Akta Kelahiran" },
        { text: "Rapor SD/MI" },
        { text: "Pas foto terbaru" },
        { text: "Sertifikat prestasi (bila ada)" },
        { text: "Surat keterangan lulus / ijazah" },
      ],
    },
  },
];

export default async function BerkasPage() {
  const settings = await getSettings().catch(() => ({}) as Record<string, string>);
  const page = await getPage("ppdb-berkas").catch(() => null);
  const blocks = page?.blocks?.length ? page.blocks : fallbackBlocks;

  return (
    <>
      <PageHeader
        eyebrow="PMBM"
        title="Berkas Persyaratan"
        subtitle="Dokumen yang perlu disiapkan calon murid sebelum mendaftar."
        breadcrumb={[{ label: "PMBM", href: "/ppdb" }, { label: "Berkas Persyaratan" }]}
      />
      <PageBody>
        <SectionNav items={ppdbMenu} current="/ppdb/berkas" className="mb-8" />
        <div className="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
          <div className="card p-6">
            <BlockRenderer blocks={blocks} />
          </div>
          <div className="card bg-brand-light p-6 text-sm text-brand-darker">
            <p className="font-bold">Butuh bantuan?</p>
            {settings.phone && <p className="mt-1">{settings.phone}</p>}
            {settings.email && <p>{settings.email}</p>}
            <Button href="/kontak" variant="outline" className="mt-3">
              Hubungi panitia
            </Button>
          </div>
        </div>
      </PageBody>
    </>
  );
}
