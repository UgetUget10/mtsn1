<?php

namespace App\Support\Blocks;

use App\Models\Document;
use App\Models\Gallery;
use App\Support\Oembed\AutoEmbed;
use Illuminate\Support\Facades\Storage;

/**
 * Resolusi data mentah satu blok (path FileUpload lokal, id Document/Gallery)
 * menjadi bentuk siap-pakai frontend (URL absolut, ringkasan relasi) — DIPISAH
 * dari App\Http\Resources\PageResource supaya bisa dipakai ulang oleh
 * App\Http\Resources\WidgetAreaResource tanpa duplikasi logic. Satu-satunya
 * sumber kebenaran untuk "bagaimana satu tipe blok dirender ke API" — kalau
 * PageResource dan WidgetAreaResource sampai berbeda hasil untuk blok yang
 * sama, itu bug.
 */
class BlockDataResolver
{
    public static function resolve(string $type, array $data): array
    {
        return match ($type) {
            BlockTypes::HERO => [
                ...$data,
                'image' => self::fileUrl($data['image'] ?? null),
            ],
            BlockTypes::RICH_TEXT => [
                ...$data,
                'body' => AutoEmbed::html($data['body'] ?? null),
            ],
            BlockTypes::QUOTE => [
                ...$data,
                'text' => AutoEmbed::html($data['text'] ?? null),
            ],
            BlockTypes::CARD_GRID => [
                ...$data,
                'cards' => collect($data['cards'] ?? [])
                    ->map(fn ($card) => [
                        ...$card,
                        'image' => self::fileUrl($card['image'] ?? null),
                    ])
                    ->all(),
            ],
            BlockTypes::FILE_LIST => [
                ...$data,
                'files' => collect($data['files'] ?? [])
                    ->map(fn ($f) => self::documentSummary($f['document_id'] ?? null))
                    ->filter()
                    ->values()
                    ->all(),
            ],
            BlockTypes::GALLERY_BLOCK => [
                ...$data,
                'gallery' => self::gallerySummary($data['gallery_id'] ?? null),
            ],
            default => $data,
        };
    }

    public static function fileUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        $path = ltrim($path, '/');

        return Storage::disk('public')->exists($path) ? asset('storage/'.$path) : null;
    }

    private static function documentSummary(mixed $id): ?array
    {
        if (! $id) {
            return null;
        }

        $document = Document::with('category')->find($id);
        if (! $document) {
            return null;
        }

        return [
            'title' => $document->title,
            'category' => $document->category?->name,
            'url' => $document->getFirstMediaUrl('file') ?: self::fileUrl($document->file),
            'download_url' => route('documents.download', $document),
        ];
    }

    private static function gallerySummary(mixed $id): ?array
    {
        if (! $id) {
            return null;
        }

        $gallery = Gallery::with('items')->find($id);
        if (! $gallery) {
            return null;
        }

        return [
            'title' => $gallery->title,
            'slug' => $gallery->slug,
            'cover' => $gallery->getFirstMediaUrl('cover', 'card') ?: self::fileUrl($gallery->cover),
            'items' => $gallery->items->map(fn ($i) => [
                'type' => $i->type,
                'url' => $i->type === 'video'
                    ? $i->video_url
                    : ($i->getFirstMediaUrl('image', 'card') ?: self::fileUrl($i->path)),
                'caption' => $i->caption,
            ]),
        ];
    }
}
