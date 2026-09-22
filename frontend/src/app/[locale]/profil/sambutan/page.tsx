import Image from "@/components/media-image";
import type { Metadata } from "next";
import { getSettings } from "@/lib/api";
import { PageBody, PageHeader } from "@/components/ui";
import { Reveal } from "@/components/motion";

export const metadata: Metadata = { title: "Sambutan Kepala Madrasah" };
export const revalidate = 600;

export default async function SambutanPage() {
  const settings = await getSettings().catch(() => ({}) as Record<string, string>);
  const name = settings.site_name ?? "MTsN 1 Kota Malang";

  return (
    <>
      <PageHeader
        eyebrow="Profil"
        title="Sambutan Kepala Madrasah"
        breadcrumb={[{ label: "Profil", href: "/profil" }, { label: "Sambutan Kepala Madrasah" }]}
      />
      <PageBody>
        <div className="relative">
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
                      // Lihat catatan di [locale]/page.tsx: 0.42fr adalah pecahan
                      // container (±512px), bukan 42% viewport.
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
                    {settings.principal_word ??
                      `Selamat datang di ${name}. Kami berikhtiar menghadirkan pendidikan yang menyeimbangkan iman, ilmu, dan amal, dengan lingkungan belajar yang islami, hijau, dan modern.`}
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
      </PageBody>
    </>
  );
}
