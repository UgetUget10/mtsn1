import type { Metadata } from "next";
import { getPage } from "@/lib/api";
import { PageBody, PageHeader } from "@/components/ui";
import { SectionNav } from "@/components/section-nav";
import { BlockRenderer } from "@/components/blocks/block-renderer";
import { layananMenu } from "@/lib/section-menus";
import type { Block } from "@/lib/types";

export const metadata: Metadata = { title: "SOP Pelayanan Terpadu Satu Pintu" };
export const revalidate = 600;

const fallbackBlocks: Block[] = [
  {
    type: "steps",
    data: {
      heading: null,
      items: [
        { title: "Ambil Nomor Antrean", description: "Datang ke loket PTSP atau ajukan permohonan daring." },
        { title: "Verifikasi Berkas", description: "Petugas memeriksa kelengkapan dan keabsahan dokumen." },
        { title: "Proses Layanan", description: "Permohonan diproses sesuai jenis dan standar waktu layanan." },
        { title: "Penyerahan Hasil", description: "Hasil layanan diserahkan langsung atau dikirim daring." },
      ],
    },
  },
];

export default async function SopPage() {
  const page = await getPage("layanan-sop").catch(() => null);
  const blocks = page?.blocks?.length ? page.blocks : fallbackBlocks;

  return (
    <>
      <PageHeader
        eyebrow="Layanan"
        title="SOP Pelayanan Terpadu Satu Pintu"
        subtitle="Empat langkah baku dalam setiap permohonan layanan di PTSP madrasah."
        breadcrumb={[{ label: "Layanan", href: "/layanan" }, { label: "SOP PTSP" }]}
      />
      <PageBody>
        <SectionNav items={layananMenu} current="/layanan/sop" className="mb-8" />
        <BlockRenderer blocks={blocks} />
      </PageBody>
    </>
  );
}
