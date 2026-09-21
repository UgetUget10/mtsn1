import type { Metadata } from "next";
import { getPage } from "@/lib/api";
import { PageBody, PageHeader } from "@/components/ui";
import { SectionNav } from "@/components/section-nav";
import { BlockRenderer } from "@/components/blocks/block-renderer";
import { ziMenu } from "@/lib/section-menus";
import type { Block } from "@/lib/types";

export const metadata: Metadata = { title: "Menuju WBK & WBBM" };
export const revalidate = 600;

const fallbackBlocks: Block[] = [
  {
    type: "rich_text",
    data: {
      heading: null,
      body: "<p>Seluruh pendidik dan tenaga kependidikan menandatangani pakta integritas serta menerapkan enam area perubahan sebagai upaya nyata mencegah korupsi dan meningkatkan mutu layanan.</p>",
    },
  },
  {
    type: "card_grid",
    data: {
      heading: null,
      columns: 3,
      cards: [
        { title: "Manajemen Perubahan", description: "Membangun komitmen dan budaya kerja berintegritas di seluruh warga madrasah.", icon: "M12 3v6m0 6v6M3 12h6m6 0h6M5.6 5.6l4.2 4.2m4.4 4.4 4.2 4.2M18.4 5.6l-4.2 4.2m-4.4 4.4-4.2 4.2", image: null, href: null },
        { title: "Penataan Tatalaksana", description: "Menyederhanakan prosedur dan mendorong pemanfaatan sistem elektronik.", icon: "M4 6h16M4 12h16M4 18h10", image: null, href: null },
        { title: "Penataan Sistem Manajemen SDM", description: "Pengelolaan pegawai yang objektif, transparan, dan berbasis kinerja.", icon: "M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z", image: null, href: null },
        { title: "Penguatan Akuntabilitas", description: "Perencanaan dan pelaporan kinerja yang terukur serta dapat dipertanggungjawabkan.", icon: "M3 3v18h18M7 15l4-4 4 4 5-6", image: null, href: null },
        { title: "Penguatan Pengawasan", description: "Pengendalian gratifikasi, benturan kepentingan, dan whistleblowing system.", icon: "M12 3l8 4v5c0 5-3.4 8.5-8 9-4.6-.5-8-4-8-9V7l8-4zM9.5 12l2 2 3.5-3.5", image: null, href: null },
        { title: "Peningkatan Kualitas Pelayanan Publik", description: "Standar pelayanan yang jelas, ramah, dan responsif terhadap kebutuhan masyarakat.", icon: "M20 21a8 8 0 0 0-16 0M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z", image: null, href: null },
      ],
    },
  },
  {
    type: "quote",
    data: {
      text: "Kami segenap keluarga besar MTsN 1 Kota Malang menyatakan siap membangun Zona Integritas, menolak segala bentuk korupsi, kolusi, nepotisme, dan gratifikasi, serta memberikan pelayanan terbaik tanpa diskriminasi kepada seluruh masyarakat.",
      attribution: null,
    },
  },
];

export default async function WbkPage() {
  const page = await getPage("area-zi-wbk").catch(() => null);
  const blocks = page?.blocks?.length ? page.blocks : fallbackBlocks;

  return (
    <>
      <PageHeader
        eyebrow="Zona Integritas"
        title="Menuju WBK & WBBM"
        subtitle="Pembangunan Zona Integritas merupakan miniatur pelaksanaan reformasi birokrasi melalui enam area perubahan."
        breadcrumb={[
          { label: "Layanan", href: "/layanan" },
          { label: "Zona Integritas", href: "/area-zi" },
          { label: "Menuju WBK / WBBM" },
        ]}
      />
      <PageBody>
        <SectionNav items={ziMenu} current="/area-zi/wbk" className="mb-8" />
        <BlockRenderer blocks={blocks} />
      </PageBody>
    </>
  );
}
