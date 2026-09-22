import Image from "@/components/media-image";
import Link from "next/link";
import {
  getAchievements,
  getAgendas,
  getExtracurriculars,
  getGalleries,
  getPage,
  getPosts,
  getSettings,
  getSliders,
  getTestimonials,
} from "@/lib/api";
import { Badge, Button, Container, CoverFallback, EmptyState, Icon, IconTile } from "@/components/ui";
import { MotifStar } from "@/components/ornament";
import { Accordion, Marquee, Reveal, SpotlightCard } from "@/components/motion";
import {
  AddToCalendar,
  CountdownPPDB,
  GalleryLightbox,
  Magnetic,
  PointerGlow,
  WaveDivider,
} from "@/components/features";
import { NewsSlider, Testimonials } from "@/components/features-more";
import { StatBars } from "@/components/features-data";
import { SiteSearch } from "@/components/site-search";
import { formatDate, formatDateTime } from "@/lib/format";
import { orderedVisibleSectionIds, parseHomepageSections } from "@/lib/homepage-sections";

export const revalidate = 300;


const portalApps = [
  { label: "Layanan Publik", href: "/layanan", icon: "M4 5a2 2 0 0 1 2-2h9v18H6a2 2 0 0 1-2-2zM15 3h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-3" },
  { label: "Akademik", href: "/akademik", icon: "M12 3 2 8l10 5 10-5-10-5zM4 10v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6" },
  { label: "Pengaduan", href: "/layanan#pengaduan", icon: "M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" },
  { label: "PTSP Online", href: "/layanan#sop", icon: "M3 21h18M6 21V7l6-4 6 4v14M10 21v-5h4v5" },
  { label: "PMBM Online", href: "/ppdb", icon: "M12 2a5 5 0 0 1 5 5v3H7V7a5 5 0 0 1 5-5zM5 10h14v10a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2z" },
  { label: "Ma'had", href: "/mahad", icon: "M3 21h18M5 21V7l7-4 7 4v14M9 9h.01M15 9h.01M9 13h.01M15 13h.01M10 21v-4h4v4" },
];

// Hanya sistem yang benar-benar aplikatif — tautan layanan/portal ada di "Akses Cepat".
const digitalApps = [
  { label: "Perpustakaan Digital", icon: "M4 5a2 2 0 0 1 2-2h9v18H6a2 2 0 0 1-2-2zM15 3h3a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-3" },
  { label: "Rapor Digital (RDM)", icon: "M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2zM9 13h6M9 17h6" },
  { label: "Absensi Digital", icon: "M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" },
  { label: "E-Learning", icon: "M2 3h20v14H2zM8 21h8M12 17v4" },
  { label: "SIPAMAD", icon: "M12 2l3 7h7l-5.5 4 2 7L12 17l-6.5 5 2-7L2 9h7z" },
];

const fasilitas = [
  "Ruang Kelas", "Laboratorium IPA", "Laboratorium Komputer", "Perpustakaan",
  "Masjid", "Ruang Aula", "Lapangan Olahraga", "Ruang UKS",
  "Gedung Ma'had Putra", "Gedung Ma'had Putri", "Ruang PTSP", "Kantin Siswa",
];

// "Sekolah Adiwiyata" & "Zona Integritas" dihapus dari sini — predikat itu
// belum berlaku untuk MTsN 1 Kota Malang saat ini (lihat juga bar predikat
// di CountdownPPDB, yang sumbernya sudah dipindah ke Setting `badges`).
const badgeProgram = [
  { title: "Berma'had", text: "Pembinaan karakter melalui program ma'had (asrama)." },
];

const keunggulan = [
  {
    icon: "M12 3l8 4v5c0 5-3.4 8.5-8 9-4.6-.5-8-4-8-9V7l8-4z",
    title: "Pendidikan Berkarakter",
    text: "Kurikulum yang memadukan penguatan akidah, akhlak, dan kompetensi akademik nasional.",
  },
  {
    icon: "M22 10v6M2 10l10-5 10 5-10 5zM6 12v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5",
    title: "Guru Kompeten",
    text: "Tenaga pendidik profesional bersertifikat dengan pendampingan belajar yang personal.",
  },
  {
    icon: "M3 21h18M6 21V7l6-4 6 4v14M10 21v-6h4v6",
    title: "Fasilitas Modern",
    text: "Laboratorium, perpustakaan digital, dan ruang belajar yang nyaman serta representatif.",
  },
  {
    icon: "M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0zM7 6H4v2a3 3 0 0 0 3 3M17 6h3v2a3 3 0 0 1-3 3",
    title: "Prestasi Konsisten",
    text: "Capaian akademik dan non-akademik yang membanggakan di tingkat kota hingga nasional.",
  },
];

const faqs = [
  { q: "Kapan pendaftaran PMBM dibuka?", a: "Jadwal PMBM mengikuti kalender resmi Kementerian Agama. Informasi tanggal, jalur, dan kuota diumumkan melalui halaman PMBM dan media sosial madrasah." },
  { q: "Apa saja berkas yang perlu disiapkan?", a: "Umumnya: kartu keluarga, akta kelahiran, rapor SD/MI, pas foto, dan dokumen prestasi (bila ada). Rincian lengkap tersedia saat pendaftaran dibuka." },
  { q: "Apakah tersedia jalur prestasi?", a: "Ya. Terdapat jalur prestasi akademik dan non-akademik. Persyaratan diatur pada petunjuk teknis PMBM tahun berjalan." },
  { q: "Bagaimana jika butuh bantuan pendaftaran?", a: "Hubungi kami melalui halaman Kontak, telepon, atau datang langsung ke madrasah pada jam kerja." },
];

export default async function HomePage() {
  const [settings, sliders, featured, latest, agendas, achievements, ekskul, galleries, testimonials, programPage] =
    await Promise.all([
      getSettings().catch(() => ({}) as Record<string, string>),
      getSliders().catch(() => []),
      getPosts({ featured: 1, per_page: 4 }),
      getPosts({ per_page: 5 }),
      getAgendas(),
      getAchievements(),
      getExtracurriculars(),
      getGalleries(),
      getTestimonials(),
      // Kartu "Program Unggulan" di beranda dibaca dari blok `card_grid`
      // halaman CMS /akademik/program-unggulan — BUKAN diketik ulang di sini.
      // Sebelumnya 4 kartu (Tahfidz/Olimpiade/Bilingual/CBI) hardcode di file
      // ini DAN duplikat persis di halaman itu; keduanya harus diedit
      // terpisah dan gampang saling menyimpang. Sekarang satu sumber saja.
      getPage("program-unggulan").catch(() => null),
    ]);

  const programUnggulan = (
    programPage?.blocks?.find((b) => b.type === "card_grid")?.data.cards ?? []
  ).map((c) => {
    // Backend menyimpan "Tag — Deskripsi" dalam satu field `description`
    // (lihat App\Filament pembuat blok card_grid). Dipisah di sini murni
    // untuk tata letak (chip tag terpisah dari teks) — bukan transformasi
    // data, sumbernya tetap satu string yang sama.
    const [tag, ...rest] = (c.description ?? "").split(" — ");
    return { tag: rest.length ? tag : "", title: c.title, text: rest.length ? rest.join(" — ") : tag };
  });

  // "badges" disimpan di backend sebagai JSON array (Setting key `badges`,
  // dikelola lewat Pengaturan Situs → Predikat & Sertifikasi). Kosong/invalid
  // → array kosong, bar predikat cukup menampilkan "Terakreditasi A" saja.
  let badges: string[] = [];
  try {
    const parsed = settings.badges ? JSON.parse(settings.badges) : [];
    badges = Array.isArray(parsed) ? parsed : [];
  } catch {
    badges = [];
  }

  const galleryPhotos = galleries.data
    .flatMap((g) =>
      g.items
        .filter((it) => it.url && (it.type === "image" || it.type === "video"))
        .map((it) => ({
          url: it.url as string,
          caption: it.caption ?? g.title,
          type: it.type,
        })),
    )
    .slice(0, 8);

  // Urutan default section beranda — HARUS sama dengan DEFAULT_SECTIONS di
  // backend App\Filament\Pages\HomepageBuilder. Dipakai saat admin belum
  // pernah menyimpan susunan, dan sebagai fallback untuk section baru.
  const DEFAULT_SECTION_ORDER = [
    "akses_cepat", "keunggulan", "berita", "statistik", "testimoni", "program",
    "identitas", "sambutan", "agenda_prestasi", "sarpras", "ekstrakurikuler",
    "aplikasi", "galeri", "kelembagaan", "cta_faq", "lokasi",
  ];
  const homepageSections = parseHomepageSections(settings);
  const orderedIds = orderedVisibleSectionIds(homepageSections, DEFAULT_SECTION_ORDER);

  const slide = sliders[0];
  const sliderPosts = Array.from(
    new Map(
      [...featured.data, ...latest.data].map((p) => [p.slug, p]),
    ).values(),
  ).slice(0, 5);
  const lead = featured.data[0] ?? latest.data[0];
  const rest = (featured.data.length > 1 ? featured.data.slice(1) : latest.data.slice(1)).slice(0, 3);
  const name = settings.site_name ?? "MTsN 1 Kota Malang";

  const numbers = [
    { value: settings.stat_students ?? "803", label: "Siswa" },
    { value: settings.stat_teachers ?? "56", label: "Guru" },
    { value: settings.stat_staff ?? "24", label: "Tenaga Kependidikan" },
    { value: settings.stat_alumni ?? "10.000+", label: "Alumni" },
  ];

  // Bar sebagai indikator "pertumbuhan/kapasitas" — angka pasti tetap ditonjolkan.
  const statRatios = [0.86, 0.8, 0.72, 1];
  const statBars = numbers.map((n, i) => ({
    label: n.label,
    display: n.value,
    ratio: statRatios[i] ?? 0.75,
  }));

  const tickerItems = [
    ...latest.data.slice(0, 5).map((p) => p.title),
    ...agendas.slice(0, 3).map((a) => `${a.title} — ${formatDate(a.start_at)}`),
  ].filter(Boolean);

  const identitas = [
    ["Nomenklatur", settings.school_name ?? `Madrasah Tsanawiyah Negeri 1 Kota Malang`],
    ["NPSN", settings.npsn ?? "—"],
    ["NSM", settings.nsm ?? "—"],
    ["Akreditasi", settings.stat_accreditation ?? "A"],
    ["Alamat", settings.address ?? "Kota Malang, Jawa Timur"],
  ] as const;

  return (
    <>
      {/* ===================== HERO ===================== */}
      <section className="mesh pattern-islamic relative overflow-hidden border-b border-border">
        <MotifStar className="-right-16 -top-24 md:-right-8" />
        <PointerGlow />
        <Container className="relative grid gap-12 section-y lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
          <div>
            <div className="flex flex-wrap gap-2">
              <span className="chip">
                <span className="h-1.5 w-1.5 rounded-full bg-brand" />
                Terakreditasi {settings.stat_accreditation ?? "A"}
              </span>
              <span className="chip">Kementerian Agama</span>
            </div>
            <h1 className="text-display reveal-wipe mt-6 text-balance">
              Modern,{" "}
              <span className="text-shimmer">Innovative</span> and Excellent School
            </h1>
            <p className="mt-5 max-w-xl text-lead">
              {slide?.subtitle ??
                `${name} menyeimbangkan iman, ilmu, dan amal — didukung guru berkualitas, program ma'had, serta lingkungan belajar yang hijau dan modern.`}
            </p>
            <div className="mt-8 flex flex-wrap gap-3">
              <Magnetic>
                <Button href={settings.ppdb_url ?? "/ppdb"} size="lg">
                  Informasi PMBM
                </Button>
              </Magnetic>
              <Magnetic>
                <Button href="/profil" size="lg" variant="outline" arrow={false}>
                  Tentang Madrasah
                </Button>
              </Magnetic>
            </div>
            <p className="tabular mt-8 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-ink-muted">
              {numbers.slice(0, 2).map((n) => (
                <span key={n.label}>
                  <span className="font-bold text-foreground">{n.value}</span> {n.label.toLowerCase()}
                  <span className="mx-2 text-border-strong">·</span>
                </span>
              ))}
              <span className="font-bold text-foreground">
                Akreditasi {settings.stat_accreditation ?? "A"}
              </span>
            </p>
          </div>

          <div className="relative ring-glow rounded-2xl">
            <div
              aria-hidden
              className="parallax-slower absolute -inset-3 -z-10 rounded-[1.6rem] opacity-60 blur-2xl [background-image:var(--gradient-brand)]"
            />
            <NewsSlider posts={sliderPosts} />
          </div>
        </Container>
      </section>

      {/* ===================== TICKER PENGUMUMAN ===================== */}
      {tickerItems.length > 0 && (
        <div className="border-b border-border bg-brand-darker py-2.5">
          <div className="mx-auto flex max-w-7xl items-center gap-3 px-4 sm:px-6 lg:px-8">
            <span className="hidden shrink-0 items-center gap-1.5 rounded-md bg-white/15 px-2.5 py-1 text-[0.68rem] font-bold uppercase tracking-wider text-white sm:inline-flex">
              <span className="h-1.5 w-1.5 animate-pulse rounded-full bg-accent" />
              Info Terkini
            </span>
            <div className="min-w-0 flex-1">
              <Marquee items={tickerItems} />
            </div>
          </div>
        </div>
      )}

      <CountdownPPDB
        deadline={settings.ppdb_deadline}
        href={settings.ppdb_url ?? "/ppdb"}
        // Predikat/sertifikasi diambil dari Setting "badges" (JSON array,
        // dikelola admin lewat Pengaturan Situs → Predikat & Sertifikasi).
        // Sebelumnya hardcode di sini — termasuk "Sekolah Adiwiyata" dan
        // "Zona Integritas / WBK" yang TIDAK berlaku untuk MTsN 1 Kota
        // Malang, jadi tayang sebagai klaim keliru ke publik.
        badges={[`Terakreditasi ${settings.stat_accreditation ?? "A"}`, ...badges]}
      />

      {/* Kolom pencarian selalu tampil di atas susunan section yang bisa diatur. */}
      <section className="border-b border-border bg-surface-muted">
        <Container className="py-12">
          <div className="mx-auto max-w-xl">
            <SiteSearch variant="block" />
          </div>
        </Container>
      </section>

      {/*
        Section beranda yang bisa diatur urutannya lewat admin
        (App\Filament\Pages\HomepageBuilder). Tiap entri di sectionNodes dikunci
        `id` yang sama dengan backend, lalu dirender sesuai `orderedIds`.
      */}
      {(() => {
        const sectionNodes: Record<string, React.ReactNode> = {

      "akses_cepat": (
      <section key="akses_cepat" className="border-b border-border bg-surface-muted section-y">
        <Container>
          <div className="mb-5 flex items-center gap-3">
            <p className="kicker">Akses Cepat</p>
            <span className="h-px flex-1 bg-border" />
          </div>
          <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            {portalApps.map((p, i) => (
              <Reveal key={p.label} delay={Math.min(i, 4) * 40}>
                <Link
                  href={p.href}
                  className="group card card-hover flex flex-col items-center gap-3 p-5 text-center"
                >
                  <IconTile path={p.icon} size="lg" interactive />
                  <span className="text-sm font-semibold text-foreground">{p.label}</span>
                </Link>
              </Reveal>
            ))}
          </div>
        </Container>
      </section>
      ),

      "keunggulan": (
      <section key="keunggulan" data-section="Keunggulan" className="border-b border-border bg-surface-muted section-y">
        <Container>
          <div className="mx-auto max-w-2xl text-center">
            <p className="kicker">Mengapa {name} Menjadi Pilihan Terbaik?</p>
            <h2 className="text-h2 mt-2">4 Alasan Kami Menjadi Pilihan Utama Masa Depan Murid</h2>
            <p className="mt-3 text-lead">
              Menyeimbangkan Iman, Ilmu, dan Amal dalam Lingkungan Belajar Islami.
            </p>
          </div>
          <div className="mt-12 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {keunggulan.map((k, i) => (
              <Reveal key={k.title} delay={Math.min(i, 4) * 60}>
                <SpotlightCard className="h-full">
                  <div className="group card card-hover h-full p-6">
                    <IconTile path={k.icon} size="lg" interactive />
                    <h3 className="text-h3 mt-4">{k.title}</h3>
                    <p className="mt-2 text-sm leading-relaxed text-ink-muted">{k.text}</p>
                  </div>
                </SpotlightCard>
              </Reveal>
            ))}
          </div>
        </Container>
      </section>
      ),

      "berita": (
      <section key="berita" data-section="Berita" className="border-b border-border section-y">
        <Container>
          <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
              <p className="kicker">Informasi</p>
              <h2 className="text-h2 heading-rule mt-2">Berita &amp; Pengumuman</h2>
            </div>
            <Button href="/berita" variant="soft" size="sm">Semua berita</Button>
          </div>

          <div className="grid gap-6 lg:grid-cols-2">
            {lead && (
              <Reveal className="lg:row-span-3">
                <Link
                  href={`/berita/${lead.slug}`}
                  className="group card card-hover flex h-full flex-col overflow-hidden"
                >
                  <div className="relative aspect-16/10 overflow-hidden bg-surface-muted">
                    {lead.cover ? (
                      <Image src={lead.cover} alt={lead.cover_alt ?? lead.title} fill sizes="50vw" className="object-cover transition duration-700 group-hover:scale-105" />
                    ) : (
                      <CoverFallback />
                    )}
                  </div>
                  <div className="flex flex-1 flex-col p-6">
                    <div className="flex items-center gap-2 text-xs font-semibold text-ink-muted">
                      {lead.category && <Badge>{lead.category.name}</Badge>}
                      <span>{formatDate(lead.published_at)}</span>
                    </div>
                    <h3 className="mt-3 text-xl font-extrabold text-foreground transition group-hover:text-brand sm:text-2xl">
                      {lead.title}
                    </h3>
                    <p className="mt-2 line-clamp-3 flex-1 text-sm text-ink-muted">{lead.excerpt}</p>
                    <span className="mt-4 text-sm font-bold text-brand">Baca selengkapnya →</span>
                  </div>
                </Link>
              </Reveal>
            )}

            <div className="flex flex-col gap-4">
              {rest.map((post, i) => (
                <Reveal key={post.id} delay={Math.min(i, 4) * 60} direction="left">
                  <Link
                    href={`/berita/${post.slug}`}
                    className="group card card-hover flex gap-4 p-3"
                  >
                    <div className="relative aspect-square w-28 shrink-0 overflow-hidden rounded-lg bg-surface-muted">
                      {post.cover ? (
                        <Image src={post.cover} alt={post.cover_alt ?? post.title} fill sizes="120px" className="object-cover" />
                      ) : (
                        <CoverFallback label="M1" />
                      )}
                    </div>
                    <div className="min-w-0 flex-1 py-1">
                      <p className="text-[0.7rem] font-semibold uppercase tracking-wide text-brand">
                        {post.category?.name ?? "Berita"}
                      </p>
                      <p className="mt-1 line-clamp-2 font-bold text-foreground transition group-hover:text-brand">
                        {post.title}
                      </p>
                      <p className="mt-1 text-xs text-ink-muted">{formatDate(post.published_at)}</p>
                    </div>
                  </Link>
                </Reveal>
              ))}
            </div>
          </div>
        </Container>
      </section>
      ),

      "statistik": (
      <section key="statistik" data-section="Statistik" className="on-dark pattern-islamic faint relative overflow-hidden bg-brand-darker section-y-lg text-white">
        <WaveDivider />
        <div
          aria-hidden
          className="animate-drift pointer-events-none absolute -right-20 -top-24 h-80 w-80 rounded-full bg-white/5 blur-3xl"
        />
        <div
          aria-hidden
          className="animate-float pointer-events-none absolute -bottom-28 left-10 h-72 w-72 rounded-full bg-brand/25 blur-3xl"
        />
        <span aria-hidden className="watermark right-2 top-0 text-white sm:right-8">
          {settings.stat_accreditation ?? "A"}
        </span>
        <Container className="relative">
          <p className="kicker">
            Statistik Madrasah
          </p>
          <h2 className="mt-2 max-w-xl text-2xl font-extrabold tracking-tight sm:text-3xl">
            Angka yang tumbuh bersama kepercayaan masyarakat
          </h2>
          <div className="mt-10">
            <StatBars items={statBars} />
          </div>
        </Container>
      </section>
      ),

      // Section hanya dirender bila ADA testimoni aktif dari backend.
      // Sebelumnya 3 testimoni FIKTIF (nama karangan "Ibu Nurhayati", "Raka
      // Pratama", dst) hardcode di sini dan selalu tayang — tidak ada yang
      // bisa admin ubah lewat panel. Sekarang diambil dari
      // Filament → Profil Madrasah → Testimoni; section otomatis
      // tersembunyi selama belum ada testimoni yang diaktifkan.
      "testimoni": testimonials.length > 0 ? (
      <section key="testimoni" className="tint-brand border-b border-border section-y-lg">
        <Container>
          <p className="kicker mx-auto w-fit">Kata Mereka</p>
          <h2 className="text-h2 mt-2 text-center">Dipercaya keluarga besar madrasah</h2>
          <div className="mt-10">
            <Testimonials items={testimonials} />
          </div>
        </Container>
      </section>
      ) : null,

      // Section disembunyikan otomatis bila halaman CMS belum punya kartu —
      // daripada menampilkan grid kosong atau kembali ke data karangan.
      "program": programUnggulan.length > 0 ? (
      <section key="program" id="program" data-section="Program" className="border-b border-border scroll-mt-24 section-y">
        <Container>
          <p className="kicker">Akademik</p>
          <h2 className="heading-rule mt-2 font-extrabold tracking-tight text-[clamp(1.5rem,4vw,3.25rem)] leading-[1.1]">
            Kami Mengembangkan Minat dan Potensi Murid Melalui Program Pembelajaran yang Tepat
          </h2>
          <div className="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
            {programUnggulan.map((k, i) => (
              <Reveal key={k.title} delay={Math.min(i, 4) * 60}>
                <SpotlightCard className="h-full">
                  <div className="card card-hover flex h-full flex-col p-6">
                    {k.tag && <Badge>{k.tag}</Badge>}
                    <h3 className="text-h3 mt-4">{k.title}</h3>
                    <p className="mt-2 text-sm leading-relaxed text-ink-muted">{k.text}</p>
                  </div>
                </SpotlightCard>
              </Reveal>
            ))}
          </div>
        </Container>
      </section>
      ) : null,

      "identitas": (
      <section key="identitas" className="border-y border-border bg-surface-muted section-y">
        <Container className="grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
          <div>
            <p className="kicker">Profil</p>
            <h2 className="text-h2 heading-rule mt-2">Identitas Madrasah</h2>
            <p className="mt-3 text-lead">
              Data pokok kelembagaan {name} sesuai registrasi Kementerian Agama.
            </p>
          </div>
          <dl className="card divide-y divide-border overflow-hidden">
            {identitas.map(([k, v]) => (
              <div key={k} className="grid grid-cols-[9rem_1fr] gap-4 p-4 sm:grid-cols-[11rem_1fr] sm:p-5">
                <dt className="text-xs font-bold uppercase tracking-wide text-ink-muted">{k}</dt>
                <dd className="text-sm font-medium text-foreground">{v}</dd>
              </div>
            ))}
          </dl>
        </Container>
      </section>
      ),

      "sambutan": settings.principal_word ? (
        <section key="sambutan" id="sambutan" className="mesh scroll-mt-24 border-y border-border section-y">
          <Container>
            <p className="kicker">Sambutan Kepala Madrasah</p>
            <div className="relative mt-6">
              <span
                aria-hidden
                className="pointer-events-none absolute -inset-x-4 -inset-y-8 -z-20 bg-[radial-gradient(ellipse_at_20%_10%,var(--color-brand),transparent_45%),radial-gradient(ellipse_at_85%_90%,var(--color-accent),transparent_40%)] opacity-[0.06] blur-2xl sm:-inset-x-8 sm:-inset-y-12"
              />
              <span
                aria-hidden
                className="pointer-events-none absolute -left-5 -top-5 -z-10 hidden h-20 w-20 rounded-2xl border-4 border-brand/15 sm:block sm:h-28 sm:w-28"
              />
              <svg
                aria-hidden
                className="animate-pulse pointer-events-none absolute -left-1 -top-8 -z-10 hidden text-accent/50 sm:block"
                width="16"
                height="16"
                viewBox="0 0 16 16"
                fill="none"
              >
                <path d="M8 0v16M0 8h16" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" />
              </svg>
              <svg
                aria-hidden
                className="pointer-events-none absolute -left-1 top-14 -z-10 hidden text-brand/20 sm:block"
                width="48"
                height="48"
                viewBox="0 0 48 48"
                fill="none"
              >
                {[0, 1, 2].map((row) =>
                  [0, 1, 2].map((col) => (
                    <circle
                      key={`${row}-${col}`}
                      cx={6 + col * 14}
                      cy={6 + row * 14}
                      r={(row + col) % 3 === 0 ? 2.2 : 1.4}
                      fill="currentColor"
                    />
                  )),
                )}
              </svg>
              <span
                aria-hidden
                className="animate-float pointer-events-none absolute -bottom-6 -right-6 -z-10 hidden h-24 w-24 rounded-full bg-accent/10 sm:block sm:h-28 sm:w-28"
              />
              <span
                aria-hidden
                className="pointer-events-none absolute -bottom-1 -right-1 -z-10 hidden h-12 w-12 rounded-full border-2 border-brand/15 sm:block sm:h-14 sm:w-14"
              />
              <Reveal direction="up">
                <div className="relative overflow-hidden rounded-3xl border border-border bg-surface shadow-md ring-1 ring-brand/5 transition-shadow duration-500 hover:shadow-xl">
                  <div className="grid lg:grid-cols-[0.42fr_0.58fr]">
                    <div className="relative order-2 aspect-4/3 overflow-hidden bg-brand-light sm:aspect-16/10 lg:order-1 lg:aspect-auto lg:min-h-104">
                      {settings.principal_photo ? (
                        <Image
                          src={settings.principal_photo}
                          alt={settings.principal_name ?? "Kepala Madrasah"}
                          fill
                          // Container = max-w-7xl (1280px) - px-8 (64px) = 1216px konten.
                          // Kolom gambar 0.42fr → ±512px, JADI BUKAN 42vw: `vw` mengukur
                          // viewport, bukan container.
                          //
                          // Angka di `sizes` adalah piksel CSS; browser mengalikannya
                          // sendiri dengan DPR saat memilih dari srcset (512px @ DPR2 →
                          // ambil varian 1024w, yang kini terdaftar di imageSizes).
                          sizes="(min-width: 1280px) 512px, (min-width: 1024px) 42vw, 100vw"
                          quality={95}
                          className="object-cover"
                        />
                      ) : settings.logo ? (
                        <Image src={settings.logo} alt="" fill sizes="(min-width: 1024px) 42vw, 100vw" className="object-contain p-12" />
                      ) : (
                        <div className="grid h-full w-full place-items-center text-6xl font-black text-brand">M1</div>
                      )}
                      <span
                        aria-hidden
                        className="pointer-events-none absolute inset-y-0 right-0 hidden w-24 bg-linear-to-r from-transparent to-surface lg:block"
                      />
                      <span
                        aria-hidden
                        className="pointer-events-none absolute -left-4 -top-4 h-14 w-14 rounded-full border-4 border-white/25 sm:h-16 sm:w-16"
                      />
                      <span
                        aria-hidden
                        className="animate-float pointer-events-none absolute bottom-5 left-5 h-9 w-9 rotate-45 rounded-md bg-accent/80 shadow-[0_6px_16px_-4px_var(--color-accent)] sm:h-10 sm:w-10"
                      />
                      <span
                        aria-hidden
                        className="pointer-events-none absolute -inset-y-8 -left-1/3 w-1/3 -rotate-12 bg-linear-to-r from-transparent via-white/10 to-transparent"
                      />
                    </div>
                    <div className="relative order-1 flex flex-col justify-center overflow-hidden p-6 sm:p-10 lg:order-2 lg:p-14">
                      <span
                        aria-hidden
                        className="pointer-events-none absolute -right-2 -top-6 select-none font-serif text-[8rem] leading-none text-brand/6 sm:text-[11rem] lg:-top-10"
                      >
                        &rdquo;
                      </span>
                      <span
                        aria-hidden
                        className="pointer-events-none absolute -right-16 -top-16 h-40 w-40 rounded-full bg-linear-to-br from-accent/10 to-transparent blur-xl"
                      />
                      <span
                        aria-hidden
                        className="animate-drift pointer-events-none absolute -bottom-10 -right-10 h-36 w-36 rounded-full bg-brand/5 blur-2xl"
                      />
                      <span
                        aria-hidden
                        className="animate-pulse pointer-events-none absolute right-10 top-10 hidden h-2 w-2 rounded-full bg-accent sm:block"
                      />
                      <span
                        aria-hidden
                        className="pointer-events-none absolute right-20 top-16 hidden h-1.5 w-1.5 rounded-full bg-brand/40 sm:block"
                      />
                      <p className="relative text-[0.7rem] font-bold uppercase tracking-[0.2em] text-brand">Sambutan</p>
                      <p className="relative measure mt-4 text-lg font-medium leading-relaxed text-foreground sm:text-xl lg:text-[1.375rem]">
                        {settings.principal_word}
                      </p>
                      <div className="relative mt-8 flex items-center gap-3">
                        <span aria-hidden className="h-px w-10 shrink-0 bg-brand/50" />
                        <div>
                          <p className="text-base font-extrabold text-foreground">{settings.principal_name ?? "Kepala Madrasah"}</p>
                          <p className="text-sm text-ink-muted">Kepala Madrasah, {name}</p>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </Reveal>
            </div>
          </Container>
        </section>
      ) : null,

      "agenda_prestasi": (
      <section key="agenda_prestasi" data-section="Agenda" className="border-t border-border section-y">
        <Container className="grid gap-12 lg:grid-cols-2">
          <div>
            <div className="mb-5 flex items-end justify-between gap-4">
              <div>
                <p className="kicker">Kalender</p>
                <h2 className="text-h2 heading-rule mt-2">Agenda</h2>
              </div>
              <Button href="/agenda" variant="soft" size="sm">Semua</Button>
            </div>
            <div className="card divide-y divide-border overflow-hidden">
              {agendas.slice(0, 5).map((a) => {
                const start = new Date(a.start_at);
                const end = a.end_at ? new Date(a.end_at) : start;
                const now = new Date();
                const sameDay = start.toDateString() === now.toDateString();
                const status =
                  end.getTime() < now.getTime() && !sameDay
                    ? { tone: "muted" as const, label: "Selesai" }
                    : sameDay
                      ? { tone: "info" as const, label: "Hari ini" }
                      : null;
                return (
                <div key={a.slug} className="flex gap-4 p-5 transition hover:bg-surface-muted">
                  <div className="shrink-0 text-center">
                    <p className="tabular text-2xl font-extrabold leading-none text-brand">
                      {start.getDate()}
                    </p>
                    <p className="text-[0.65rem] font-bold uppercase text-ink-muted">
                      {start.toLocaleDateString("id-ID", { month: "short" })}
                    </p>
                  </div>
                  <div className="min-w-0 flex-1">
                    <p className="flex flex-wrap items-center gap-2 font-bold text-foreground">
                      {a.title}
                      {status && <Badge tone={status.tone}>{status.label}</Badge>}
                    </p>
                    <p className="mt-0.5 text-xs text-ink-muted">
                      {formatDateTime(a.start_at)}
                      {a.location ? ` · ${a.location}` : ""}
                    </p>
                    <div className="mt-2">
                      <AddToCalendar
                        title={a.title}
                        start={a.start_at}
                        end={a.end_at}
                        location={a.location}
                        description={a.description}
                      />
                    </div>
                  </div>
                </div>
                );
              })}
              {agendas.length === 0 && (
                <p className="p-6 text-sm text-ink-muted">Belum ada agenda mendatang.</p>
              )}
            </div>
          </div>

          <div>
            <div className="mb-5 flex items-end justify-between gap-4">
              <div>
                <p className="kicker">Capaian</p>
                <h2 className="text-h2 heading-rule mt-2">Prestasi Terkini</h2>
              </div>
              <Button href="/prestasi" variant="soft" size="sm">Semua</Button>
            </div>
            {achievements.data.length === 0 ? (
              <EmptyState>Data prestasi belum tersedia.</EmptyState>
            ) : (
              <div className="space-y-3">
                {achievements.data.slice(0, 5).map((a, i) => (
                  <div key={i} className="card card-hover flex items-center gap-4 p-4">
                    <span className="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-accent-soft text-accent">
                      <Icon path="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0zM7 6H4v2a3 3 0 0 0 3 3M17 6h3v2a3 3 0 0 1-3 3" />
                    </span>
                    <div className="min-w-0">
                      <p className="font-bold text-foreground">{a.title}</p>
                      <p className="text-xs text-ink-muted">
                        {[a.student_name, a.level, a.year].filter(Boolean).join(" · ")}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            )}
          </div>
        </Container>
      </section>
      ),

      "sarpras": (
      <section key="sarpras" id="sarpras" data-section="Fasilitas" className="section-y">
        <Container>
          <p className="kicker">Fasilitas</p>
          <h2 className="text-h2 heading-rule mt-2">Sarana &amp; Prasarana</h2>
          <p className="mt-3 max-w-xl text-lead">
            Ruang belajar, laboratorium, dan fasilitas pendukung untuk kegiatan akademik maupun ibadah.
          </p>
          <div className="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            {fasilitas.map((f, i) => (
              <Reveal key={f} delay={Math.min(i, 4) * 40}>
                <div className="card card-hover flex items-center gap-3 p-4">
                  <IconTile path="M3 21h18M5 21V7l7-4 7 4v14M9 21v-6h6v6" size="sm" />
                  <span className="text-sm font-semibold text-foreground">{f}</span>
                </div>
              </Reveal>
            ))}
          </div>
        </Container>
      </section>
      ),

      "ekstrakurikuler": ekskul.length > 0 ? (
        <section key="ekstrakurikuler" className="border-y border-border bg-surface-muted section-y">
          <Container>
            <p className="kicker">Pengembangan Diri</p>
            <h2 className="text-h2 heading-rule mt-2">Ekstrakurikuler</h2>
            <p className="mt-3 max-w-xl text-lead">
              Beragam wadah untuk menyalurkan minat, bakat, dan jiwa kepemimpinan peserta didik.
            </p>
            <div className="mt-8 flex flex-wrap gap-2.5">
              {ekskul.slice(0, 18).map((e) => (
                <Link
                  key={e.slug}
                  href="/ekstrakurikuler"
                  className="group inline-flex items-center gap-2 rounded-full border border-border bg-surface px-4 py-2.5 text-sm font-semibold text-ink-soft transition hover:-translate-y-0.5 hover:border-brand hover:text-brand hover:shadow-sm"
                >
                  <span className="h-1.5 w-1.5 rounded-full bg-brand transition group-hover:bg-accent" />
                  {e.name}
                </Link>
              ))}
            </div>
          </Container>
        </section>
      ) : null,

      "aplikasi": (
      <section key="aplikasi" id="aplikasi" data-section="Aplikasi" className="scroll-mt-24 border-y border-border section-y">
        <Container>
          <p className="kicker">Sistem Informasi</p>
          <h2 className="text-h2 heading-rule mt-2">Aplikasi Digital Madrasah</h2>
          <p className="mt-3 max-w-xl text-lead">
            Layanan digital untuk peserta didik, orang tua, guru, dan tenaga kependidikan.
          </p>
          <div className="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            {digitalApps.map((a, i) => (
              <Reveal key={a.label} delay={Math.min(i, 4) * 40}>
                <div className="group card card-hover flex flex-col items-center gap-3 p-5 text-center">
                  <IconTile path={a.icon} size="lg" interactive />
                  <span className="text-xs font-semibold text-foreground">{a.label}</span>
                </div>
              </Reveal>
            ))}
          </div>
        </Container>
      </section>
      ),

      "galeri": galleryPhotos.length > 0 ? (
        <section key="galeri" data-section="Galeri" className="border-t border-border section-y">
          <Container>
            <div className="mb-8 flex flex-wrap items-end justify-between gap-4">
              <div>
                <p className="kicker">Dokumentasi</p>
                <h2 className="text-h2 heading-rule mt-2">Galeri Kegiatan</h2>
              </div>
              <Button href="/galeri" variant="soft" size="sm">Semua galeri</Button>
            </div>
            <GalleryLightbox photos={galleryPhotos} />
          </Container>
        </section>
      ) : null,

      "kelembagaan": (
      <section key="kelembagaan" id="kelembagaan" className="scroll-mt-24 section-y">
        <Container>
          <p className="kicker">Predikat &amp; Program</p>
          <h2 className="text-h2 heading-rule mt-2">Komitmen Kelembagaan</h2>
          <div className="mt-10 grid gap-5 sm:grid-cols-3">
            {badgeProgram.map((b, i) => (
              <Reveal key={b.title} delay={Math.min(i, 4) * 60}>
                <div className="card card-hover flex h-full flex-col p-6">
                  <IconTile path="M12 2l2.4 5 5.6.8-4 4 1 5.6L12 20l-5 2.4 1-5.6-4-4 5.6-.8z" />
                  <h3 className="text-h3 mt-4">{b.title}</h3>
                  <p className="mt-2 text-sm leading-relaxed text-ink-muted">{b.text}</p>
                </div>
              </Reveal>
            ))}
          </div>
        </Container>
      </section>
      ),

      "cta_faq": (
      <section key="cta_faq" className="band-brand on-dark pattern-islamic faint grain relative overflow-hidden section-y-lg">
        <WaveDivider />
        <span aria-hidden className="stars" />
        <Container className="grid gap-10 lg:grid-cols-[0.9fr_1.1fr] lg:items-start">
          <div>
            <p className="kicker">Tanya Jawab · PMBM</p>
            <h2 className="text-h2 heading-rule mt-3">Siap bergabung dengan {name}?</h2>
            <p className="mt-3 text-white/90">
              Ikuti informasi Penerimaan Murid Baru Madrasah atau hubungi kami untuk pertanyaan lebih lanjut.
            </p>
            <div className="mt-6 flex flex-wrap gap-3">
              <Button
                href="/ppdb"
                size="lg"
                className="!border-transparent !bg-none !bg-white !text-brand-dark !shadow-none hover:!bg-white/90"
              >
                Info PMBM
              </Button>
              <Button href="/kontak" size="lg" variant="glass">Hubungi Kami</Button>
            </div>
          </div>
          <Accordion items={faqs} />
        </Container>
      </section>
      ),

      "lokasi": (
      <section key="lokasi" className="border-t border-border bg-surface-muted section-y">
        <Container className="grid gap-10 lg:grid-cols-[1fr_1.1fr] lg:items-stretch">
          <div>
            <p className="kicker">Kunjungi Kami</p>
            <h2 className="text-h2 heading-rule mt-2">Lokasi &amp; Kontak</h2>
            <dl className="mt-6 space-y-4">
              {settings.address && (
                <div className="flex gap-3">
                  <IconTile path="M12 21s-7-5.2-7-11a7 7 0 1 1 14 0c0 5.8-7 11-7 11zM12 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4z" />
                  <div>
                    <dt className="text-xs font-bold uppercase tracking-wide text-ink-muted">Alamat</dt>
                    <dd className="mt-0.5 font-medium text-foreground">{settings.address}</dd>
                  </div>
                </div>
              )}
              {settings.phone && (
                <div className="flex gap-3">
                  <IconTile path="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.7A2 2 0 0 1 4.1 2H7a2 2 0 0 1 2 1.7c.1 1.2.4 2.4.8 3.5a2 2 0 0 1-.5 2.1L8 10.6a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c1.1.4 2.3.7 3.5.8A2 2 0 0 1 22 16.9z" />
                  <div>
                    <dt className="text-xs font-bold uppercase tracking-wide text-ink-muted">Telepon</dt>
                    <dd className="mt-0.5 font-medium text-foreground">{settings.phone}</dd>
                  </div>
                </div>
              )}
              {settings.email && (
                <div className="flex gap-3">
                  <IconTile path="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zm0 2 8 6 8-6" />
                  <div>
                    <dt className="text-xs font-bold uppercase tracking-wide text-ink-muted">Email</dt>
                    <dd className="mt-0.5 font-medium text-foreground">{settings.email}</dd>
                  </div>
                </div>
              )}
              <div className="flex gap-3">
                <IconTile path="M12 8v4l3 2M12 3a9 9 0 1 0 0 18 9 9 0 0 0 0-18z" />
                <div>
                  <dt className="text-xs font-bold uppercase tracking-wide text-ink-muted">Jam Layanan</dt>
                  <dd className="mt-0.5 font-medium text-foreground">
                    {settings.service_hours ?? "Senin–Jumat, 07.00–15.30 WIB"}
                  </dd>
                </div>
              </div>
            </dl>
            <div className="mt-8 flex flex-wrap gap-3">
              <Button href="/kontak" size="lg">Kirim Pesan</Button>
              <Button href="/dokumen" size="lg" variant="outline" arrow={false}>
                Unduh Dokumen
              </Button>
            </div>
          </div>

          <div className="flex flex-col gap-3">
            <div className="min-h-64 flex-1 overflow-hidden rounded-2xl border border-border bg-surface shadow-sm">
              {settings.maps_embed ? (
                <iframe
                  src={settings.maps_embed}
                  className="h-full min-h-64 w-full"
                  loading="lazy"
                  referrerPolicy="no-referrer-when-downgrade"
                  title="Peta lokasi madrasah"
                />
              ) : (
                <div className="grid h-full min-h-64 place-items-center media-fallback text-sm font-bold">
                  Peta lokasi
                </div>
              )}
            </div>
            {(settings.address || settings.maps_url) && (
              <div className="flex flex-wrap gap-2">
                <a
                  href={
                    settings.maps_url ??
                    `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(settings.address ?? "MTsN 1 Kota Malang")}`
                  }
                  target="_blank"
                  rel="noreferrer"
                  className="inline-flex items-center gap-1.5 rounded-lg border border-border bg-surface px-3 py-2 text-xs font-semibold text-brand transition hover:border-brand hover:bg-brand-light"
                >
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 21s-7-5.2-7-11a7 7 0 1 1 14 0c0 5.8-7 11-7 11zM12 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4z" /></svg>
                  Buka di Google Maps
                </a>
                <a
                  href={`https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(settings.address ?? "MTsN 1 Kota Malang")}`}
                  target="_blank"
                  rel="noreferrer"
                  className="inline-flex items-center gap-1.5 rounded-lg border border-border bg-surface px-3 py-2 text-xs font-semibold text-brand transition hover:border-brand hover:bg-brand-light"
                >
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M3 11l19-9-9 19-2-8-8-2z" /></svg>
                  Buka rute
                </a>
              </div>
            )}
          </div>
        </Container>
      </section>
      ),

        };

        return orderedIds.map((id) => sectionNodes[id] ?? null);
      })()}
    </>
  );
}
