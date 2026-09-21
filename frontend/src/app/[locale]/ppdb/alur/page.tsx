import type { Metadata } from "next";
import { getPage, getSettings } from "@/lib/api";
import { Button, PageBody, PageHeader } from "@/components/ui";
import { SectionNav } from "@/components/section-nav";
import { BlockRenderer } from "@/components/blocks/block-renderer";
import { ppdbMenu } from "@/lib/section-menus";
import type { Block } from "@/lib/types";

export const metadata: Metadata = { title: "Alur Pendaftaran PMBM" };
export const revalidate = 600;

const fallbackBlocks: Block[] = [
  {
    type: "steps",
    data: {
      heading: null,
      items: [
        { title: "Buat akun & isi formulir", description: "Daftar pada portal PMBM dan lengkapi data diri calon murid." },
        { title: "Unggah berkas", description: "Siapkan hasil pindai dokumen sesuai ketentuan." },
        { title: "Verifikasi", description: "Panitia memverifikasi berkas dan data pendaftaran." },
        { title: "Pengumuman", description: "Hasil seleksi diumumkan melalui portal dan kanal resmi madrasah." },
      ],
    },
  },
];

export default async function AlurPage() {
  const settings = await getSettings().catch(() => ({}) as Record<string, string>);
  const page = await getPage("ppdb-alur").catch(() => null);
  const blocks = page?.blocks?.length ? page.blocks : fallbackBlocks;

  return (
    <>
      <PageHeader
        eyebrow="PMBM"
        title="Alur Pendaftaran"
        subtitle="Empat tahap pendaftaran murid baru MTsN 1 Kota Malang."
        breadcrumb={[{ label: "PMBM", href: "/ppdb" }, { label: "Alur Pendaftaran" }]}
      />
      <PageBody>
        <SectionNav items={ppdbMenu} current="/ppdb/alur" className="mb-8" />
        <BlockRenderer blocks={blocks} />
        <div className="mt-8 border-t border-border pt-6">
          {settings.ppdb_url ? (
            <Button href={settings.ppdb_url} variant="accent" size="lg">
              Daftar Sekarang
            </Button>
          ) : (
            <p className="text-sm text-ink-muted">
              Tautan pendaftaran akan diumumkan menjelang periode PMBM.
            </p>
          )}
        </div>
      </PageBody>
    </>
  );
}
