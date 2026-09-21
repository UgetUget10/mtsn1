import type { Metadata } from "next";
import { getPage } from "@/lib/api";
import { PageBody, PageHeader } from "@/components/ui";
import { SectionNav } from "@/components/section-nav";
import { BlockRenderer } from "@/components/blocks/block-renderer";
import { layananMenu } from "@/lib/section-menus";
import type { Block } from "@/lib/types";

export const metadata: Metadata = { title: "Pengaduan Masyarakat" };
export const revalidate = 600;

const fallbackBlocks: Block[] = [
  {
    type: "link_cards",
    data: {
      heading: null,
      items: [
        { title: "Formulir Pengaduan", description: "Kirim pengaduan langsung ke madrasah.", href: "/kontak", external: false },
        { title: "SP4N-LAPOR!", description: "Kanal pengaduan nasional pemerintah.", href: "https://www.lapor.go.id/", external: true },
        { title: "Whistleblowing System", description: "Laporan dugaan pelanggaran secara rahasia.", href: "/area-zi/wbs", external: false },
      ],
    },
  },
];

export default async function PengaduanPage() {
  const page = await getPage("layanan-pengaduan").catch(() => null);
  const blocks = page?.blocks?.length ? page.blocks : fallbackBlocks;

  return (
    <>
      <PageHeader
        eyebrow="Layanan"
        title="Pengaduan Masyarakat"
        subtitle="Sampaikan pengaduan, aspirasi, atau laporan dugaan pelanggaran melalui kanal resmi berikut."
        breadcrumb={[{ label: "Layanan", href: "/layanan" }, { label: "Pengaduan" }]}
      />
      <PageBody>
        <SectionNav items={layananMenu} current="/layanan/pengaduan" className="mb-8" />
        <BlockRenderer blocks={blocks} />
      </PageBody>
    </>
  );
}
