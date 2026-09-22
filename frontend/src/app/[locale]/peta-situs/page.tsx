import Link from "next/link";
import type { Metadata } from "next";
import { getMenu } from "@/lib/api";
import { PageBody, PageHeader } from "@/components/ui";
import { buildNav, isNavGroup } from "@/lib/nav";

export const metadata: Metadata = {
  title: "Peta Situs",
  description: "Daftar lengkap seluruh halaman dan tautan pada situs MTsN 1 Kota Malang.",
};
export const dynamic = "force-dynamic";

export default async function PetaSitusPage() {
  const menu = await getMenu("header");
  const nav = buildNav(menu);
  const groups = nav.filter(isNavGroup);
  const leaves = nav.filter((e) => !isNavGroup(e)) as { label: string; href: string }[];

  return (
    <>
      <PageHeader
        eyebrow="Navigasi"
        title="Peta Situs"
        subtitle="Semua halaman dan tautan yang tersedia pada situs, dikelompokkan sesuai menu."
        breadcrumb={[{ label: "Peta Situs" }]}
      />
      <PageBody>
        <div className="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
          {groups.map((g) => (
            <nav key={g.label} aria-label={g.label} className="card p-6">
              <h2 className="flex items-center gap-2.5 text-h3">
                <span className="grid h-9 w-9 place-items-center rounded-xl bg-brand-light text-brand-dark">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                    <path d={g.icon} />
                  </svg>
                </span>
                {g.label}
              </h2>
              {(g.sections ?? [{ title: "", items: g.items }]).map((sec) => (
                <div key={sec.title || "flat"} className="mt-3">
                  {sec.title && (
                    <p className="text-[0.7rem] font-bold uppercase tracking-wide text-ink-muted">{sec.title}</p>
                  )}
                  <ul className="mt-1.5 space-y-2 text-sm">
                    {sec.items.map((it) => {
                      const ext = it.href.startsWith("http");
                      return (
                        <li key={`${it.label}-${it.href}`}>
                          {ext ? (
                            <a href={it.href} target="_blank" rel="noreferrer" className="text-ink-soft transition hover:text-brand">
                              {it.label} ↗
                            </a>
                          ) : (
                            <Link href={it.href} className="text-ink-soft transition hover:text-brand">
                              {it.label}
                            </Link>
                          )}
                        </li>
                      );
                    })}
                  </ul>
                </div>
              ))}
            </nav>
          ))}
        </div>

        <div className="mt-8 card p-6">
          <h2 className="text-h3">Halaman Utama</h2>
          <ul className="mt-3 flex flex-wrap gap-x-6 gap-y-2 text-sm">
            {leaves.map((l) => (
              <li key={l.href}>
                <Link href={l.href} className="text-ink-soft transition hover:text-brand">
                  {l.label}
                </Link>
              </li>
            ))}
            <li>
              <Link href="/pencarian" className="text-ink-soft transition hover:text-brand">
                Pencarian
              </Link>
            </li>
          </ul>
        </div>
      </PageBody>
    </>
  );
}
