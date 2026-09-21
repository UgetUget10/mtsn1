import type { Metadata } from "next";
import { getPage } from "@/lib/api";
import { PageBody, PageHeader } from "@/components/ui";
import { SectionNav } from "@/components/section-nav";
import { BlockRenderer } from "@/components/blocks/block-renderer";
import { akademikMenu } from "@/lib/section-menus";
import type { Block } from "@/lib/types";

export const metadata: Metadata = { title: "Penilaian & Rapor" };
export const revalidate = 600;

const fallbackBlocks: Block[] = [
  {
    type: "rich_text",
    data: {
      heading: null,
      body: "<p>Penilaian dilakukan secara menyeluruh melalui asesmen formatif dan sumatif. Hasil belajar dilaporkan setiap tengah dan akhir semester melalui rapor digital (RDM) serta pertemuan dengan orang tua.</p>",
    },
  },
  {
    type: "card_grid",
    data: {
      heading: null,
      columns: 3,
      cards: [
        { title: "Asesmen Formatif", description: "Umpan balik berkelanjutan selama proses belajar.", icon: null, image: null, href: null },
        { title: "Asesmen Sumatif", description: "Penilaian capaian pada akhir lingkup materi / semester.", icon: null, image: null, href: null },
        { title: "Rapor Digital (RDM)", description: "Laporan hasil belajar yang dapat diakses orang tua.", icon: null, image: null, href: null },
      ],
    },
  },
];

export default async function PenilaianPage() {
  const page = await getPage("akademik-penilaian").catch(() => null);
  const blocks = page?.blocks?.length ? page.blocks : fallbackBlocks;

  return (
    <>
      <PageHeader
        eyebrow="Akademik"
        title="Penilaian & Rapor"
        subtitle="Penilaian menyeluruh atas sikap, pengetahuan, dan keterampilan peserta didik."
        breadcrumb={[{ label: "Akademik", href: "/akademik" }, { label: "Penilaian & Rapor" }]}
      />
      <PageBody>
        <SectionNav items={akademikMenu} current="/akademik/penilaian" className="mb-8" />
        <BlockRenderer blocks={blocks} />
      </PageBody>
    </>
  );
}
