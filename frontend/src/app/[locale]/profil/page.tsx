import type { Metadata } from "next";
import { getPage, getSettings } from "@/lib/api";
import { PageBody, PageHeader, Stat } from "@/components/ui";
import { PrintButton } from "@/components/features-profil";

export const metadata: Metadata = { title: "Profil Madrasah" };
export const revalidate = 600;

export default async function ProfilIndex() {
  const [settings, profilPage, visiPage] = await Promise.all([
    getSettings().catch(() => ({}) as Record<string, string>),
    // Halaman index ini merangkum isi dua halaman CMS di bawahnya supaya
    // pengunjung langsung melihat identitas madrasah, bukan sekadar daftar
    // tautan. Sumbernya TETAP satu (halaman CMS) — di sini hanya dibaca,
    // tidak ditulis ulang, jadi tak ada dua versi data yang bisa menyimpang.
    getPage("profil-madrasah").catch(() => null),
    getPage("visi-dan-misi").catch(() => null),
  ]);

  /** Ambil blok pertama bertipe tertentu dari sebuah halaman CMS. */
  const blockOf = <T,>(
    page: { blocks?: { type: string; data: unknown }[] } | null,
    type: string,
  ): T | null =>
    (page?.blocks?.find((b) => b.type === type)?.data as T | undefined) ?? null;

  const sejarah = blockOf<{ subtitle: string | null }>(profilPage, "hero");
  const timeline = blockOf<{
    items: { label: string; title: string; description: string | null }[];
  }>(profilPage, "timeline");
  const kepala = blockOf<{
    columns: { label: string }[];
    rows: { cells: { value: string }[] }[];
  }>(profilPage, "table");

  const visi = blockOf<{ title: string; subtitle: string | null }>(visiPage, "hero");
  const motto = blockOf<{ text: string; attribution: string | null }>(visiPage, "quote");
  const misi = blockOf<{ items: { title: string }[] }>(visiPage, "steps");

  return (
    <>
      <PageHeader
        eyebrow="Tentang"
        title="Profil Madrasah"
        subtitle="Sejarah, visi-misi, struktur, dan informasi kelembagaan madrasah."
        breadcrumb={[{ label: "Profil" }]}
      />
      <PageBody>
        <div className="mb-6 flex justify-end">
          <PrintButton label="Unduh / Cetak profil" />
        </div>

        {/* Ringkasan singkat — angka lengkap & riwayat penuh ada di
            /profil/profil-madrasah, supaya tidak ada dua sumber data
            (index & halaman detail) yang bisa saling menyimpang. */}
        {(settings.npsn || settings.nsm || settings.stat_accreditation || settings.principal_name) && (
          <div className="mb-10 grid grid-cols-2 gap-6 rounded-2xl border border-border bg-surface-muted p-6 sm:grid-cols-4">
            {settings.npsn && <Stat value={settings.npsn} label="NPSN" />}
            {settings.nsm && <Stat value={settings.nsm} label="NSM" />}
            {settings.stat_accreditation && (
              <Stat value={settings.stat_accreditation} label="Akreditasi" />
            )}
            {settings.principal_name && (
              /*
                Nama ditampilkan UTUH. Sebelumnya dipotong dua kata pertama
                (`split(" ").slice(0, 2)`), yang mengubah "Dra. Erni Qomaria
                Rida, M.Pd." menjadi "Dra. Erni" — pemotongan itu memperlakukan
                gelar depan seolah nama diri.

                Ukuran font dibuat lebih kecil dari Stat angka di sebelahnya
                (nama jauh lebih panjang daripada "A" atau deretan digit) dan
                `text-balance` membagi baris secara merata saat membungkus.
              */
              <div className="col-span-2 text-center sm:col-span-1 sm:text-left">
                <p className="text-balance text-lg font-extrabold leading-tight tracking-tight text-foreground sm:text-xl">
                  {settings.principal_name}
                </p>
                <p className="mt-1 text-xs font-medium uppercase tracking-wide text-ink-muted">
                  Kepala Madrasah
                </p>
              </div>
            )}
          </div>
        )}

        {/* ---- Sekilas madrasah (dari halaman CMS "Profil Madrasah") ---- */}
        {sejarah?.subtitle && (
          <section className="mb-10">
            <h2 className="text-h2 heading-rule">Sekilas MTsN 1 Kota Malang</h2>
            {/* Tanpa `.measure` (65ch) — teks mengisi penuh lebar halaman. */}
            <p className="mt-4 text-lead">{sejarah.subtitle}</p>
          </section>
        )}

        {/* ---- Visi, motto, misi (dari halaman CMS "Visi dan Misi") ---- */}
        {(visi || motto || misi) && (
          <section className="mb-10 grid items-stretch gap-5 lg:grid-cols-2">
            {visi?.title && (
              <div className="card flex flex-col p-6">
                <p className="kicker">Visi</p>
                <p className="mt-3 text-balance text-lg font-bold leading-snug text-foreground">
                  {visi.title}
                </p>
                {motto?.text && (
                  <p className="mt-4 border-t border-border pt-4 text-sm italic text-brand-dark">
                    {/* `text` dari blok quote berupa HTML sederhana (<p>…</p>) —
                        tag dibuang agar aman dirender sebagai teks biasa. */}
                    &ldquo;{motto.text.replace(/<[^>]+>/g, "").trim()}&rdquo;
                    {motto.attribution && (
                      <span className="mt-1 block not-italic text-xs font-semibold uppercase tracking-wide text-ink-muted">
                        {motto.attribution}
                      </span>
                    )}
                  </p>
                )}
              </div>
            )}
            {misi?.items?.length ? (
              <div className="card flex flex-col p-6">
                <p className="kicker">Misi</p>
                <ol className="mt-3 space-y-2.5">
                  {misi.items.map((m, i) => (
                    <li key={i} className="flex gap-3 text-sm leading-relaxed text-ink-soft">
                      <span className="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-brand-light text-[0.7rem] font-bold text-brand-dark">
                        {i + 1}
                      </span>
                      {m.title}
                    </li>
                  ))}
                </ol>
              </div>
            ) : null}
          </section>
        )}

        {/* ---- Perjalanan madrasah ---- */}
        {timeline?.items?.length ? (
          <section className="mb-10">
            <h2 className="text-h2 heading-rule">Perjalanan Madrasah</h2>
            <ol className="mt-5 space-y-4 border-l-2 border-border pl-5">
              {timeline.items.map((t, i) => (
                <li key={i} className="relative">
                  <span
                    aria-hidden
                    className="absolute left-[-1.6rem] top-1.5 h-3 w-3 rounded-full border-2 border-surface bg-brand"
                  />
                  <p className="text-sm font-bold text-brand">{t.label}</p>
                  <p className="font-semibold text-foreground">{t.title}</p>
                  {t.description && (
                    <p className="mt-1 text-sm leading-relaxed text-ink-soft">
                      {t.description}
                    </p>
                  )}
                </li>
              ))}
            </ol>
          </section>
        ) : null}

        {/* ---- Daftar kepala madrasah ---- */}
        {kepala?.rows?.length ? (
          <section className="mb-10">
            <h2 className="text-h2 heading-rule">Kepala Madrasah dari Masa ke Masa</h2>
            <div className="mt-5 overflow-x-auto rounded-2xl border border-border">
              <table className="w-full min-w-88 border-collapse text-sm">
                <thead className="bg-surface-muted">
                  <tr>
                    {(kepala.columns ?? []).map((c, i) => (
                      <th
                        key={i}
                        scope="col"
                        /* Kolom pertama (Periode) dibuat sesempit isinya supaya
                           sisa lebar jatuh ke kolom nama — tanpa ini tabel
                           `w-full` membagi rata dan kolom periode menganga. */
                        className={`px-4 py-2.5 text-left text-xs font-bold uppercase tracking-wide text-ink-muted ${i === 0 ? "w-px whitespace-nowrap" : ""}`}
                      >
                        {c.label}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {kepala.rows.map((r, i) => (
                    <tr key={i} className="border-t border-border">
                      {r.cells.map((c, j) => (
                        <td
                          key={j}
                          className={`px-4 py-2.5 ${j === 0 ? "whitespace-nowrap font-semibold text-brand-dark" : "text-ink-soft"}`}
                        >
                          {c.value}
                        </td>
                      ))}
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>
        ) : null}

      </PageBody>
    </>
  );
}
