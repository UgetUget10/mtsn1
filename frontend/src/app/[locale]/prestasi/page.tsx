import type { Metadata } from "next";
import { getAchievements } from "@/lib/api";
import { Container, EmptyState, PageHeader } from "@/components/ui";
import { PrestasiExplorer } from "@/components/features-data";

export const metadata: Metadata = { title: "Prestasi" };
export const revalidate = 600;

export default async function PrestasiPage() {
  const achievements = await getAchievements();

  return (
    <>
      <PageHeader
        eyebrow="Capaian"
        title="Ringkasan Prestasi"
        subtitle="Capaian membanggakan siswa dan madrasah di berbagai tingkat."
        breadcrumb={[{ label: "Prestasi" }]}
      />
      <Container className="section-y">
        {achievements.data.length === 0 ? (
          <EmptyState>Data belum tersedia.</EmptyState>
        ) : (
          <PrestasiExplorer items={achievements.data} />
        )}
      </Container>
    </>
  );
}
