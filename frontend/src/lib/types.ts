export type Paginated<T> = {
  data: T[];
  links: { first: string; last: string; prev: string | null; next: string | null };
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
};

export type Category = { name: string; slug: string };
export type Tag = { name: string; slug: string };

/** Penulis artikel. `slug` hanya terisi bila profil publik aktif → tautan arsip. */
export type Author = {
  name: string;
  slug: string | null;
  avatar: string | null;
  job_title: string | null;
};

/** Kembalian /authors/{slug} — halaman arsip penulis. */
export type AuthorProfile = {
  name: string;
  slug: string;
  job_title: string | null;
  avatar: string | null;
  posts_count: number;
  bio: string | null;
  social: Record<string, string>;
};

/** Kembalian /categories — kategori dengan deskripsi & jumlah berita publik. */
export type CategoryWithCount = Category & {
  description: string | null;
  posts_count: number;
  /** Slug kategori induk, atau null untuk kategori tingkat atas. */
  parent: string | null;
};

/** Kembalian /categories/{slug} — header arsip kategori (hierarkis). */
export type CategoryDetail = Category & {
  description: string | null;
  parent: string | null;
  /** Leluhur dari akar → induk langsung (untuk breadcrumb). */
  ancestors: Category[];
  /** Sub-kategori langsung. */
  children: Category[];
};

/** Blok SEO ala Yoast — dikirim backend di setiap Post & PageWithBlocks. */
export type Seo = {
  title: string | null;
  description: string | null;
  canonical: string | null;
  noindex: boolean;
  og_image: string | null;
};

/** Rujukan minimal ke artikel lain (navigasi bersebelahan, "baca juga"). */
export type PostRef = { title: string; slug: string };

export type Post = {
  id: number;
  title: string;
  slug: string;
  excerpt: string | null;
  body?: string;
  cover: string | null;
  cover_alt?: string | null;
  /** Keterangan gambar sampul (wp: Caption). */
  cover_caption?: string | null;
  /** Kredit foto sampul (wp: photo credit), dirender berprefiks "Foto: ". */
  cover_credit?: string | null;
  is_featured: boolean;
  views: number;
  published_at: string | null;
  /**
   * Tanggal sunting terakhir (wp: post_modified). Backend hanya mengirimnya
   * bila artikel memang disunting setelah terbit; null bila belum pernah.
   */
  updated_at?: string | null;
  category?: Category;
  tags?: Tag[];
  seo?: Seo;
  author?: Author | null;
  comments_count?: number;
  /** Hanya diisi pada endpoint detail — apakah form komentar ditampilkan. */
  comments_open?: boolean;
  /**
   * Navigasi artikel bersebelahan (wp: previous_post_link / next_post_link).
   * Hanya diisi pada endpoint detail. `previous` = lebih tua, `next` = lebih baru.
   */
  adjacent?: {
    previous: PostRef | null;
    next: PostRef | null;
  };
  /** WordPress: post_password — isi terkunci sampai kata sandi benar. */
  protected?: boolean;
  /** true bila tak terproteksi, atau sudah dibuka dengan token. */
  unlocked?: boolean;
  /**
   * "Custom Fields" ala WordPress — pasangan kunci/nilai bebas yang diisi
   * editor di panel admin (Kolom Kustom). Hanya diisi pada endpoint detail.
   * Kosong bila editor tidak mengisi apa pun.
   */
  custom_fields?: Record<string, string>;
};

/** Satu komentar publik (sudah disetujui), berulir lewat `replies`. */
export type Comment = {
  id: number;
  author_name: string;
  author_url: string | null;
  is_staff: boolean;
  body: string;
  created_at: string | null;
  replies: Comment[];
};

export type CommentThread = {
  open: boolean;
  require_email: boolean;
  /** wp: tawarkan checkbox "Beri tahu saya bila ada balasan". */
  subscriptions_enabled: boolean;
  count: number;
  data: Comment[];
};

/** Kembalian /api/v1/tags — daftar tag dengan jumlah berita publik. */
export type TagWithCount = Tag & { posts_count: number; description: string | null };

/** Kembalian /tags/{slug} — header arsip tag. */
export type TagDetail = Tag & { description: string | null };

/**
 * Satu bulan arsip berita (wp: wp_get_archives / date archive `/2026/09/`).
 * `label` sudah diterjemahkan backend ("September 2026").
 */
export type ArchiveMonth = {
  year: number;
  month: number;
  label: string;
  posts_count: number;
};

export type NavPage = {
  title: string;
  slug: string;
  order: number;
  /** Slug halaman induk, atau null untuk halaman tingkat atas. */
  parent: string | null;
};

export type Teacher = {
  name: string;
  nip: string | null;
  position: string | null;
  subject: string | null;
  group: string;
  photo: string | null;
  photo_alt?: string | null;
};

export type Testimonial = {
  quote: string;
  name: string;
  role: string | null;
};

export type Agenda = {
  title: string;
  slug: string;
  description: string | null;
  start_at: string;
  end_at: string | null;
  location: string | null;
};

export type GalleryItem = {
  type: "image" | "video";
  url: string | null;
  caption: string | null;
  alt?: string | null;
};
export type Gallery = {
  title: string;
  slug: string;
  description: string | null;
  taken_on: string | null;
  cover: string | null;
  cover_alt?: string | null;
  items: GalleryItem[];
};

export type Extracurricular = {
  name: string;
  slug: string;
  coach: string | null;
  schedule: string | null;
  description: string | null;
  image: string | null;
  image_alt?: string | null;
};

export type Achievement = {
  title: string;
  student_name: string | null;
  level: string | null;
  year: number | null;
  description: string | null;
  image: string | null;
  image_alt?: string | null;
};

export type LaravelPage<T> = {
  data: T[];
  current_page: number;
  last_page: number;
  total: number;
};

export type DocumentItem = {
  id: number;
  title: string;
  category: string | null;
  size: string | null;
  /** URL berkas asli — untuk pratinjau iframe. */
  url: string;
  /** Tautan unduh backend yang menghitung `downloads`, lalu redirect ke `url`. */
  download_url: string;
  downloads: number;
};

export type Slide = {
  title: string | null;
  subtitle: string | null;
  image: string | null;
  image_alt?: string | null;
  link: string | null;
};

export type Settings = Record<string, string>;

/* ---------- Pencarian menyeluruh (GET /search) ---------- */

export type SearchHit = {
  title: string;
  excerpt: string | null;
  href: string;
  meta: string | null;
};

export type SearchGroup = {
  label: string;
  href: string;
  items: SearchHit[];
};

export type SearchResult = {
  query: string;
  total: number;
  groups: SearchGroup[];
};

/* ---------- Blok konten (Page Builder backend) ---------- */

export type HeroBlockData = {
  eyebrow: string | null;
  title: string;
  subtitle: string | null;
  image: string | null;
  cta_label: string | null;
  cta_href: string | null;
};

export type RichTextBlockData = { heading: string | null; body: string };

export type CardGridBlockData = {
  heading: string | null;
  columns: number;
  cards: {
    title: string;
    description: string | null;
    icon: string | null;
    image: string | null;
    href: string | null;
  }[];
};

export type AccordionBlockData = {
  heading: string | null;
  items: { question: string; answer: string }[];
};

export type CtaBlockData = {
  heading: string;
  text: string | null;
  button_label: string;
  button_href: string;
  style: "primary" | "outline";
};

export type FileListBlockData = {
  heading: string | null;
  files: {
    title: string;
    category: string | null;
    url: string | null;
    download_url?: string | null;
  }[];
};

export type GalleryBlockData = {
  heading: string | null;
  gallery: Gallery | null;
};

export type StatsBlockData = {
  items: { label: string; value: string; icon: string | null }[];
};

export type HubGridBlockData = {
  numbered: boolean;
  items: { title: string; desc: string | null; href: string; icon: string | null }[];
};

export type TableBlockData = {
  heading: string | null;
  columns: { label: string }[];
  rows: { cells: { value: string }[] }[];
};

export type StepsBlockData = {
  heading: string | null;
  items: { title: string; description: string | null }[];
};

export type QuoteBlockData = { text: string; attribution: string | null };

export type LinkCardsBlockData = {
  heading: string | null;
  items: { title: string; description: string | null; href: string; external: boolean }[];
};

export type ChecklistBlockData = { heading: string | null; items: { text: string }[] };

export type IconListBlockData = { heading: string | null; items: { text: string }[] };

export type TimelineBlockData = {
  heading: string | null;
  items: { label: string; title: string | null; description: string | null }[];
};

export type Block =
  | { type: "hero"; data: HeroBlockData }
  | { type: "rich_text"; data: RichTextBlockData }
  | { type: "card_grid"; data: CardGridBlockData }
  | { type: "accordion"; data: AccordionBlockData }
  | { type: "cta"; data: CtaBlockData }
  | { type: "file_list"; data: FileListBlockData }
  | { type: "gallery_block"; data: GalleryBlockData }
  | { type: "stats"; data: StatsBlockData }
  | { type: "hub_grid"; data: HubGridBlockData }
  | { type: "table"; data: TableBlockData }
  | { type: "steps"; data: StepsBlockData }
  | { type: "quote"; data: QuoteBlockData }
  | { type: "link_cards"; data: LinkCardsBlockData }
  | { type: "checklist"; data: ChecklistBlockData }
  | { type: "icon_list"; data: IconListBlockData }
  | { type: "timeline"; data: TimelineBlockData };

/* ---------- Tree kanvas visual (Page Builder Phase 1, preview-only) ---------- */

/** Nilai gaya per breakpoint — kosakata tertutup, divalidasi backend (StyleResolver, Phase 2). */
export type TreeNodeStyle = Record<string, Record<string, unknown>>;

export type TreeWidgetNode = { id: string; type: Block["type"]; data: unknown; style?: TreeNodeStyle };
export type TreeColumnNode = { id: string; type: "column"; style?: TreeNodeStyle; children: TreeWidgetNode[] };
export type TreeSectionNode = { id: string; type: "section"; style?: TreeNodeStyle; children: TreeColumnNode[] };

/** Kembalian PageResource::resolvedTree() — hanya terisi di jalur pratinjau kanvas. */
export type PageTree = { schema: number; tree: TreeSectionNode[] };

/* ---------- Widget Area (ala WordPress Appearance > Widgets) ---------- */

/** Isi satu zona widget — daftar blok yang dirender lewat BlockRenderer yang sama dengan halaman biasa. */
export type WidgetArea = {
  key: string;
  blocks: Block[];
};

/* ---------- Menu (Menu Builder backend) ---------- */

export type ApiMenuItem = {
  label: string;
  // custom_url = URL harfiah; page/post/category/tag = referensi konten,
  // href dibangun backend dari slug terbaru; section = pengelompok tanpa tautan.
  type: "custom_url" | "page" | "post" | "category" | "tag" | "section";
  href: string | null;
  icon: string | null;
  feature: { title: string; text: string | null; cta: string | null } | null;
  children: ApiMenuItem[];
};

export type ApiMenu = { key: string; label: string; items: ApiMenuItem[] };

export type PageRef = { title: string; slug: string };

/** Template tata letak ala WordPress (Page Attributes → Template). */
export type PageTemplate = "default" | "full-width" | "sidebar-nav" | "landing";

export type PageWithBlocks = {
  title: string;
  slug: string;
  /** Wp: Page Attributes → Template. Backend selalu mengirim nilai valid. */
  template: PageTemplate;
  meta_description: string | null;
  seo?: Seo;
  updated_at: string;
  blocks: Block[];
  /** Hanya terisi di jalur pratinjau kanvas visual (lihat PreviewController). */
  tree?: PageTree;
  /** Hierarki halaman ala WordPress (Page Attributes → Parent). */
  parent: string | null;
  ancestors: PageRef[];
  children: PageRef[];
};
