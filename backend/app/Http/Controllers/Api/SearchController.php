<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agenda;
use App\Models\Document;
use App\Models\Extracurricular;
use App\Models\Page;
use App\Models\Post;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Pencarian menyeluruh ala WordPress — satu kueri, hasil dari semua "post type":
 * berita, halaman profil, agenda, dokumen, ekstrakurikuler, guru.
 *
 * Kolom translatable (title/name) disimpan sebagai JSON, jadi `LIKE` pada
 * kolomnya tetap cocok untuk pencarian sederhana lintas-bahasa. Tidak butuh
 * indeks fulltext untuk skala situs sekolah.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request): array
    {
        $q = trim((string) $request->query('q', ''));

        if (Str::length($q) < 2) {
            return ['query' => $q, 'total' => 0, 'groups' => []];
        }

        $like = '%'.$q.'%';

        $groups = array_filter([
            $this->group('Berita', '/berita', Post::query()
                ->published()
                ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('excerpt', 'like', $like))
                ->latest('published_at')
                ->limit(8)
                ->get(['title', 'slug', 'excerpt', 'body', 'published_at'])
                ->map(fn (Post $p) => [
                    'title' => $p->title,
                    'excerpt' => $p->displayExcerpt(30),
                    'href' => "/berita/{$p->slug}",
                    'meta' => optional($p->published_at)->toDateString(),
                ])),

            $this->group('Halaman', '/profil', Page::query()
                ->where('is_published', true)
                ->where('slug', '!=', Page::HOMEPAGE_SLUG)
                ->where(fn ($w) => $w->where('title', 'like', $like)
                    ->orWhere('body', 'like', $like)
                    ->orWhere('meta_description', 'like', $like))
                ->orderBy('order')
                ->limit(6)
                ->get(['title', 'slug', 'meta_description'])
                ->map(fn (Page $p) => [
                    'title' => $p->title,
                    'excerpt' => $p->meta_description,
                    'href' => "/profil/{$p->slug}",
                    'meta' => null,
                ])),

            $this->group('Agenda', '/agenda', Agenda::query()
                ->where(fn ($w) => $w->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('location', 'like', $like))
                ->orderByDesc('start_at')
                ->limit(6)
                ->get(['title', 'slug', 'description', 'start_at', 'location'])
                ->map(fn (Agenda $a) => [
                    'title' => $a->title,
                    'excerpt' => $a->description ? Str::of($a->description)->stripTags()->limit(120) : null,
                    'href' => '/agenda',
                    'meta' => optional($a->start_at)->toDateString().($a->location ? " · {$a->location}" : ''),
                ])),

            $this->group('Dokumen', '/dokumen', Document::query()
                ->where('title', 'like', $like)
                ->latest()
                ->limit(6)
                ->get(['id', 'title'])
                ->map(fn (Document $d) => [
                    'title' => $d->title,
                    'excerpt' => null,
                    'href' => '/dokumen',
                    'meta' => 'Unduhan',
                ])),

            $this->group('Ekstrakurikuler', '/ekstrakurikuler', Extracurricular::query()
                ->where(fn ($w) => $w->where('name', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhere('coach', 'like', $like))
                ->orderBy('name')
                ->limit(6)
                ->get(['name', 'slug', 'description'])
                ->map(fn (Extracurricular $e) => [
                    'title' => $e->name,
                    'excerpt' => $e->description ? Str::of($e->description)->stripTags()->limit(120) : null,
                    'href' => '/ekstrakurikuler',
                    'meta' => null,
                ])),

            $this->group('Guru & Tendik', '/guru', Teacher::query()
                ->where('is_active', true)
                ->where(fn ($w) => $w->where('name', 'like', $like)
                    ->orWhere('subject', 'like', $like)
                    ->orWhere('position', 'like', $like))
                ->orderBy('name')
                ->limit(6)
                ->get(['name', 'position', 'subject'])
                ->map(fn (Teacher $t) => [
                    'title' => $t->name,
                    'excerpt' => $t->subject,
                    'href' => '/guru',
                    'meta' => $t->position,
                ])),
        ]);

        return [
            'query' => $q,
            'total' => collect($groups)->sum(fn ($g) => count($g['items'])),
            'groups' => array_values($groups),
        ];
    }

    /** @param Collection<int, array<string, mixed>> $items */
    private function group(string $label, string $href, $items): ?array
    {
        return $items->isEmpty() ? null : [
            'label' => $label,
            'href' => $href,
            'items' => $items->values()->all(),
        ];
    }
}
