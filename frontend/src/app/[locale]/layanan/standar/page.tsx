import Link from "next/link";
import type { Metadata } from "next";
import { getPage } from "@/lib/api";
import { PageBody, PageHeader } from "@/components/ui";
import { SectionNav } from "@/components/section-nav";
import { BlockRenderer } from "@/components/blocks/block-renderer";
import { layananMenu } from "@/lib/section-menus";
import type { Block } from "@/lib/types";

export const metadata: Metadata = { title: "Standar Layanan" };
export const revalidate = 600;

const fallbackBlocks: Block[] = [
  {
    type: "table",
    data: {
      heading: null,
      columns: [{ label: "Jenis Layanan" }, { label: "Persyaratan" }, { label: "Waktu" }, { label: "Biaya" }],
      rows: [
        { cells: [{ value: "Legalisir & Surat Keterangan" }, { value: "KTP/KK pemohon, surat permohonan" }, { value: "1–2 hari kerja" }, { value: "Gratis" }] },
        { cells: [{ value: "Surat Keterangan Aktif Siswa" }, { value: "Kartu pelajar, permohonan wali" }, { value: "1 hari kerja" }, { value: "Gratis" }] },
        { cells: [{ value: "Mutasi Masuk / Keluar" }, { value: "Rapor, surat pindah, KK" }, { value: "3–5 hari kerja" }, { value: "Gratis" }] },
        { cells: [{ value: "Peminjaman Sarana" }, { value: "Surat permohonan lembaga" }, { value: "2 hari kerja" }, { value: "Sesuai ketentuan" }] },
        { cells: [{ value: "Permohonan Informasi Publik" }, { value: "Formulir permohonan (PPID)" }, { value: "10 hari kerja" }, { value: "Gratis" }] },
      ],
    },
  },
];

export default async function StandarLayananPage() {
  const page = await getPage("layanan-standar").catch(() => null);
  const blocks = page?.blocks?.length ? page.blocks : fallbackBlocks;

  return (
    <>
      <PageHeader
        eyebrow="Layanan"
        title="Standar Layanan"
        subtitle="Jenis layanan, persyaratan, jangka waktu penyelesaian, dan biaya."
        breadcrumb={[{ label: "Layanan", href: "/layanan" }, { label: "Standar Layanan" }]}
      />
      <PageBody>
        <SectionNav items={layananMenu} current="/layanan/standar" className="mb-8" />
        <BlockRenderer blocks={blocks} />
        <Link href="/dokumen" className="mt-4 inline-flex items-center gap-1.5 text-sm font-bold text-brand">
          Unduh dokumen standar pelayanan <span className="arrow-shift">→</span>
        </Link>
      </PageBody>
    </>
  );
}
