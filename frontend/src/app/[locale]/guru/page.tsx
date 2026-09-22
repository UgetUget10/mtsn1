import type { Metadata } from "next";
import { getTeachers } from "@/lib/api";
import { Container, EmptyState, PageHeader } from "@/components/ui";
import { GuruDirectory } from "@/components/features-data";

export const metadata: Metadata = { title: "Guru & Tenaga Kependidikan" };
export const dynamic = "force-dynamic";

const groupLabels: Record<string, string> = {
  pimpinan: "Pimpinan",
  guru: "Guru",
  tendik: "Tenaga Kependidikan",
};

export default async function GuruPage() {
  const teachers = await getTeachers();

  return (
    <>
      <PageHeader
        eyebrow="Tim Kami"
        title="Guru & Tenaga Kependidikan"
        subtitle="Tenaga pendidik dan kependidikan yang mendampingi peserta didik."
        breadcrumb={[{ label: "Guru & Tendik" }]}
      />
      <Container className="section-y">
        {teachers.length === 0 ? (
          <EmptyState>Data belum tersedia.</EmptyState>
        ) : (
          <GuruDirectory teachers={teachers} groupLabels={groupLabels} />
        )}
      </Container>
    </>
  );
}
