import type { Metadata } from "next";
import { getPage } from "@/lib/api";
import { PageBody, PageHeader } from "@/components/ui";
import { SectionNav } from "@/components/section-nav";
import { BlockRenderer } from "@/components/blocks/block-renderer";
import { akademikMenu } from "@/lib/section-menus";
import type { Block } from "@/lib/types";

export const metadata: Metadata = { title: "Program Unggulan" };
export const revalidate = 600;

const fallbackBlocks: Block[] = [
  {
    type: "card_grid",
    data: {
      heading: null,
      columns: 4,
      cards: [
        { title: "Kelas Tahfidz", description: "Tahfidz — Program hafalan Al-Qur'an bertingkat dengan pembimbing khusus dan target hafalan terukur.", icon: null, image: null, href: null },
        { title: "Kelas Olimpiade", description: "Sains — Pembinaan intensif menuju kompetisi sains dan matematika tingkat nasional serta internasional.", icon: null, image: null, href: null },
        { title: "Kelas Bilingual", description: "Bahasa — Penguatan Bahasa Arab dan Inggris sebagai pengantar sebagian mata pelajaran.", icon: null, image: null, href: null },
        { title: "Kelas CBI", description: "Akselerasi — Cerdas Berbakat Istimewa — program percepatan penyelesaian studi dalam dua tahun.", icon: null, image: null, href: null },
      ],
    },
  },
];

export default async function ProgramUnggulanPage() {
  const page = await getPage("akademik-program-unggulan").catch(() => null);
  const blocks = page?.blocks?.length ? page.blocks : fallbackBlocks;

  return (
    <>
      <PageHeader
        eyebrow="Akademik"
        title="Program Unggulan"
        subtitle="Kelas dan program yang disesuaikan dengan minat serta potensi peserta didik."
        breadcrumb={[{ label: "Akademik", href: "/akademik" }, { label: "Program Unggulan" }]}
      />
      <PageBody>
        <SectionNav items={akademikMenu} current="/akademik/program-unggulan" className="mb-8" />
        <BlockRenderer blocks={blocks} />
      </PageBody>
    </>
  );
}
