import Link from "next/link";
import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { getArchiveMonth, getPosts, getPostsPerPage } from "@/lib/api";
import type { ArchiveMonth } from "@/lib/types";
import { Container, EmptyState, PageHeader, Pagination, PostCard } from "@/components/ui";
import { Reveal } from "@/components/motion";
import { localeAlternates } from "@/lib/i18n";

// Membaca ?page= (searchParams) → harus dirender saat request, sama seperti
// arsip tag/kategori. Fetch-level revalidate di lib/api.ts tetap aktif.
export const dynamic = "force-dynamic";

/** Ubah segmen URL jadi angka yang masuk akal, atau null bila tak valid. */
function parseMonthParams(year: string, month: string) {
  const y = Number(year);
  const m = Number(month);
  if (!Number.isInteger(y) || !Number.isInteger(m)) return null;
  if (y < 1970 || y > 2200 || m < 1 || m > 12) return null;
  return { year: y, month: m };
}

export async function generateMetadata({
  params,
}: PageProps<"/[locale]/berita/arsip/[year]/[month]">): Promise<Metadata> {
  const { locale, year, month } = await params;
  const parsed = parseMonthParams(year, month);
  if (!parsed) return { title: "Arsip Berita" };

  try {
    const archive = await getArchiveMonth(parsed.year, parsed.month);
    return {
      title: `Arsip: ${archive.label}`,
      description: `Kumpulan ${archive.posts_count} berita yang terbit pada ${archive.label}.`,
      alternates: localeAlternates(
        locale,
        `/berita/arsip/${archive.year}/${String(archive.month).padStart(2, "0")}`,
      ),
    };
  } catch {
    return { title: "Arsip Berita" };
  }
}

export default async function ArsipBeritaPage({
  params,
  searchParams,
}: PageProps<"/[locale]/berita/arsip/[year]/[month]">) {
  const { year, month } = await params;
  const sp = await searchParams;
  const page = Number(sp.page ?? 1) || 1;

  const parsed = parseMonthParams(year, month);
  if (!parsed) notFound();

  let archive: ArchiveMonth;
  try {
    archive = await getArchiveMonth(parsed.year, parsed.month);
  } catch {
    // Bulan tanpa berita terbit → 404 (wp: arsip kosong tidak dilayani).
    notFound();
  }

  const posts = await getPosts({
    page,
    per_page: await getPostsPerPage(),
    year: archive.year,
    month: archive.month,
  });
  const { current_page, last_page } = posts.meta;
  const base = `/berita/arsip/${archive.year}/${String(archive.month).padStart(2, "0")}`;

  return (
    <>
      <PageHeader
        eyebrow="Arsip Berita"
        title={archive.label}
        subtitle={`${archive.posts_count} berita terbit pada ${archive.label}.`}
        breadcrumb={[{ label: "Berita", href: "/berita" }, { label: archive.label }]}
      />

      <Container className="section-y">
        <div className="mb-8">
          <Link
            href="/berita"
            className="inline-flex items-center gap-1.5 rounded-lg border border-border bg-surface px-4 py-2 text-sm font-semibold text-brand-dark transition hover:border-brand hover:bg-brand-light"
          >
            ← Semua berita
          </Link>
        </div>

        {posts.data.length === 0 ? (
          <EmptyState>Belum ada berita pada bulan ini.</EmptyState>
        ) : (
          <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            {posts.data.map((post, i) => (
              <Reveal key={post.id} delay={(i % 3) * 70} direction="up">
                <PostCard post={post} />
              </Reveal>
            ))}
          </div>
        )}

        <Pagination
          currentPage={current_page}
          lastPage={last_page}
          hrefFor={(p) => `${base}?page=${p}`}
        />
      </Container>
    </>
  );
}
