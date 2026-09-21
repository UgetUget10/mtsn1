import type { Metadata } from "next";
import { getPage } from "@/lib/api";
import { Button, PageBody, PageHeader } from "@/components/ui";
import { SectionNav } from "@/components/section-nav";
import { BlockRenderer } from "@/components/blocks/block-renderer";
import { ziMenu } from "@/lib/section-menus";
import type { Block } from "@/lib/types";

export const metadata: Metadata = { title: "Pengendalian Gratifikasi" };
export const revalidate = 600;

const fallbackBlocks: Block[] = [
  {
    type: "rich_text",
    data: {
      heading: null,
      body: "<p>Unit Pengendalian Gratifikasi (UPG) madrasah menerima dan menindaklanjuti laporan gratifikasi dari pegawai. Pelaporan dapat disampaikan paling lambat 7 (tujuh) hari kerja sejak penerimaan, melalui UPG madrasah atau langsung ke aplikasi GOL milik KPK.</p>",
    },
  },
  {
    type: "icon_list",
    data: {
      heading: null,
      items: [
        { text: "Tolak gratifikasi yang berhubungan dengan jabatan sejak awal." },
        { text: "Bila tidak dapat ditolak, laporkan ke UPG madrasah." },
        { text: "UPG meneruskan laporan ke KPK untuk penetapan status kepemilikan." },
      ],
    },
  },
];

export default async function GratifikasiPage() {
  const page = await getPage("area-zi-gratifikasi").catch(() => null);
  const blocks = page?.blocks?.length ? page.blocks : fallbackBlocks;

  return (
    <>
      <PageHeader
        eyebrow="Zona Integritas"
        title="Pengendalian Gratifikasi"
        subtitle="Setiap penerimaan gratifikasi yang berhubungan dengan jabatan dan berlawanan dengan kewajiban wajib dilaporkan."
        breadcrumb={[
          { label: "Layanan", href: "/layanan" },
          { label: "Zona Integritas", href: "/area-zi" },
          { label: "Pengendalian Gratifikasi" },
        ]}
      />
      <PageBody>
        <SectionNav items={ziMenu} current="/area-zi/gratifikasi" className="mb-8" />
        <div className="card p-6 sm:p-8">
          <BlockRenderer blocks={blocks} />
          <div className="mt-6 flex flex-wrap gap-3">
            <Button href="/kontak">Lapor ke UPG Madrasah</Button>
            <Button href="https://www.kpk.go.id/gratifikasi" variant="outline" arrow={false}>
              Aplikasi GOL — KPK
            </Button>
          </div>
        </div>
      </PageBody>
    </>
  );
}
