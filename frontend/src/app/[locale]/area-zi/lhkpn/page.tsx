import type { Metadata } from "next";
import { getPage } from "@/lib/api";
import { PageBody, PageHeader } from "@/components/ui";
import { SectionNav } from "@/components/section-nav";
import { BlockRenderer } from "@/components/blocks/block-renderer";
import { ziMenu } from "@/lib/section-menus";
import type { Block } from "@/lib/types";

export const metadata: Metadata = { title: "LHKPN & LHKASN" };
export const revalidate = 600;

const fallbackBlocks: Block[] = [
  {
    type: "card_grid",
    data: {
      heading: null,
      columns: 2,
      cards: [
        { title: "LHKPN", description: "Laporan Harta Kekayaan Penyelenggara Negara — bagi Kepala Madrasah dan pejabat wajib lapor, disampaikan ke KPK.", icon: null, image: null, href: "https://elhkpn.kpk.go.id/" },
        { title: "LHKASN", description: "Laporan Harta Kekayaan Aparatur Sipil Negara — bagi ASN selain wajib LHKPN, disampaikan melalui Inspektorat Jenderal Kemenag.", icon: null, image: null, href: "https://siharka.menpan.go.id/" },
      ],
    },
  },
];

export default async function LhkpnPage() {
  const page = await getPage("area-zi-lhkpn").catch(() => null);
  const blocks = page?.blocks?.length ? page.blocks : fallbackBlocks;

  return (
    <>
      <PageHeader
        eyebrow="Zona Integritas"
        title="LHKPN & LHKASN"
        subtitle="Kepatuhan pelaporan harta kekayaan sebagai wujud transparansi dan akuntabilitas pegawai madrasah."
        breadcrumb={[
          { label: "Layanan", href: "/layanan" },
          { label: "Zona Integritas", href: "/area-zi" },
          { label: "LHKPN & LHKASN" },
        ]}
      />
      <PageBody>
        <SectionNav items={ziMenu} current="/area-zi/lhkpn" className="mb-8" />
        <BlockRenderer blocks={blocks} />
      </PageBody>
    </>
  );
}
