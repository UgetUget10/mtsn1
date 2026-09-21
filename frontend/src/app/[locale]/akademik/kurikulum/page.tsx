import type { Metadata } from "next";
import { getPage } from "@/lib/api";
import { PageBody, PageHeader } from "@/components/ui";
import { SectionNav } from "@/components/section-nav";
import { BlockRenderer } from "@/components/blocks/block-renderer";
import { akademikMenu } from "@/lib/section-menus";
import type { Block } from "@/lib/types";

export const metadata: Metadata = { title: "Kurikulum" };
export const revalidate = 600;

const fallbackBlocks: Block[] = [
  {
    type: "card_grid",
    data: {
      heading: null,
      columns: 2,
      cards: [
        { title: "Kurikulum Merdeka", description: "Diterapkan bertahap dengan penguatan profil pelajar Rahmatan lil ‘Alamin.", icon: null, image: null, href: null },
        { title: "Muatan Keagamaan", description: "Al-Qur'an Hadis, Akidah Akhlak, Fikih, SKI, dan Bahasa Arab.", icon: null, image: null, href: null },
        { title: "Kelas Program", description: "Tahfidz, Olimpiade/Sains, Bilingual, dan CBI (akselerasi).", icon: null, image: null, href: null },
        { title: "Ekstrakurikuler", description: "Wajib (Pramuka) dan pilihan sesuai minat–bakat.", icon: null, image: null, href: null },
      ],
    },
  },
];

export default async function KurikulumPage() {
  const page = await getPage("akademik-kurikulum").catch(() => null);
  const blocks = page?.blocks?.length ? page.blocks : fallbackBlocks;

  return (
    <>
      <PageHeader
        eyebrow="Akademik"
        title="Kurikulum"
        subtitle="Struktur kurikulum yang memadukan Kurikulum Merdeka dengan penguatan muatan keagamaan."
        breadcrumb={[{ label: "Akademik", href: "/akademik" }, { label: "Kurikulum" }]}
      />
      <PageBody>
        <SectionNav items={akademikMenu} current="/akademik/kurikulum" className="mb-8" />
        <BlockRenderer blocks={blocks} />
      </PageBody>
    </>
  );
}
