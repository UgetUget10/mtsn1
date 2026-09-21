import Link from "next/link";
import type { SectionNavItem } from "@/lib/section-menus";

export type { SectionNavItem };

/**
 * Navigasi antar sub-halaman dalam satu kelompok menu (dipasang di bawah PageHeader).
 * Menandai halaman aktif lewat `current` (harus cocok persis dengan href).
 */
export function SectionNav({
  items,
  current,
  className = "",
}: {
  items: SectionNavItem[];
  current: string;
  className?: string;
}) {
  return (
    <nav aria-label="Sub-halaman" className={className}>
      <div className="-mx-4 flex gap-1.5 overflow-x-auto px-4 pb-3 [scrollbar-width:none] sm:mx-0 sm:flex-wrap sm:px-0 [&::-webkit-scrollbar]:hidden">
        {items.map((it) => {
          const active = it.href === current;
          const cls = `inline-flex shrink-0 items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-semibold transition ${
            active
              ? "bg-brand text-on-brand shadow-sm"
              : "bg-surface-muted text-ink-soft hover:bg-brand-light hover:text-brand-dark"
          }`;
          return it.external ? (
            <a key={it.href} href={it.href} target="_blank" rel="noreferrer" className={cls}>
              {it.label}
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" className="opacity-70">
                <path d="M7 17 17 7M8 7h9v9" />
              </svg>
            </a>
          ) : (
            <Link key={it.href} href={it.href} aria-current={active ? "page" : undefined} className={cls}>
              {it.label}
            </Link>
          );
        })}
      </div>
      <div className="h-px bg-border" />
    </nav>
  );
}
