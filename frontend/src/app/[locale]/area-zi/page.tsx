import type { Metadata } from "next";
import { getPage } from "@/lib/api";
import { PageBody, PageHeader } from "@/components/ui";
import { SectionNav } from "@/components/section-nav";
import { BlockRenderer } from "@/components/blocks/block-renderer";
import { ziMenu } from "@/lib/section-menus";
import type { Block } from "@/lib/types";

export const metadata: Metadata = {
  title: "Area Zona Integritas",
  description:
    "Pembangunan Zona Integritas menuju Wilayah Bebas dari Korupsi (WBK) dan Wilayah Birokrasi Bersih dan Melayani (WBBM) di MTsN 1 Kota Malang.",
};
export const revalidate = 600;

const fallbackBlocks: Block[] = [
  {
    type: "hub_grid",
    data: {
      numbered: true,
      items: [
        { title: "Menuju WBK / WBBM", desc: "Enam area perubahan & maklumat integritas.", href: "/area-zi/wbk", icon: "M12 3l8 4v5c0 5-3.4 8.5-8 9-4.6-.5-8-4-8-9V7l8-4zM9 12l2 2 4-4" },
        { title: "Pengendalian Gratifikasi", desc: "Pelaporan penerimaan gratifikasi kepada UPG.", href: "/area-zi/gratifikasi", icon: "M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" },
        { title: "Whistleblowing (WBS)", desc: "Kanal pelaporan dugaan pelanggaran secara rahasia.", href: "/area-zi/wbs", icon: "M3 11l19-9-9 19-2-8-8-2z" },
        { title: "LHKPN & LHKASN", desc: "Kepatuhan pelaporan harta kekayaan pejabat & ASN.", href: "/area-zi/lhkpn", icon: "M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zM9 13h6M9 17h6" },
      ],
    },
  },
];

export default async function AreaZIPage() {
  const page = await getPage("area-zi-index").catch(() => null);
  const blocks = page?.blocks?.length ? page.blocks : fallbackBlocks;

  return (
    <>
      <PageHeader
        eyebrow="Reformasi Birokrasi"
        title="Area Zona Integritas"
        subtitle="MTsN 1 Kota Malang berkomitmen membangun Zona Integritas menuju Wilayah Bebas dari Korupsi (WBK) dan Wilayah Birokrasi Bersih dan Melayani (WBBM)."
        breadcrumb={[{ label: "Layanan", href: "/layanan" }, { label: "Zona Integritas" }]}
      />
      <PageBody>
        <SectionNav items={ziMenu} current="/area-zi" className="mb-8" />
        <BlockRenderer blocks={blocks} />
      </PageBody>
    </>
  );
}
