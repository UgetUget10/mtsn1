import type { Metadata } from "next";
import { getPage } from "@/lib/api";
import { PageBody, PageHeader } from "@/components/ui";
import { SectionNav } from "@/components/section-nav";
import { BlockRenderer } from "@/components/blocks/block-renderer";
import { akademikMenu } from "@/lib/section-menus";
import type { Block } from "@/lib/types";

export const metadata: Metadata = {
  title: "Akademik",
  description:
    "Kurikulum, proses pembelajaran, penilaian, bimbingan konseling, program unggulan, dan aplikasi digital MTsN 1 Kota Malang.",
};
export const revalidate = 600;

const fallbackBlocks: Block[] = [
  {
    type: "hub_grid",
    data: {
      numbered: true,
      items: [
        { title: "Kurikulum", desc: "Kurikulum Merdeka, muatan keagamaan, dan kelas program.", href: "/akademik/kurikulum", icon: "M12 3 2 8l10 5 10-5-10-5zM4 10v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6" },
        { title: "Proses Pembelajaran", desc: "Kegiatan belajar, kalender akademik, dan pengembangan diri.", href: "/akademik/pembelajaran", icon: "M8 2v4M16 2v4M3 10h18M5 6h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z" },
        { title: "Penilaian & Rapor", desc: "Asesmen formatif, sumatif, dan Rapor Digital (RDM).", href: "/akademik/penilaian", icon: "M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zM9 13h6M9 17h6" },
        { title: "Bimbingan Konseling", desc: "Pendampingan pribadi, sosial, belajar, dan karier.", href: "/akademik/bk", icon: "M20 21a8 8 0 0 0-16 0M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z" },
        { title: "Program Unggulan", desc: "Tahfidz, Olimpiade, Bilingual, dan CBI (akselerasi).", href: "/akademik/program-unggulan", icon: "M12 3l8 4v5c0 5-3.4 8.5-8 9-4.6-.5-8-4-8-9V7l8-4z" },
        { title: "Aplikasi Digital", desc: "Perpustakaan digital, RDM, absensi, e-learning, SIPAMAD.", href: "/akademik/aplikasi", icon: "M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" },
      ],
    },
  },
];

export default async function AkademikPage() {
  const page = await getPage("akademik-index").catch(() => null);
  const blocks = page?.blocks?.length ? page.blocks : fallbackBlocks;

  return (
    <>
      <PageHeader
        eyebrow="Akademik"
        title="Layanan Akademik"
        subtitle="Kurikulum, proses pembelajaran, penilaian, dan pembinaan siswa yang menyeimbangkan iman, ilmu, dan amal."
        breadcrumb={[{ label: "Akademik" }]}
      />
      <PageBody>
        <SectionNav items={akademikMenu} current="/akademik" className="mb-8" />
        <BlockRenderer blocks={blocks} />
      </PageBody>
    </>
  );
}
