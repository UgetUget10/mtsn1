import type { Metadata } from "next";
import { getPage } from "@/lib/api";
import { Button, PageBody, PageHeader } from "@/components/ui";
import { SectionNav } from "@/components/section-nav";
import { BlockRenderer } from "@/components/blocks/block-renderer";
import { ziMenu } from "@/lib/section-menus";
import type { Block } from "@/lib/types";

export const metadata: Metadata = { title: "Whistleblowing System (WBS)" };
export const revalidate = 600;

const fallbackBlocks: Block[] = [
  {
    type: "icon_list",
    data: {
      heading: "Yang dapat dilaporkan",
      items: [
        { text: "Korupsi, kolusi, dan nepotisme" },
        { text: "Penyalahgunaan wewenang / jabatan" },
        { text: "Benturan kepentingan" },
        { text: "Pelanggaran kode etik & disiplin pegawai" },
        { text: "Pungutan liar dan gratifikasi" },
      ],
    },
  },
];

export default async function WbsPage() {
  const page = await getPage("area-zi-wbs").catch(() => null);
  const blocks = page?.blocks?.length ? page.blocks : fallbackBlocks;

  return (
    <>
      <PageHeader
        eyebrow="Zona Integritas"
        title="Whistleblowing System (WBS)"
        subtitle="Kanal pelaporan dugaan pelanggaran oleh pegawai madrasah. Identitas pelapor dijamin kerahasiaannya."
        breadcrumb={[
          { label: "Layanan", href: "/layanan" },
          { label: "Zona Integritas", href: "/area-zi" },
          { label: "Whistleblowing (WBS)" },
        ]}
      />
      <PageBody>
        <SectionNav items={ziMenu} current="/area-zi/wbs" className="mb-8" />
        <div className="grid gap-6 lg:grid-cols-2">
          <div className="card p-6">
            <BlockRenderer blocks={blocks} />
          </div>
          <div className="card p-6">
            <h2 className="text-h3">Cara melapor</h2>
            <p className="mt-2 text-sm text-ink-soft">
              Sertakan uraian peristiwa (apa, siapa, kapan, di mana, bagaimana) dan bukti pendukung
              bila ada. Laporan dapat disampaikan melalui formulir kontak madrasah atau kanal
              nasional SP4N-LAPOR.
            </p>
            <div className="mt-5 flex flex-wrap gap-3">
              <Button href="/kontak">Sampaikan Laporan</Button>
              <Button href="https://www.lapor.go.id/" variant="outline" arrow={false}>
                SP4N-LAPOR!
              </Button>
            </div>
          </div>
        </div>
      </PageBody>
    </>
  );
}
