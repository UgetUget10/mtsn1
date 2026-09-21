import type { Metadata } from "next";
import { getPage } from "@/lib/api";
import { PageBody, PageHeader } from "@/components/ui";
import { SectionNav } from "@/components/section-nav";
import { BlockRenderer } from "@/components/blocks/block-renderer";
import { akademikMenu } from "@/lib/section-menus";
import type { Block } from "@/lib/types";

export const metadata: Metadata = { title: "Proses Pembelajaran" };
export const revalidate = 600;

const fallbackBlocks: Block[] = [
  {
    type: "rich_text",
    data: {
      heading: null,
      body: "<p>Kegiatan belajar mengajar berlangsung pada hari kerja dengan pendekatan pembelajaran berdiferensiasi. Kalender akademik, jadwal kegiatan, dan pengumuman disampaikan melalui kanal resmi madrasah.</p>",
    },
  },
  {
    type: "card_grid",
    data: {
      heading: null,
      columns: 4,
      cards: [
        { title: "Kalender & Agenda", description: "Jadwal kegiatan, kalender akademik, dan pengumuman belajar mengajar.", icon: "M8 2v4M16 2v4M3 10h18M5 6h14a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2z", image: null, href: "/agenda" },
        { title: "Kesiswaan & Ekstrakurikuler", description: "Pembinaan karakter, OSIS, dan wadah pengembangan minat–bakat.", icon: "M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z", image: null, href: "/ekstrakurikuler" },
        { title: "Prestasi Akademik", description: "Capaian siswa pada olimpiade, kompetisi sains, dan lomba.", icon: "M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0zM7 6H4v2a3 3 0 0 0 3 3M17 6h3v2a3 3 0 0 1-3 3", image: null, href: "/prestasi" },
        { title: "Berita Akademik", description: "Kabar kegiatan pembelajaran, penilaian, dan pengumuman resmi.", icon: "M4 4h16v16H4zM8 8h8M8 12h8M8 16h5", image: null, href: "/berita" },
      ],
    },
  },
];

export default async function PembelajaranPage() {
  const page = await getPage("akademik-pembelajaran").catch(() => null);
  const blocks = page?.blocks?.length ? page.blocks : fallbackBlocks;

  return (
    <>
      <PageHeader
        eyebrow="Akademik"
        title="Proses & Kegiatan Pembelajaran"
        subtitle="Pembelajaran aktif yang menyeimbangkan capaian akademik, karakter, dan pengembangan diri."
        breadcrumb={[{ label: "Akademik", href: "/akademik" }, { label: "Proses Pembelajaran" }]}
      />
      <PageBody>
        <SectionNav items={akademikMenu} current="/akademik/pembelajaran" className="mb-8" />
        <BlockRenderer blocks={blocks} />
      </PageBody>
    </>
  );
}
