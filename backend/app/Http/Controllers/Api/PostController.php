<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PostResource;
use App\Models\Category;
use App\Models\Post;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $posts = Post::query()
            ->published()
            ->with(['category', 'tags', 'author' => fn ($q) => $q->withCount([
                'posts as published_posts_count' => fn ($p) => $p->published(),
            ])])
            ->withCount(['comments as comments_count' => fn ($q) => $q->where('status', 'approved')])
            ->when($request->category, function ($q, $slug) {
                // Sertakan berita dari sub-kategori juga (wp: "include children").
                $slugs = $this->categorySlugsWithDescendants($slug);

                $q->where(fn ($w) => $w
                    ->whereHas('category', fn ($c) => $c->whereIn('slug', $slugs))
                    ->orWhereHas('categories', fn ($c) => $c->whereIn('slug', $slugs)));
            })
            ->when($request->tag, fn ($q, $slug) => $q->whereRelation('tags', 'slug', $slug))
            ->when($request->author, fn ($q, $slug) => $q->whereRelation('author', 'slug', $slug))
            ->when($request->featured, fn ($q) => $q->where('is_featured', true))
            // Arsip tanggal ala WordPress (/2026/09/). `month` tanpa `year`
            // diabaikan — WP juga menuntut tahun untuk arsip bulanan.
            ->when($request->filled('year'), fn ($q) => $q
                ->whereYear('published_at', (int) $request->input('year'))
                ->when($request->filled('month'), fn ($w) => $w
                    ->whereMonth('published_at', (int) $request->input('month'))))
            ->when($request->search, fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('title', 'like', "%{$s}%")
                ->orWhere('excerpt', 'like', "%{$s}%")));

        // Sticky posts (wp: is_sticky) mengapung ke atas HANYA pada arus utama
        // tanpa filter — bukan di arsip kategori/tag/penulis/pencarian, persis
        // seperti WordPress.
        $isFilteredArchive = $request->filled('category')
            || $request->filled('tag')
            || $request->filled('author')
            || $request->filled('search')
            || $request->filled('year')
            || $request->boolean('featured');

        if (! $isFilteredArchive) {
            $posts->orderByDesc('is_featured');
        }

        $posts = $posts
            ->latest('published_at')
            ->paginate($this->perPage($request));

        return PostResource::collection($posts);
    }

    /**
     * Jumlah item per halaman. Prioritas: `?per_page=` dari pemanggil →
     * setting `posts_per_page` (wp: Settings → Reading) → 12.
     * Dibatasi 1..100 supaya query publik tak bisa dipaksa mengambil ribuan
     * baris. Batas atas 100 karena `frontend/src/app/sitemap.ts` memang
     * meminta `per_page: 100` — jangan turunkan tanpa menyesuaikan sitemap.
     */
    private function perPage(Request $request): int
    {
        $default = (int) (Setting::get('posts_per_page') ?: 12);
        $value = (int) $request->integer('per_page', $default ?: 12);

        return max(1, min(100, $value));
    }

    /**
     * Slug kategori yang diminta + seluruh keturunannya (rekursif, dangkal
     * karena pohon kategori sekolah tak dalam).
     *
     * @return array<int, string>
     */
    private function categorySlugsWithDescendants(string $slug): array
    {
        $root = Category::where('slug', $slug)->first();
        if (! $root) {
            return [$slug];
        }

        $slugs = [$root->slug];
        $queue = [$root->id];

        while ($queue) {
            $children = Category::whereIn('parent_id', $queue)->get(['id', 'slug']);
            $queue = [];
            foreach ($children as $child) {
                $slugs[] = $child->slug;
                $queue[] = $child->id;
            }
        }

        return array_values(array_unique($slugs));
    }

    public function show(Request $request, Post $post)
    {
        abort_unless($post->isViewablePublicly(), 404);

        // Post terlindungi kata sandi: cek token unlock (dikirim frontend sebagai
        // ?unlock= atau header X-Post-Unlock). Token = Crypt payload {post, exp}.
        if ($post->isPasswordProtected()) {
            $request->attributes->set('post_unlocked', $this->unlockTokenValid($request, $post));
        }

        // Naikkan penghitung tampilan TANPA memicu event model. increment()
        // biasa memicu `saved` → TriggersFrontendRevalidation akan mem-ping
        // frontend Next.js pada SETIAP pembacaan artikel (purge tag `posts` +
        // path artikel per pengunjung). Update langsung di query builder
        // menghindari itu; angka `views` bukan data kritis untuk ISR dan akan
        // tersegarkan pada revalidate berikutnya (120 dtk).
        Post::withoutEvents(fn () => $post->newQuery()
            ->whereKey($post->getKey())
            ->increment('views'));
        $post->views++; // cerminkan di respons tanpa query ulang

        $post->load(['category', 'categories', 'tags', 'author' => fn ($q) => $q->withCount([
            'posts as published_posts_count' => fn ($p) => $p->published(),
        ])])->loadCount(['comments as comments_count' => fn ($q) => $q->where('status', 'approved')]);

        return new PostResource($post);
    }

    /**
     * Verifikasi kata sandi post (wp: post_password check). Balas token unlock
     * bertanda-tangan yang dipakai frontend untuk membuka isi di request show
     * berikutnya. Stateless supaya cocok dengan ISR/cache Next.js.
     */
    public function unlock(Request $request, Post $post)
    {
        abort_unless($post->isViewablePublicly(), 404);
        abort_unless($post->isPasswordProtected(), 422, 'Artikel ini tidak dilindungi kata sandi.');

        $data = $request->validate(['password' => ['required', 'string', 'max:255']]);

        if (! $post->checkPassword($data['password'])) {
            return response()->json(['message' => 'Kata sandi salah.'], 422);
        }

        $token = Crypt::encryptString(json_encode([
            'post' => $post->id,
            'exp' => now()->addDays(7)->timestamp,
        ]));

        return response()->json([
            'unlocked' => true,
            'token' => $token,
            'expires_in' => 7 * 24 * 3600,
        ]);
    }

    private function unlockTokenValid(Request $request, Post $post): bool
    {
        $token = $request->query('unlock') ?: $request->header('X-Post-Unlock');
        if (! $token) {
            return false;
        }

        try {
            $payload = json_decode(Crypt::decryptString($token), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return false;
        }

        return ($payload['post'] ?? null) === $post->id
            && ($payload['exp'] ?? 0) > now()->timestamp;
    }
}
