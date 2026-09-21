import Link from "next/link";
import type { Metadata } from "next";
import { getPage } from "@/lib/api";
import { PageBody, PageHeader } from "@/components/ui";
import { SectionNav } from "@/components/section-nav";
import { BlockRenderer } from "@/components/blocks/block-renderer";
import { akademikMenu } from "@/lib/section-menus";
import type { Block } from "@/lib/types";

export const metadata: Metadata = { title: "Bimbingan & Konseling" };
export const revalidate = 600;

const fallbackBlocks: Block[] = [
  {
    type: "checklist",
    data: {
      heading: null,
      items: [
        { text: "Bimbingan pribadi & sosial" },
        { text: "Bimbingan belajar" },
        { text: "Bimbingan karier & studi lanjut" },
        { text: "Konseling individu / kelompok" },
        { text: "Layanan alih tangan kasus (referal)" },
      ],
    },
  },
];

export default async function BKPage() {
  const page = await getPage("akademik-bk").catch(() => null);
  const blocks = page?.blocks?.length ? page.blocks : fallbackBlocks;

  return (
    <>
      <PageHeader
        eyebrow="Akademik"
        title="Bimbingan & Konseling"
        subtitle="Layanan BK mendampingi peserta didik agar berkembang optimal secara pribadi, sosial, belajar, dan karier."
        breadcrumb={[{ label: "Akademik", href: "/akademik" }, { label: "Bimbingan Konseling" }]}
      />
      <PageBody>
        <SectionNav items={akademikMenu} current="/akademik/bk" className="mb-8" />
        <BlockRenderer blocks={blocks} />
        <Link href="/kontak" className="mt-6 inline-flex items-center gap-1.5 text-sm font-bold text-brand">
          Hubungi guru BK <span className="arrow-shift">→</span>
        </Link>
      </PageBody>
    </>
  );
}
