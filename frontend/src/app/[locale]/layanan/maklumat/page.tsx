import type { Metadata } from "next";
import { getPage } from "@/lib/api";
import { PageBody, PageHeader } from "@/components/ui";
import { SectionNav } from "@/components/section-nav";
import { BlockRenderer } from "@/components/blocks/block-renderer";
import { layananMenu } from "@/lib/section-menus";
import type { Block } from "@/lib/types";

export const metadata: Metadata = { title: "Maklumat Pelayanan" };
export const revalidate = 600;

const fallbackBlocks: Block[] = [
  {
    type: "quote",
    data: {
      text: "Dengan ini kami menyatakan sanggup menyelenggarakan pelayanan sesuai standar pelayanan yang telah ditetapkan, dan apabila tidak menepati janji ini, kami siap menerima sanksi sesuai peraturan perundang-undangan yang berlaku.",
      attribution: null,
    },
  },
];

export default async function MaklumatPage() {
  const page = await getPage("layanan-maklumat").catch(() => null);
  const blocks = page?.blocks?.length ? page.blocks : fallbackBlocks;

  return (
    <>
      <PageHeader
        eyebrow="Layanan"
        title="Maklumat Pelayanan"
        subtitle="Pernyataan kesanggupan madrasah menyelenggarakan pelayanan sesuai standar."
        breadcrumb={[{ label: "Layanan", href: "/layanan" }, { label: "Maklumat Pelayanan" }]}
      />
      <PageBody>
        <SectionNav items={layananMenu} current="/layanan/maklumat" className="mb-8" />
        <BlockRenderer blocks={blocks} />
      </PageBody>
    </>
  );
}
