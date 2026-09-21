import Link from "next/link";
import type { Metadata } from "next";
import { getSettings } from "@/lib/api";
import { Container, PageHeader } from "@/components/ui";
import { Reveal } from "@/components/motion";

export const metadata: Metadata = {
  title: "Ma'had (Asrama)",
  description:
    "Program ma'had (asrama) putra dan putri MTsN 1 Kota Malang: pembinaan karakter, tahfidz, penguatan ibadah, dan kemandirian.",
};
export const revalidate = 600;

const keunggulan = [
  {
    t: "Program Tahfidz",
    d: "Bimbingan hafalan Al-Qur'an bertingkat dengan target terukur dan muraja'ah rutin.",
    icon: "M4 5a2 2 0 0 1 2-2h9v18H6a2 2 0 0 1-2-2zM15 3h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-3",
  },
  {
    t: "Penguatan Ibadah",
    d: "Pembiasaan salat berjamaah, qiyamul lail, zikir, dan kajian keislaman harian.",
    icon: "M12 3l8 4v5c0 5-3.4 8.5-8 9-4.6-.5-8-4-8-9V7l8-4z",
  },
  {
    t: "Bahasa Arab & Inggris",
    d: "Lingkungan berbahasa (bi'ah lughawiyah) melalui kosakata harian dan muhadatsah.",
    icon: "M3 5h12M9 3v2M12 20l4.5-11L21 20M14 16h5M5 8s1.5 4 5 4 5-4 5-4",
  },
  {
    t: "Kemandirian & Adab",
    d: "Pembinaan disiplin, tanggung jawab, kebersihan, dan adab pergaulan islami.",
    icon: "M20 7 9 18l-5-5",
  },
];

const jadwal = [
  ["03.30", "Qiyamul lail & persiapan Subuh"],
  ["04.30", "Salat Subuh berjamaah & tahfidz"],
  ["06.00", "Persiapan & berangkat ke madrasah"],
  ["15.30", "Kegiatan ma'had & muraja'ah"],
  ["18.00", "Salat Magrib, kajian, & Isya berjamaah"],
  ["20.00", "Belajar terbimbing & istirahat"],
];

export default async function MahadPage() {
  const settings = await getSettings().catch(() => ({}) as Record<string, string>);
  const ppdb = settings.ppdb_url ?? "/ppdb";

  return (
    <>
      <PageHeader
        eyebrow="Pembinaan Karakter"
        title="Program Ma'had (Asrama)"
        subtitle="Ma'had putra dan putri menjadi wadah pembinaan karakter islami, tahfidz, dan kemandirian di luar jam belajar madrasah."
        breadcrumb={[{ label: "Ma'had" }]}
      />

      {/*
        Latar bermotif (`grain glow-spots`) dipindah dari <Container> ke
        pembungkus selebar layar. Container dibatasi max-w-7xl, jadi ketika
        latar menempel padanya, motif ikut terpotong di kanan-kiri dan tidak
        mencapai tepi viewport. Sekarang latar membentang penuh, sementara
        Container tetap mengurus lebar KONTEN saja.
      */}
      <div className="grain glow-spots relative">
      <Container className="relative section-y">
        <section>
          <p className="kicker">Selayang Pandang</p>
          <h2 className="text-h2 heading-rule mt-2">Belajar, Beribadah, Bertumbuh Bersama</h2>
          <p className="mt-4 max-w-3xl text-lead">
            Program ma&rsquo;had melengkapi pendidikan akademik dengan pembinaan menyeluruh: hafalan
            Al-Qur&rsquo;an, penguatan ibadah, penguasaan bahasa, serta kemandirian. Santri didampingi
            musyrif/musyrifah dalam suasana kekeluargaan yang religius dan disiplin.
          </p>
        </section>

        <section className="mt-12">
          <p className="kicker">Keunggulan</p>
          <h2 className="text-h2 heading-rule mt-2">Fokus Pembinaan</h2>
          <div className="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {keunggulan.map((k, i) => (
              <Reveal key={k.t} delay={(i % 4) * 60}>
                <div className="group card card-hover h-full p-6">
                  <span className="grid h-12 w-12 place-items-center rounded-xl bg-brand-light text-brand-dark transition duration-300 group-hover:bg-brand group-hover:text-on-brand">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round">
                      <path d={k.icon} />
                    </svg>
                  </span>
                  <h3 className="mt-4 font-bold text-foreground">{k.t}</h3>
                  <p className="mt-1.5 text-sm leading-relaxed text-ink-muted">{k.d}</p>
                </div>
              </Reveal>
            ))}
          </div>
        </section>

        <section className="mt-12 grid gap-10 lg:grid-cols-[1fr_1fr] lg:items-start">
          <div>
            <p className="kicker">Rutinitas</p>
            <h2 className="text-h2 heading-rule mt-2">Jadwal Harian (Ringkas)</h2>
            <div className="mt-6 card divide-y divide-border overflow-hidden">
              {jadwal.map(([jam, keg]) => (
                <div key={jam} className="flex gap-4 p-4 transition hover:bg-surface-muted">
                  <span className="w-14 shrink-0 text-sm font-extrabold tabular-nums text-brand">{jam}</span>
                  <span className="text-sm text-ink-soft">{keg}</span>
                </div>
              ))}
            </div>
            <p className="mt-3 text-xs text-ink-muted">
              Jadwal menyesuaikan kalender madrasah dan bulan Ramadan.
            </p>
          </div>

          <div>
            <p className="kicker">Fasilitas</p>
            <h2 className="text-h2 heading-rule mt-2">Sarana Ma&rsquo;had</h2>
            <ul className="mt-6 grid gap-3 sm:grid-cols-2">
              {[
                "Gedung Ma'had Putra",
                "Gedung Ma'had Putri",
                "Musala / ruang tahfidz",
                "Kamar santri & kamar mandi",
                "Ruang makan bersama",
                "Ruang belajar terbimbing",
              ].map((f) => (
                <li key={f} className="flex items-center gap-3 rounded-xl border border-border bg-surface p-4 text-sm font-medium text-ink-soft">
                  <span className="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-brand-light text-brand-dark">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round"><path d="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6" /></svg>
                  </span>
                  {f}
                </li>
              ))}
            </ul>
          </div>
        </section>

        <section className="band-brand on-dark grain relative mt-14 overflow-hidden rounded-2xl p-6 sm:p-9">
          <span aria-hidden className="stars" />
          <h2 className="text-h2 relative">Ingin bergabung dengan ma&rsquo;had?</h2>
          <p className="relative mt-2 max-w-xl text-white/85">
            Pendaftaran ma&rsquo;had menjadi satu kesatuan dengan Penerimaan Murid Baru Madrasah. Ikuti
            jadwal dan ketentuan pada halaman PMBM.
          </p>
          <div className="relative mt-6 flex flex-wrap gap-3">
            <a
              href={ppdb}
              target={ppdb.startsWith("http") ? "_blank" : undefined}
              rel={ppdb.startsWith("http") ? "noreferrer" : undefined}
              className="btn-glow inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-semibold text-brand-dark shadow-md transition hover:bg-white/90"
            >
              Informasi PMBM <span className="arrow-shift">→</span>
            </a>
            <Link
              href="/kontak"
              className="inline-flex items-center gap-2 rounded-xl border border-white/25 bg-white/10 px-5 py-3 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20"
            >
              Narahubung
            </Link>
          </div>
        </section>
      </Container>
      </div>
    </>
  );
}
