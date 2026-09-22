import Image from "@/components/media-image";
import Link from "next/link";
import type { ComponentProps, ReactNode } from "react";
import type { Post } from "@/lib/types";
import { formatDate } from "@/lib/format";

/* ---------- Section ---------- */

export function Section({
  children,
  muted = false,
  size = "md",
  className = "",
  id,
}: {
  children: ReactNode;
  muted?: boolean;
  /** Ritme vertikal: "md" (standar) atau "lg" (momen besar spt hero/CTA). */
  size?: "md" | "lg";
  className?: string;
  id?: string;
}) {
  return (
    <section
      id={id}
      className={`${size === "lg" ? "section-y-lg" : "section-y"} ${muted ? "bg-surface-muted" : ""} ${className}`}
    >
      {children}
    </section>
  );
}

/* ---------- Container ---------- */

export function Container({
  children,
  className = "",
}: {
  children: ReactNode;
  className?: string;
}) {
  return (
    <div className={`mx-auto w-full max-w-7xl px-5 sm:px-6 lg:px-8 ${className}`}>{children}</div>
  );
}

/* ---------- Page body (isi halaman dalam — ritme vertikal seragam) ---------- */

export function PageBody({
  children,
  className = "",
}: {
  children: ReactNode;
  className?: string;
}) {
  return <Container className={`section-y ${className}`}>{children}</Container>;
}

/* ---------- Page header (halaman dalam) ---------- */

export function PageHeader({
  eyebrow,
  title,
  subtitle,
  breadcrumb = [],
}: {
  eyebrow?: string;
  title: string;
  subtitle?: string;
  breadcrumb?: { label: string; href?: string }[];
}) {
  return (
    <section className="mesh pattern-islamic faint relative overflow-hidden border-b border-border">
      {/*
        Ornamen dekoratif di kanan (dua bingkai geometris + motif bintang
        `.motif-star` / ikon `motifIcon`) dihapus — bentuknya besar dan
        mendominasi area header. Latar `mesh pattern-islamic faint` pada
        <section> di atas tetap ada sebagai tekstur halus.
      */}
      <Container className="relative section-y">
        <nav
          aria-label="Breadcrumb"
          className="flex flex-wrap items-center gap-x-1.5 gap-y-1 text-xs font-semibold text-ink-muted"
        >
          <Link href="/" className="transition hover:text-brand">
            Beranda
          </Link>
          {breadcrumb.map((b, i) => {
            const last = i === breadcrumb.length - 1;
            return (
              <span key={b.label} className="flex items-center gap-1.5">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" className="text-border-strong" aria-hidden>
                  <path d="M9 6l6 6-6 6" />
                </svg>
                {b.href && !last ? (
                  <Link href={b.href} className="transition hover:text-brand">
                    {b.label}
                  </Link>
                ) : (
                  <span className="text-foreground" aria-current={last ? "page" : undefined}>
                    {b.label}
                  </span>
                )}
              </span>
            );
          })}
        </nav>
        {/*
          Garis aksen kiri menambah inset ~20px di sisi kiri saja; tanpa
          penyeimbang di kanan, judul panjang tampak terdorong dan hampir
          menyentuh tepi layar di ponsel. `pr-*` mengembalikan keseimbangan
          optisnya, dan `max-w-4xl` memberi judul ruang lebih untuk bernapas
          pada skala tipografi yang kini lebih besar.
        */}
        <div className="mt-6 border-l-4 border-brand pl-4 pr-2 sm:pl-5 sm:pr-4">
          {eyebrow && (
            <span className="inline-flex items-center gap-1.5 rounded-full border border-brand/20 bg-brand-light px-3 py-1 text-[0.7rem] font-bold uppercase tracking-[0.14em] text-brand-dark">
              <span className="h-1.5 w-1.5 rotate-45 bg-accent" />
              {eyebrow}
            </span>
          )}
          <h1 className="text-display reveal-wipe mt-3 max-w-4xl text-balance">{title}</h1>
          {subtitle && <p className="measure mt-4 text-lead">{subtitle}</p>}
        </div>
      </Container>
    </section>
  );
}

/* ---------- Button ---------- */

type ButtonProps = {
  variant?: "primary" | "accent" | "outline" | "ghost" | "glass" | "soft";
  size?: "md" | "lg" | "sm";
  arrow?: boolean;
} & ComponentProps<typeof Link>;

const btnBase =
  "group/btn btn-glow inline-flex items-center justify-center gap-2 rounded-xl font-semibold tracking-tight transition duration-200 active:scale-[.98] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-ring";

const btnVariants: Record<string, string> = {
  primary:
    "text-on-brand shadow-brand ring-1 ring-inset ring-white/15 hover:-translate-y-0.5 [background-image:var(--gradient-brand)] hover:shadow-[0_16px_36px_-12px_color-mix(in_oklab,var(--brand)_55%,transparent)] hover:brightness-[1.04]",
  accent:
    "bg-accent text-on-accent shadow-brand ring-1 ring-inset ring-white/15 hover:-translate-y-0.5 hover:brightness-[1.05]",
  outline:
    "border border-border-strong bg-surface text-foreground hover:border-brand hover:bg-brand-light hover:text-brand-dark",
  ghost: "text-brand hover:bg-brand-light",
  glass: "border border-white/25 bg-white/10 text-white backdrop-blur hover:bg-white/20",
  soft: "border border-border bg-surface text-brand hover:border-brand hover:bg-brand-light",
};

const btnSizes: Record<string, string> = {
  sm: "min-h-9 px-4 text-sm",
  md: "min-h-10 px-5 text-sm",
  lg: "min-h-12 px-7 text-[0.95rem]",
};

export function Button({
  variant = "primary",
  size = "md",
  arrow = true,
  className = "",
  children,
  ...props
}: ButtonProps) {
  return (
    <Link
      className={`${btnBase} ${btnVariants[variant]} ${btnSizes[size]} ${className}`}
      {...props}
    >
      {children}
      {arrow && (
      <svg
        width="16"
        height="16"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        strokeWidth="2.4"
        strokeLinecap="round"
        strokeLinejoin="round"
        aria-hidden
        className="arrow-shift -mr-1 opacity-80"
      >
        <path d="M5 12h14M13 6l6 6-6 6" />
      </svg>
      )}
    </Link>
  );
}

/* ---------- Badge ---------- */

export function Badge({
  children,
  tone = "brand",
}: {
  children: ReactNode;
  tone?: "brand" | "accent" | "muted" | "success" | "warning" | "info";
}) {
  const tones: Record<string, string> = {
    brand: "border-brand/20 bg-brand-light text-brand-dark",
    accent: "border-accent/25 bg-accent-soft text-accent",
    muted: "border-border-strong bg-surface-muted text-ink-muted",
    success: "border-success/25 bg-success-soft text-success",
    warning: "border-warning/25 bg-warning-soft text-warning",
    info: "border-info/25 bg-info-soft text-info",
  };
  const dot: Record<string, string> = {
    brand: "bg-brand",
    accent: "bg-accent",
    muted: "bg-ink-muted",
    success: "bg-success",
    warning: "bg-warning",
    info: "bg-info",
  };
  return (
    <span
      className={`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-0.5 text-[0.65rem] font-bold uppercase tracking-[0.07em] ${tones[tone]}`}
    >
      <span className={`h-1.5 w-1.5 shrink-0 rounded-full ${dot[tone]}`} />
      {children}
    </span>
  );
}

/* ---------- Eyebrow ---------- */

export function Eyebrow({
  children,
  className = "",
}: {
  children: ReactNode;
  className?: string;
}) {
  return (
    <span
      className={`inline-flex items-center gap-2 text-xs font-bold uppercase tracking-[0.16em] ${className}`}
    >
      <span className="h-px w-6 bg-current opacity-40" />
      {children}
    </span>
  );
}

/* ---------- Card ---------- */

export function Card({
  children,
  className = "",
  as: As = "div",
}: {
  children: ReactNode;
  className?: string;
  as?: React.ElementType;
}) {
  return <As className={`card ${className}`}>{children}</As>;
}

/* ---------- Icon + IconTile (ubin ikon konsisten) ---------- */

export function Icon({
  path,
  size = 20,
  className = "",
}: {
  path: string;
  size?: number;
  className?: string;
}) {
  return (
    <svg
      width={size}
      height={size}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.8"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden
      className={className}
    >
      <path d={path} />
    </svg>
  );
}

const iconTileSizes = {
  sm: "h-9 w-9 rounded-lg",
  md: "h-11 w-11 rounded-xl",
  lg: "h-12 w-12 rounded-xl",
} as const;

/**
 * Ubin ikon brand yang seragam di seluruh situs.
 * `interactive` mengaktifkan perubahan warna saat kartu/tautan induk (.group) di-hover.
 */
export function IconTile({
  path,
  size = "md",
  interactive = false,
  className = "",
}: {
  path: string;
  size?: keyof typeof iconTileSizes;
  interactive?: boolean;
  className?: string;
}) {
  return (
    <span
      className={`grid shrink-0 place-items-center bg-brand-light text-brand-dark ring-1 ring-inset ring-brand/10 ${iconTileSizes[size]} ${
        interactive
          ? "transition-all duration-300 group-hover:-translate-y-0.5 group-hover:bg-brand group-hover:text-on-brand group-hover:ring-brand/0 group-hover:shadow-brand"
          : ""
      } ${className}`}
    >
      <Icon path={path} size={size === "sm" ? 16 : size === "lg" ? 22 : 20} />
    </span>
  );
}

/* ---------- Cover fallback (kartu tanpa gambar) ---------- */

export function CoverFallback({
  label = "MTsN 1",
  className = "",
}: {
  label?: string;
  className?: string;
}) {
  return (
    <div
      className={`media-fallback grid h-full w-full place-items-center ${className}`}
      aria-hidden
    >
      <span className="flex items-center gap-2 opacity-80">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round">
          <path d="M3 21h18M5 21V8l7-4 7 4v13M9 21v-5h6v5M9 12h.01M15 12h.01" />
        </svg>
        <span className="text-sm font-extrabold tracking-tight">{label}</span>
      </span>
    </div>
  );
}

/* ---------- Hub card + grid (halaman ikhtisar kelompok) ---------- */

export type HubItem = { title: string; desc: string; href: string; icon: string; cta?: string };

export function HubCard({ item, index }: { item: HubItem; index?: number }) {
  return (
    <Link
      href={item.href}
      className="group card card-hover relative flex h-full flex-col overflow-hidden p-6"
    >
      <span aria-hidden className="corner-frame pointer-events-none absolute inset-0" />
      <div className="flex items-start justify-between gap-3">
        <IconTile path={item.icon} size="lg" interactive />
        {index != null && (
          <span aria-hidden className="tabular text-3xl font-black leading-none text-brand/10 transition-colors group-hover:text-brand/20">
            {String(index).padStart(2, "0")}
          </span>
        )}
      </div>
      <h2 className="text-h3 mt-4">{item.title}</h2>
      <p className="mt-1.5 flex-1 text-sm leading-relaxed text-ink-muted">{item.desc}</p>
      <span className="mt-4 flex items-center gap-1.5 border-t border-border pt-3 text-sm font-bold text-brand">
        {item.cta ?? "Selengkapnya"}
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round" aria-hidden className="arrow-shift">
          <path d="M5 12h14M13 6l6 6-6 6" />
        </svg>
      </span>
    </Link>
  );
}

export function HubGrid({
  items,
  numbered = false,
  className = "sm:grid-cols-2 lg:grid-cols-3",
}: {
  items: HubItem[];
  numbered?: boolean;
  className?: string;
}) {
  return (
    <div className={`grid gap-5 ${className}`}>
      {items.map((it, i) => (
        <HubCard key={it.href} item={it} index={numbered ? i + 1 : undefined} />
      ))}
    </div>
  );
}

/* ---------- Feature card ---------- */

export function FeatureCard({
  icon,
  title,
  children,
  className = "",
}: {
  icon: string;
  title: string;
  children: ReactNode;
  className?: string;
}) {
  return (
    <div className={`group card card-hover h-full p-6 ${className}`}>
      <IconTile path={icon} interactive />
      <h3 className="text-h3 mt-4">{title}</h3>
      <p className="mt-1.5 text-sm leading-relaxed text-ink-muted">{children}</p>
    </div>
  );
}

/* ---------- Section heading ---------- */

export function SectionTitle({
  eyebrow,
  title,
  subtitle,
  href,
  hrefLabel = "Lihat semua",
  align = "start",
}: {
  eyebrow?: string;
  title: string;
  subtitle?: string;
  href?: string;
  hrefLabel?: string;
  align?: "start" | "center";
}) {
  const centered = align === "center";
  return (
    <div
      className={`mb-8 flex flex-wrap gap-x-6 gap-y-3 ${
        centered ? "flex-col items-center text-center" : "items-end justify-between"
      }`}
    >
      <div className="max-w-2xl">
        {eyebrow && <Eyebrow className="mb-2 text-brand">{eyebrow}</Eyebrow>}
        <h2 className="text-h2">{title}</h2>
        {subtitle && <p className="mt-2 text-lead">{subtitle}</p>}
      </div>
      {href && !centered && (
        <Link
          href={href}
          className="group arrow-link inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-border bg-surface px-4 py-2 text-sm font-semibold text-brand transition hover:border-brand hover:bg-brand-light"
        >
          {hrefLabel}
          <span className="arrow-shift">→</span>
        </Link>
      )}
    </div>
  );
}

/* ---------- Empty state ---------- */

export function EmptyState({ children }: { children: ReactNode }) {
  return (
    <div className="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-border-strong bg-surface-muted/50 px-6 py-14 text-center text-sm text-ink-muted">
      <span className="grid h-12 w-12 place-items-center rounded-xl bg-surface text-ink-muted shadow-sm ring-1 ring-inset ring-border">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" strokeLinejoin="round" aria-hidden>
          <path d="M4 7h16M4 12h16M4 17h10" />
        </svg>
      </span>
      {children}
    </div>
  );
}

/* ---------- Pagination ---------- */

/**
 * Navigasi halaman seragam untuk semua arsip berita. `hrefFor(page)`
 * mengembalikan URL untuk nomor halaman tertentu (query `?page=`).
 */
export function Pagination({
  currentPage,
  lastPage,
  hrefFor,
}: {
  currentPage: number;
  lastPage: number;
  hrefFor: (page: number) => string;
}) {
  if (lastPage <= 1) return null;

  const linkCls =
    "btn-chip min-h-10 disabled:pointer-events-none disabled:opacity-40";

  return (
    <nav
      aria-label="Navigasi halaman"
      className="mt-12 flex items-center justify-center gap-3"
    >
      {currentPage > 1 ? (
        <Link href={hrefFor(currentPage - 1)} rel="prev" className={linkCls}>
          <span aria-hidden>←</span> Sebelumnya
        </Link>
      ) : (
        <span className={linkCls} aria-disabled>
          <span aria-hidden>←</span> Sebelumnya
        </span>
      )}

      <span className="text-xs font-semibold tabular-nums text-ink-muted">
        Halaman <span className="text-foreground">{currentPage}</span> /{" "}
        {lastPage}
      </span>

      {currentPage < lastPage ? (
        <Link href={hrefFor(currentPage + 1)} rel="next" className={linkCls}>
          Berikutnya <span aria-hidden>→</span>
        </Link>
      ) : (
        <span className={linkCls} aria-disabled>
          Berikutnya <span aria-hidden>→</span>
        </span>
      )}
    </nav>
  );
}

/* ---------- Post card ---------- */

export function PostCard({
  post,
  priority = false,
}: {
  post: Post;
  priority?: boolean;
}) {
  return (
    <article className="group card card-hover relative flex h-full flex-col overflow-hidden">
      <Link href={`/berita/${post.slug}`} className="flex h-full flex-col">
        <div className="relative aspect-16/10 overflow-hidden bg-surface-muted">
          {post.cover ? (
            <Image
              src={post.cover}
              alt={post.cover_alt ?? post.title}
              fill
              priority={priority}
              sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
              className="object-cover transition duration-500 ease-out group-hover:scale-[1.04]"
            />
          ) : (
            <CoverFallback />
          )}
          {post.category && (
            <div className="absolute left-3 top-3">
              <span className="inline-flex items-center rounded-md bg-surface/95 px-2 py-0.5 text-[0.62rem] font-bold uppercase tracking-[0.06em] text-brand-dark">
                {post.category.name}
              </span>
            </div>
          )}
          {/* wp: sticky post. Toggle "Sorotan" di panel sebelumnya tak pernah
              terlihat pengunjung. Ditaruh kanan-atas karena kiri dipakai kategori. */}
          {post.is_featured && (
            <div className="absolute right-3 top-3">
              <span className="inline-flex items-center rounded-md bg-brand px-2 py-0.5 text-[0.62rem] font-bold uppercase tracking-[0.06em] text-on-brand shadow-sm">
                Sorotan
              </span>
            </div>
          )}
        </div>
        <div className="flex flex-1 flex-col p-5">
          <h3 className="text-lg font-bold leading-snug text-foreground transition group-hover:text-brand">
            {post.title}
          </h3>
          <p className="mt-2 line-clamp-2 flex-1 text-sm text-ink-muted">{post.excerpt}</p>
          <div className="mt-4 flex items-center gap-x-2 gap-y-1 border-t border-border/70 pt-3 text-xs font-medium text-ink-muted">
            <span className="inline-flex items-center gap-1.5 whitespace-nowrap">
              <span className="h-1.5 w-1.5 shrink-0 rounded-full bg-brand" />
              {formatDate(post.published_at)}
            </span>
            {post.author?.name && (
              <span className="min-w-0 truncate before:mr-2 before:text-border-strong before:content-['•']">
                {post.author.name}
              </span>
            )}
            <span className="ml-auto inline-flex items-center gap-2 whitespace-nowrap">
              {typeof post.comments_count === "number" &&
                post.comments_count > 0 && (
                  <span className="inline-flex items-center gap-1 text-ink-muted">
                    <svg
                      width="12"
                      height="12"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeWidth="2"
                      aria-hidden
                    >
                      <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                    </svg>
                    {post.comments_count}
                  </span>
                )}
              <span className="font-bold text-brand opacity-0 transition group-hover:opacity-100">
                Baca <span className="arrow-shift inline-block">→</span>
              </span>
            </span>
          </div>
        </div>
      </Link>
    </article>
  );
}

/* ---------- Stat ---------- */

export function Stat({ value, label }: { value: string; label: string }) {
  return (
    <div className="text-center sm:text-left">
      <p className="text-2xl font-extrabold tracking-tight text-foreground sm:text-3xl">{value}</p>
      <p className="mt-0.5 text-xs font-medium uppercase tracking-wide text-ink-muted">{label}</p>
    </div>
  );
}
