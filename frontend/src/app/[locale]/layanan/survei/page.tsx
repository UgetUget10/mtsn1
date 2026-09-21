import Link from "next/link";
import type { Metadata } from "next";
import { getSettings } from "@/lib/api";
import { PageBody, PageHeader } from "@/components/ui";
import { SectionNav } from "@/components/section-nav";
import { layananMenu } from "@/lib/section-menus";

export const metadata: Metadata = { title: "Survei Kepuasan Masyarakat" };
export const revalidate = 600;

const items = [
  ["Survei Persepsi Kualitas Pelayanan (SPKP)", "Penilaian kepuasan atas kualitas layanan yang Anda terima."],
  ["Survei Persepsi Anti Korupsi (SPAK)", "Persepsi Anda tentang integritas dan bebas gratifikasi."],
];

export default async function SurveiPage() {
  const settings = await getSettings().catch(() => ({}) as Record<string, string>);
  const survey = /^https?:\/\//.test(settings.survey_url ?? "") ? settings.survey_url! : "/kontak";
  const ext = survey.startsWith("http");

  return (
    <>
      <PageHeader
        eyebrow="Layanan"
        title="Survei Kepuasan Masyarakat"
        subtitle="Masukan Anda membantu kami meningkatkan mutu dan integritas pelayanan."
        breadcrumb={[{ label: "Layanan", href: "/layanan" }, { label: "Survei Kepuasan" }]}
      />
      <PageBody>
        <SectionNav items={layananMenu} current="/layanan/survei" className="mb-8" />
        <div className="grid gap-4 sm:grid-cols-2">
          {items.map(([t, d]) => (
            <div key={t} className="card flex flex-col p-6">
              <h2 className="text-h3">{t}</h2>
              <p className="mt-1.5 flex-1 text-sm text-ink-muted">{d}</p>
              <Link
                href={survey}
                target={ext ? "_blank" : undefined}
                rel={ext ? "noreferrer" : undefined}
                className="mt-4 inline-flex w-fit items-center gap-1.5 rounded-lg bg-brand px-4 py-2 text-sm font-semibold text-on-brand transition hover:bg-brand-dark"
              >
                Isi Survei <span className="arrow-shift">→</span>
              </Link>
            </div>
          ))}
        </div>
      </PageBody>
    </>
  );
}
