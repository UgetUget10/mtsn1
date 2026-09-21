import type { Metadata } from "next";
import { getPage } from "@/lib/api";
import { PageBody, PageHeader } from "@/components/ui";
import { SectionNav } from "@/components/section-nav";
import { BlockRenderer } from "@/components/blocks/block-renderer";
import { layananMenu } from "@/lib/section-menus";
import type { Block } from "@/lib/types";

export const metadata: Metadata = {
  title: "Layanan Publik",
  description:
    "Standar layanan, SOP PTSP, maklumat pelayanan, survei kepuasan, dan kanal pengaduan MTsN 1 Kota Malang.",
};
export const revalidate = 600;

const fallbackBlocks: Block[] = [
  {
    type: "hub_grid",
    data: {
      numbered: true,
      items: [
        { title: "Standar Layanan", desc: "Jenis layanan, persyaratan, waktu, dan biaya.", href: "/layanan/standar", icon: "M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" },
        { title: "SOP PTSP", desc: "Alur Pelayanan Terpadu Satu Pintu.", href: "/layanan/sop", icon: "M3 21h18M6 21V7l6-4 6 4v14M10 21v-5h4v5" },
        { title: "Maklumat Pelayanan", desc: "Janji layanan madrasah kepada masyarakat.", href: "/layanan/maklumat", icon: "M4 4h16v12H5.2L4 17.2zM8 9h8M8 12h5" },
        { title: "Survei Kepuasan", desc: "SKM / SPKP dan Survei Persepsi Anti Korupsi.", href: "/layanan/survei", icon: "M3 3v18h18M7 15l3-3 3 3 5-6" },
        { title: "Pengaduan Masyarakat", desc: "Kanal pengaduan resmi & SP4N-LAPOR.", href: "/layanan/pengaduan", icon: "M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" },
        { title: "E-Repository", desc: "Dokumen, formulir, dan panduan yang dapat diunduh.", href: "/dokumen", icon: "M4 5a2 2 0 0 1 2-2h9v18H6a2 2 0 0 1-2-2zM15 3h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-3" },
      ],
    },
  },
];

export default async function LayananPage() {
  const page = await getPage("layanan-index").catch(() => null);
  const blocks = page?.blocks?.length ? page.blocks : fallbackBlocks;

  return (
    <>
      <PageHeader
        eyebrow="Layanan"
        title="Layanan Publik"
        subtitle="Komitmen menghadirkan pelayanan yang cepat, transparan, dan akuntabel bagi peserta didik, orang tua, dan masyarakat."
        breadcrumb={[{ label: "Layanan" }]}
      />
      <PageBody>
        <SectionNav items={layananMenu} current="/layanan" className="mb-8" />
        <BlockRenderer blocks={blocks} />
      </PageBody>
    </>
  );
}
