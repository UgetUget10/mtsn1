<?php

namespace App\Filament\Support;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use League\Csv\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use SimpleXMLElement;

/**
 * Import berita dari CSV, XLSX, atau XML "WordPress Export" (Tools → Export
 * di WP) — meniru fitur import plugin populer (mis. WP All Import) tapi
 * berjalan SYNCHRONOUS, bukan lewat queue: server ini
 * (QUEUE_CONNECTION=database) tidak punya worker berjalan (lihat
 * ecosystem.config.js), jadi ImportAction bawaan Filament — yang
 * mendispatch job ke queue — akan macet diam-diam tanpa progress sama
 * sekali. Diproses langsung saat admin klik "Jalankan Import"; untuk file
 * sangat besar ini lebih lambat tapi hasilnya pasti terlihat, cocok untuk
 * kapasitas madrasah (puluhan–ratusan berita, bukan jutaan baris).
 *
 * Kolom CSV/XLSX yang dikenali (header baris pertama, tidak case-sensitive):
 *   title* | slug | excerpt | body | category | tags (pisah koma)
 *   | status (draft/pending/scheduled/published) | published_at
 *   | is_featured (1/true/yes) | cover_url (diunduh ke media library)
 * (XLSX memakai openspout/openspout — sudah ada di composer.json lewat
 * dependency Filament, tidak perlu paket tambahan seperti maatwebsite/excel.
 * Hanya sheet PERTAMA yang dibaca; sel bertipe tanggal/angka dikonversi ke
 * string sebelum diproses supaya jalur parsingnya sama dengan CSV.)
 *
 * XML: node <item> standar WXR (WordPress eXtended RSS) — <title>,
 * <wp:post_name>, <content:encoded>, <excerpt:encoded>, <category>,
 * <wp:status>, <wp:post_date>, <wp:postmeta> (thumbnail via _thumbnail_id
 * TIDAK didukung — WXR memisahkan attachment sbg <item> lain; dipetakan
 * lewat <wp:attachment_url> bila ada di item yang sama, kalau tidak
 * cover dilewati agar import tidak gagal karena satu berkas hilang).
 */
class PostImporter
{
    public const DUPLICATE_SKIP = 'skip';

    public const DUPLICATE_UPDATE = 'update';

    public const DUPLICATE_CREATE_NEW = 'create_new';

    /**
     * @return array{created: int, updated: int, skipped: int, failed: int, errors: array<int, string>}
     */
    public static function run(
        string $path,
        string $format,
        int $authorId,
        string $defaultStatus,
        string $duplicateStrategy,
        bool $downloadCovers,
    ): array {
        $rows = match ($format) {
            'xml' => self::parseWxr($path),
            'xlsx' => self::parseXlsx($path),
            default => self::parseCsv($path),
        };

        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0, 'errors' => []];

        foreach ($rows as $i => $row) {
            try {
                self::importRow($row, $authorId, $defaultStatus, $duplicateStrategy, $downloadCovers, $result);
            } catch (\Throwable $e) {
                $result['failed']++;
                $label = $row['title'] ?? "baris #{$i}";
                $result['errors'][] = "\"{$label}\": {$e->getMessage()}";
            }
        }

        return $result;
    }

    /** @return array<int, array<string, mixed>> */
    private static function parseCsv(string $path): array
    {
        $csv = CsvReader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);

        $rows = [];
        foreach ($csv->getRecords() as $record) {
            $mapped = self::mapNormalizedRow(self::normalizeKeyedRow($record));
            if ($mapped) {
                $rows[] = $mapped;
            }
        }

        return $rows;
    }

    /**
     * XLSX: baris pertama sheet PERTAMA diperlakukan sebagai header (sama
     * seperti CSV), baris sisanya dipetakan berdasarkan posisi kolom.
     * openspout membaca dalam mode streaming (baris demi baris, bukan
     * seluruh file ke memori) — aman untuk file besar.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function parseXlsx(string $path): array
    {
        $reader = new XlsxReader();
        $reader->open($path);

        $rows = [];
        $header = null;

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rawCells = $row->toArray();

                    if ($header === null) {
                        $header = array_map(fn ($h) => strtolower(trim((string) $h)), $rawCells);

                        continue;
                    }

                    // Kolom tanggal butuh perlakuan KHUSUS per posisi (bukan
                    // generik per-sel): saat Excel/openspout menulis sebuah
                    // tanggal lalu file dibaca ulang, sel itu sering kembali
                    // sebagai NUMERIC (serial number, mis. 46096.375 = hari
                    // sejak 1899-12-30) — bukan objek DateTime. Angka murni di
                    // kolom LAIN (mis. is_featured=1) TIDAK boleh ikut
                    // dikonversi, jadi deteksi ini dibatasi ke kolom
                    // published_at/tanggal saja.
                    $dateColumnIndexes = array_keys($header, 'published_at') + array_keys($header, 'tanggal');

                    $cells = [];
                    foreach ($rawCells as $i => $v) {
                        if ($v instanceof \DateTimeInterface) {
                            $cells[$i] = $v->format('Y-m-d H:i:s');
                        } elseif (in_array($i, $dateColumnIndexes, true) && is_numeric($v)) {
                            $cells[$i] = self::excelSerialToDateString((float) $v);
                        } else {
                            $cells[$i] = trim((string) $v);
                        }
                    }

                    if (! array_filter($cells, fn ($v) => $v !== '')) {
                        continue; // baris kosong di tengah/akhir sheet
                    }

                    $keyed = [];
                    foreach ($header as $i => $key) {
                        if ($key !== '') {
                            $keyed[$key] = $cells[$i] ?? '';
                        }
                    }

                    $mapped = self::mapNormalizedRow($keyed);
                    if ($mapped) {
                        $rows[] = $mapped;
                    }
                }

                break; // hanya sheet pertama — cukup untuk kasus "satu sheet berisi daftar berita"
            }
        } finally {
            $reader->close();
        }

        return $rows;
    }

    /** Header (mis. "Judul Berita") -> kunci ternormalisasi ("judul berita"), nilai ditrim. */
    private static function normalizeKeyedRow(iterable $record): array
    {
        $normalized = [];
        foreach ($record as $key => $value) {
            $normalized[strtolower(trim((string) $key))] = trim((string) $value);
        }

        return $normalized;
    }

    /** Kunci ternormalisasi (header sudah lowercase+trim) -> struktur baris yang dipahami importRow(). */
    private static function mapNormalizedRow(array $normalized): ?array
    {
        $title = $normalized['title'] ?? $normalized['judul'] ?? null;
        if (! $title) {
            return null; // baris tanpa judul dilewati diam-diam (mis. baris kosong di akhir file)
        }

        return [
            'title' => $title,
            'slug' => $normalized['slug'] ?? null,
            'excerpt' => $normalized['excerpt'] ?? $normalized['ringkasan'] ?? null,
            'body' => $normalized['body'] ?? $normalized['isi'] ?? $normalized['content'] ?? '',
            'category' => $normalized['category'] ?? $normalized['kategori'] ?? null,
            'tags' => isset($normalized['tags'])
                ? array_values(array_filter(array_map('trim', explode(',', $normalized['tags']))))
                : [],
            'status' => $normalized['status'] ?? null,
            'published_at' => $normalized['published_at'] ?? $normalized['tanggal'] ?? null,
            'is_featured' => self::toBool($normalized['is_featured'] ?? $normalized['featured'] ?? null),
            'cover_url' => $normalized['cover_url'] ?? $normalized['cover'] ?? $normalized['image'] ?? null,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private static function parseWxr(string $path): array
    {
        $xmlString = file_get_contents($path);
        if ($xmlString === false) {
            throw new \RuntimeException('Berkas XML tidak dapat dibaca.');
        }

        // WXR mendaftarkan namespace wp:/content:/excerpt: — perlu didaftarkan
        // manual di SimpleXML supaya children() bisa mengaksesnya per-namespace.
        $prev = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlString);
        libxml_use_internal_errors($prev);

        if ($xml === false) {
            throw new \RuntimeException('Format XML tidak valid atau bukan WordPress Export (WXR).');
        }

        $namespaces = $xml->getNamespaces(true);
        $wp = $namespaces['wp'] ?? 'http://wordpress.org/export/1.2/';
        $content = $namespaces['content'] ?? 'http://purl.org/rss/1.0/modules/content/';
        $excerptNs = $namespaces['excerpt'] ?? 'http://wordpress.org/export/1.2/excerpt/';

        $items = $xml->channel->item ?? [];
        $rows = [];

        foreach ($items as $item) {
            $wpChildren = $item->children($wp);
            $postType = (string) ($wpChildren->post_type ?? 'post');

            // WXR juga menyertakan <item> untuk halaman & attachment (gambar
            // yang diunggah) — hanya proses yang bertipe "post" (berita).
            if ($postType !== 'post') {
                continue;
            }

            $status = (string) ($wpChildren->status ?? 'draft');
            // WP status "future" (dijadwalkan) & "publish" dipetakan ke
            // enum status lokal; selain itu jatuh ke draft (aman: tidak
            // pernah otomatis tayang untuk status WP yang tak dikenal).
            $mappedStatus = match ($status) {
                'publish' => Post::STATUS_PUBLISHED,
                'future' => Post::STATUS_SCHEDULED,
                'pending' => Post::STATUS_PENDING,
                default => Post::STATUS_DRAFT,
            };

            $categories = [];
            $tags = [];
            foreach ($item->category ?? [] as $cat) {
                $domain = (string) ($cat['domain'] ?? '');
                $name = (string) $cat;
                if ($domain === 'post_tag') {
                    $tags[] = $name;
                } elseif ($domain === 'category' && $name && strtolower($name) !== 'uncategorized') {
                    $categories[] = $name;
                }
            }

            $coverUrl = null;
            foreach ($wpChildren->postmeta ?? [] as $meta) {
                if ((string) $meta->meta_key === '_wp_attached_file_url') {
                    $coverUrl = (string) $meta->meta_value;
                }
            }

            $rows[] = [
                'title' => (string) $item->title,
                'slug' => (string) ($wpChildren->post_name ?? '') ?: null,
                'excerpt' => trim((string) $item->children($excerptNs)->encoded ?? ''),
                'body' => (string) $item->children($content)->encoded,
                'category' => $categories[0] ?? null,
                'tags' => $tags,
                'status' => $mappedStatus,
                'published_at' => (string) ($wpChildren->post_date ?? '') ?: null,
                'is_featured' => false,
                'cover_url' => $coverUrl,
            ];
        }

        return $rows;
    }

    private static function toBool(?string $value): bool
    {
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'ya'], true);
    }

    /**
     * Konversi serial number tanggal Excel (hari sejak 1899-12-30, dengan
     * pecahan = fraksi hari) ke string "Y-m-d H:i:s" yang bisa dibaca
     * Carbon::parse() di importRow(). Epoch 1899-12-30 (bukan 1900-01-01)
     * sengaja mengikuti konvensi Excel, yang menghitung 1900 sebagai tahun
     * kabisat (bug historis Lotus 1-2-3 yang dipertahankan Excel demi
     * kompatibilitas) — pakai 1900-01-01 akan meleset 1 hari untuk tanggal
     * setelah 28 Feb 1900.
     */
    private static function excelSerialToDateString(float $serial): string
    {
        $days = (int) floor($serial);
        $seconds = (int) round(($serial - $days) * 86400);

        return Carbon::create(1899, 12, 30, 0, 0, 0)
            ->addDays($days)
            ->addSeconds($seconds)
            ->format('Y-m-d H:i:s');
    }

    /**
     * @param  array{created: int, updated: int, skipped: int, failed: int, errors: array<int, string>}  &$result
     */
    private static function importRow(
        array $row,
        int $authorId,
        string $defaultStatus,
        string $duplicateStrategy,
        bool $downloadCovers,
        array &$result,
    ): void {
        $slug = $row['slug'] ? Str::slug($row['slug']) : Str::slug($row['title']);
        $existing = $slug ? Post::withTrashed()->where('slug', $slug)->first() : null;

        if ($existing && $duplicateStrategy === self::DUPLICATE_SKIP) {
            $result['skipped']++;

            return;
        }

        $isUpdate = $existing && $duplicateStrategy === self::DUPLICATE_UPDATE;
        $post = $isUpdate ? $existing : new Post();

        if (! $isUpdate) {
            $post->user_id = $authorId;
            // Existing + create_new → biarkan HasSlug menghasilkan slug baru
            // (mis. "judul-sama-2") daripada bentrok dengan yang lama.
            if (! ($existing && $duplicateStrategy === self::DUPLICATE_CREATE_NEW)) {
                $post->slug = $slug;
            }
        }

        $post->setTranslation('title', 'id', $row['title']);
        $post->setTranslation('excerpt', 'id', (string) ($row['excerpt'] ?? ''));
        $post->setTranslation('body', 'id', (string) ($row['body'] ?? ''));
        $post->status = self::validStatus($row['status']) ?? $defaultStatus;
        $post->is_featured = (bool) $row['is_featured'];

        if ($row['published_at']) {
            try {
                $post->published_at = Carbon::parse($row['published_at']);
            } catch (\Throwable) {
                // Tanggal tak terbaca — biarkan null, jangan gagalkan seluruh baris.
            }
        }
        if (! $post->published_at && $post->status === Post::STATUS_PUBLISHED) {
            $post->published_at = now();
        }

        if ($row['category']) {
            $post->category_id = Category::firstOrCreate(
                ['slug' => Str::slug($row['category'])],
                ['name' => $row['category']],
            )->id;
        }

        $post->save();

        if (! empty($row['tags'])) {
            $tagIds = collect($row['tags'])->filter()->map(
                fn (string $name) => Tag::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => $name],
                )->id,
            );
            $post->tags()->sync($tagIds);
        }

        if ($downloadCovers && $row['cover_url'] && filter_var($row['cover_url'], FILTER_VALIDATE_URL)) {
            try {
                $post->addMediaFromUrl($row['cover_url'])->toMediaCollection('cover');
            } catch (\Throwable) {
                // Unduhan gagal (URL mati, timeout) — post tetap tersimpan
                // tanpa cover, bukan alasan menggagalkan seluruh baris.
            }
        }

        $isUpdate ? $result['updated']++ : $result['created']++;
    }

    private static function validStatus(?string $status): ?string
    {
        $status = strtolower((string) $status);

        return in_array($status, [
            Post::STATUS_DRAFT, Post::STATUS_PENDING, Post::STATUS_SCHEDULED, Post::STATUS_PUBLISHED,
        ], true) ? $status : null;
    }
}
