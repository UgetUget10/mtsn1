import { draftMode } from "next/headers";
import { cookies } from "next/headers";
import { locale } from "next/root-params";
import type {
  Achievement,
  Agenda,
  ApiMenu,
  ArchiveMonth,
  AuthorProfile,
  CategoryDetail,
  CategoryWithCount,
  CommentThread,
  DocumentItem,
  Extracurricular,
  Gallery,
  LaravelPage,
  NavPage,
  Paginated,
  PageWithBlocks,
  Post,
  SearchResult,
  Settings,
  Slide,
  TagDetail,
  TagWithCount,
  Teacher,
  Testimonial,
  WidgetArea,
} from "./types";

/** Nama cookie tempat /api/preview menyimpan token pratinjau dari backend. */
export const PREVIEW_TOKEN_COOKIE = "mtsn1_preview_token";

// Server-only: hindari fetch server-ke-diri-sendiri lewat domain publik
// (DNS/HTTPS/firewall di luar kendali kita). INTERNAL_API_URL menunjuk
// langsung ke backend di mesin yang sama; NEXT_PUBLIC_API_URL tetap dipakai
// browser (lihat file lain yang membaca env yang sama untuk href/redirect
// yang memang harus publik).
const BASE =
  process.env.INTERNAL_API_URL ?? process.env.NEXT_PUBLIC_API_URL ?? "http://127.0.0.1:8000/api/v1";

type Opts = {
  revalidate?: number;
  query?: Record<string, string | number | undefined>;
  /**
   * Cache tag Next — dipakai backend untuk purge selektif lewat
   * /revalidate (App\Support\Revalidation\RevalidationTargets). Nama tag
   * di sini HARUS sama dengan yang dikirim backend.
   */
  tags?: string[];
};

/**
 * Sisipkan ?locale= dari root param `[locale]` (lihat frontend/src/app/[locale])
 * ke setiap query, supaya konten translatable (Post/Page/Category/MenuItem —
 * lihat backend App\Http\Middleware\SetApiLocale) mengikuti locale aktif.
 * `next/root-params` hanya berjalan di Server Component; dipanggil di sini
 * (bukan tiap pemanggil) supaya pemanggil tidak perlu tahu soal locale sama
 * sekali.
 *
 * PENTING: di Route Handler (mis. `app/sitemap.ts`, `app/robots.ts`) `locale()`
 * MELEMPAR SECARA SINKRON — "Support for this API in Route Handlers is planned
 * for a future version of Next.js". Karena itu `try/catch`, BUKAN `.catch()`:
 * `.catch()` hanya menangkap promise yang ditolak, sedangkan lemparan sinkron
 * lolos begitu saja dan (dulu) merambat ke `.catch()` tiap fetcher sehingga
 * sitemap kehilangan SELURUH entri dinamisnya tanpa pesan error.
 */
async function withLocale(query?: Opts["query"]): Promise<Opts["query"]> {
  let loc: string | undefined;
  try {
    loc = await locale();
  } catch {
    loc = undefined; // di luar segmen [locale] → biarkan backend pakai default
  }

  return loc ? { ...query, locale: loc } : query;
}

async function api<T>(path: string, { revalidate = 300, query, tags }: Opts = {}): Promise<T> {
  const url = new URL(`${BASE}${path}`);
  const withLoc = await withLocale(query);
  if (withLoc) {
    for (const [k, v] of Object.entries(withLoc)) {
      if (v !== undefined && v !== "") url.searchParams.set(k, String(v));
    }
  }

  // revalidate:0 → jangan cache sama sekali (dipakai jalur pratinjau draft).
  const res = await fetch(
    url,
    revalidate === 0
      ? { cache: "no-store" }
      : { next: { revalidate, ...(tags && tags.length ? { tags } : {}) } },
  );
  if (!res.ok) throw new Error(`API ${res.status} on ${path}`);
  return res.json() as Promise<T>;
}

function emptyPage<T>(): Paginated<T> {
  return {
    data: [],
    links: { first: "", last: "", prev: null, next: null },
    meta: { current_page: 1, last_page: 1, per_page: 0, total: 0 },
  };
}

export const getSettings = async (): Promise<Settings> => {
  const s = await api<Settings>("/settings", { revalidate: 600, tags: ["settings"] }).catch(
    () => ({}) as Settings,
  );
  // Amankan field gambar: hanya pakai kalau berupa URL absolut.
  for (const key of ["logo", "favicon", "og_image"]) {
    if (s[key] && !/^https?:\/\//.test(s[key])) delete s[key];
  }
  return s;
};

/**
 * Berapa berita per halaman di arsip publik (wp: Settings → Reading →
 * "Blog pages show at most"). Diatur admin di Pengaturan Situs → Membaca;
 * backend juga memakai nilai ini sebagai default bila `per_page` tak dikirim.
 */
export const getPostsPerPage = async (): Promise<number> => {
  const s = await getSettings();
  const n = Number(s.posts_per_page);

  return Number.isFinite(n) && n >= 1 && n <= 100 ? Math.floor(n) : 12;
};

export const getSliders = () =>
  api<Slide[]>("/sliders", { revalidate: 600, tags: ["sliders"] });
export const getNavPages = () =>
  api<NavPage[]>("/pages", { revalidate: 600, tags: ["pages"] });
export const getMenu = (key: string) =>
  api<ApiMenu>(`/menus/${key}`, { revalidate: 600, tags: ["menu"] }).catch(
    () => ({ key, label: "", items: [] }) as ApiMenu,
  );
/** Isi satu zona widget (ala WordPress Appearance > Widgets) — kosong bila admin belum mengisi apa pun. */
export const getWidgetArea = (key: string) =>
  api<{ data: WidgetArea }>(`/widget-areas/${key}`, { revalidate: 600, tags: ["widget-areas"] })
    .then((r) => r.data)
    .catch(() => ({ key, blocks: [] }) as WidgetArea);
/**
 * Saat Draft Mode aktif (editor menekan "Pratinjau" di admin), ambil versi
 * draft lewat endpoint terpisah backend `/preview/...` yang butuh token
 * rahasia — cocok pola "separate draft endpoint" di dokumen Next. Di luar
 * Draft Mode, jalur normal (ISR) tetap dipakai tanpa overhead apa pun.
 */
async function previewContext(): Promise<{ enabled: boolean; token?: string }> {
  const { isEnabled } = await draftMode().catch(() => ({ isEnabled: false }));
  if (!isEnabled) return { enabled: false };
  const jar = await cookies();
  return { enabled: true, token: jar.get(PREVIEW_TOKEN_COOKIE)?.value };
}

export const getPage = async (slug: string): Promise<PageWithBlocks> => {
  const preview = await previewContext();
  if (preview.enabled && preview.token) {
    return api<{ data: PageWithBlocks }>(`/preview/pages/${slug}`, {
      revalidate: 0,
      query: { token: preview.token },
    }).then((r) => r.data);
  }
  return api<{ data: PageWithBlocks }>(`/pages/${slug}`, {
    revalidate: 600,
    tags: ["pages"],
  }).then((r) => r.data);
};

export const getPosts = (query?: Opts["query"]) =>
  api<Paginated<Post>>("/posts", { query, tags: ["posts"] }).catch(() => emptyPage<Post>());

export const getPost = async (slug: string): Promise<{ data: Post }> => {
  const preview = await previewContext();
  if (preview.enabled && preview.token) {
    return api<{ data: Post }>(`/preview/posts/${slug}`, {
      revalidate: 0,
      query: { token: preview.token },
    });
  }

  // Jalur ISR normal dulu — TANPA menyentuh cookies(), supaya post biasa
  // (mayoritas) tetap static/di-cache dan tidak memicu error Next "Page
  // changed from static to dynamic at runtime" (cookies() memaksa route
  // jadi dynamic, dan itu fatal kalau path-nya sudah ter-prerender statis).
  const result = await api<{ data: Post }>(`/posts/${slug}`, { revalidate: 120, tags: ["posts"] });

  // Hanya post terlindungi kata sandi & belum ter-unlock yang perlu cek
  // cookie unlock (wp: post_password) — kasus langka, jadi cookies() hanya
  // disentuh saat benar-benar perlu, bukan di setiap request.
  if (!result.data.protected || result.data.unlocked) {
    return result;
  }

  const jar = await cookies();
  const unlockToken = jar.get(`mtsn1_unlock_${slug}`)?.value;
  if (!unlockToken) {
    return result;
  }

  return api<{ data: Post }>(`/posts/${slug}`, {
    revalidate: 0,
    query: { unlock: unlockToken },
  });
};

/** Komentar publik (disetujui) untuk sebuah artikel, sudah berulir. */
export const getComments = (slug: string) =>
  api<CommentThread>(`/posts/${slug}/comments`, { revalidate: 120, tags: ["posts", "comments"] }).catch(
    () =>
      ({
        open: false,
        require_email: true,
        subscriptions_enabled: false,
        count: 0,
        data: [],
      }) as CommentThread,
  );

export const getTags = () =>
  api<TagWithCount[]>("/tags", { revalidate: 600, tags: ["tags", "posts"] }).catch(
    () => [] as TagWithCount[],
  );

/** Header arsip satu tag (/berita/tag/{slug}). */
export const getTag = (slug: string) =>
  api<TagDetail>(`/tags/${slug}`, { revalidate: 600, tags: ["tags", "posts"] });

export const getCategories = () =>
  api<CategoryWithCount[]>("/categories", { revalidate: 600, tags: ["posts"] }).catch(
    () => [] as CategoryWithCount[],
  );
export const getCategory = (slug: string) =>
  api<CategoryDetail>(`/categories/${slug}`, { revalidate: 600, tags: ["posts"] });

/** Daftar bulan arsip berita (wp: widget "Archives"). */
export const getArchives = () =>
  api<ArchiveMonth[]>("/archives", {
    revalidate: 600,
    tags: ["archives", "posts"],
  }).catch(() => [] as ArchiveMonth[]);

/** Header arsip satu bulan (/berita/arsip/{year}/{month}). */
export const getArchiveMonth = (year: number, month: number) =>
  api<ArchiveMonth>(`/archives/${year}/${month}`, {
    revalidate: 600,
    tags: ["archives", "posts"],
  });

/** Profil satu penulis (arsip /penulis/{slug}). */
export const getAuthor = (slug: string) =>
  api<AuthorProfile>(`/authors/${slug}`, { revalidate: 600, tags: ["authors", "posts"] });

/** Semua penulis publik (untuk generateStaticParams / halaman redaksi). */
export const getAuthors = () =>
  api<Pick<AuthorProfile, "name" | "slug" | "job_title" | "avatar" | "posts_count">[]>("/authors", {
    revalidate: 600,
    tags: ["authors", "posts"],
  }).catch(() => []);

/** Pencarian menyeluruh lintas-tipe konten. */
export const search = (q: string) =>
  api<SearchResult>("/search", {
    revalidate: 120,
    query: { q },
    tags: ["posts", "pages", "agendas", "documents", "extracurriculars", "teachers"],
  }).catch(() => ({ query: q, total: 0, groups: [] }) as SearchResult);

/**
 * Cek apakah sebuah path lama diarahkan (301) ke path baru — backend membuat
 * baris redirect otomatis tiap slug konten berubah. Dipakai not-found untuk
 * menyelamatkan tautan/bookmark lama, seperti core WordPress.
 */
export const resolveRedirect = (path: string) =>
  api<{ to: string; status: number }>("/resolve", {
    revalidate: 300,
    query: { path },
  }).catch(() => null);

export const getTeachers = (group?: string) =>
  api<Teacher[]>("/teachers", { query: { group }, tags: ["teachers"] }).catch(
    () => [] as Teacher[],
  );
export const getAgendas = (month?: string) =>
  api<Agenda[]>("/agendas", { query: { month }, tags: ["agendas"] }).catch(() => [] as Agenda[]);
export const getGalleries = () =>
  api<Paginated<Gallery>>("/galleries", { tags: ["galleries"] }).catch(() => emptyPage<Gallery>());
export const getExtracurriculars = () =>
  api<Extracurricular[]>("/extracurriculars", { tags: ["extracurriculars"] }).catch(
    () => [] as Extracurricular[],
  );
export const getAchievements = () =>
  api<Paginated<Achievement>>("/achievements", { tags: ["achievements"] }).catch(
    () => emptyPage<Achievement>(),
  );
export const getTestimonials = () =>
  api<Testimonial[]>("/testimonials", { tags: ["testimonials"] }).catch(
    () => [] as Testimonial[],
  );
export const getDocuments = (search?: string) =>
  api<LaravelPage<DocumentItem>>("/documents", { query: { search }, tags: ["documents"] }).catch(
    () => ({ data: [], current_page: 1, last_page: 1, total: 0 }) as LaravelPage<DocumentItem>,
  );
