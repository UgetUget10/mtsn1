import type { Metadata } from "next";
import { getPage } from "@/lib/api";
import { PageBody, PageHeader } from "@/components/ui";
import { SectionNav } from "@/components/section-nav";
import { BlockRenderer } from "@/components/blocks/block-renderer";
import { akademikMenu } from "@/lib/section-menus";
import type { Block } from "@/lib/types";

export const metadata: Metadata = { title: "Aplikasi Digital Madrasah" };
export const revalidate = 600;

const fallbackBlocks: Block[] = [
  {
    type: "card_grid",
    data: {
      heading: null,
      columns: 4,
      cards: [
        { title: "Perpustakaan Digital", description: null, icon: "M4 5a2 2 0 0 1 2-2h9v18H6a2 2 0 0 1-2-2zM15 3h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-3", image: null, href: null },
        { title: "Rapor Digital (RDM)", description: null, icon: "M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zM9 13h6M9 17h6", image: null, href: null },
        { title: "Absensi Digital", description: null, icon: "M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11", image: null, href: null },
        { title: "E-Learning", description: null, icon: "M2 3h20v14H2zM8 21h8M12 17v4", image: null, href: null },
        { title: "SIPAMAD", description: null, icon: "M12 2l3 7h7l-5.5 4 2 7L12 17l-6.5 5 2-7L2 9h7z", image: null, href: null },
      ],
    },
  },
];

export default async function AplikasiPage() {
  const page = await getPage("akademik-aplikasi").catch(() => null);
  const blocks = page?.blocks?.length ? page.blocks : fallbackBlocks;

  return (
    <>
      <PageHeader
        eyebrow="Akademik"
        title="Aplikasi Digital Madrasah"
        subtitle="Layanan digital untuk peserta didik, orang tua, guru, dan tenaga kependidikan."
        breadcrumb={[{ label: "Akademik", href: "/akademik" }, { label: "Aplikasi Digital" }]}
      />
      <PageBody>
        <SectionNav items={akademikMenu} current="/akademik/aplikasi" className="mb-8" />
        <BlockRenderer blocks={blocks} />
      </PageBody>
    </>
  );
}
