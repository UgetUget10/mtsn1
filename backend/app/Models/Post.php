<?php

namespace App\Models;

use App\Models\Concerns\HasRevisions;
use App\Models\Concerns\TriggersFrontendRevalidation;
use App\Support\DiscussionSettings;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

class Post extends Model implements HasMedia
{
    use HasRevisions;
    use HasSlug;
    use HasTranslations;
    use InteractsWithMedia;
    use LogsActivity;
    use SoftDeletes;
    use TriggersFrontendRevalidation;

    /** Status ala WordPress. */
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';      // menunggu review (contributor submit)

    public const STATUS_SCHEDULED = 'scheduled';  // wp: future

    public const STATUS_PUBLISHED = 'published';

    /** Visibilitas ala WordPress (Publish → Visibility). */
    public const VISIBILITY_PUBLIC = 'public';

    public const VISIBILITY_PASSWORD = 'password';

    public const VISIBILITY_PRIVATE = 'private';

    protected $guarded = [];

    protected $hidden = ['password'];

    /** @var array<int, string> */
    public array $translatable = ['title', 'excerpt', 'body'];

    /** Kolom yang disnapshot ke tabel revisions. */
    protected array $revisionable = ['title', 'excerpt', 'body'];

    protected $casts = [
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
        'meta' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (Post $post): void {
            $post->preview_token ??= (string) Str::uuid();
        });

        // Simpan `password` sebagai hash (seperti WP tidak, tapi lebih aman: WP
        // menyimpan plaintext di post_password). Hash bcrypt bila belum di-hash.
        static::saving(function (Post $post): void {
            if ($post->visibility !== self::VISIBILITY_PASSWORD) {
                $post->password = null;

                return;
            }
            if (filled($post->password) && ! str_starts_with((string) $post->password, '$2y$')) {
                $post->password = bcrypt($post->password);
            }
        });
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('title')->saveSlugsTo('slug');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'status', 'visibility', 'published_at', 'category_id', 'is_featured'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')->width(800)->height(600)->nonQueued();
        $this->addMediaConversion('thumb')->width(400)->height(300)->nonQueued();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** Taksonomi many-to-many ala WP (post bisa >1 kategori & >1 tag). */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** Komentar pengunjung ala WordPress (semua status). */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Komentar masih dibuka? Meniru `comment_status` WP + auto-close setelah
     * sejumlah hari (WP: close_comments_for_old_posts / close_comments_days_old).
     */
    public function commentsAreOpen(): bool
    {
        if (! DiscussionSettings::enabled()) {
            return false;
        }

        if (($this->meta['comments_closed'] ?? false) === true) {
            return false;
        }

        $days = DiscussionSettings::closeAfterDays();
        if ($days > 0 && $this->published_at && $this->published_at->lt(now()->subDays($days))) {
            return false;
        }

        return $this->isViewablePublicly();
    }

    /**
     * Ringkasan siap-tampil ala WordPress `get_the_excerpt()`:
     *  1. pakai `excerpt` manual bila diisi editor;
     *  2. jika `body` memuat tag <!--more-->, ambil teks sebelum tag itu;
     *  3. jika tidak, potong `body` jadi ~$words kata (wp: excerpt_length 55)
     *     dan tambahkan elipsis (wp: excerpt_more "[…]").
     *
     * Selalu mengembalikan teks polos (tanpa HTML) — aman untuk kartu & meta.
     */
    public function displayExcerpt(int $words = 55): ?string
    {
        if (filled($this->excerpt)) {
            return trim((string) $this->excerpt);
        }

        $body = (string) $this->body;
        if (blank($body)) {
            return null;
        }

        // Hormati quicktag <!--more--> / <!--more teks-->.
        if (preg_match('/<!--\s*more(?:\s+.*?)?\s*-->/is', $body, $m, PREG_OFFSET_CAPTURE)) {
            $body = substr($body, 0, $m[0][1]);
        }

        $text = trim(preg_replace('/\s+/', ' ', strip_tags($body)) ?? '');
        if ($text === '') {
            return null;
        }

        return Str::words($text, $words, ' …');
    }

    /**
     * Apakah artikel benar-benar disunting SETELAH terbit? (wp: post_modified
     * berbeda bermakna dari post_date).
     *
     * Ambang 60 detik meredam selisih sepele saat penyimpanan pertama. Kenaikan
     * `views` tidak menyentuh `updated_at` karena PostController memakai
     * `withoutEvents()` + query builder, jadi angka ini tetap jujur.
     */
    public function wasEditedAfterPublish(): bool
    {
        if (! $this->published_at || ! $this->updated_at) {
            return false;
        }

        return $this->updated_at->diffInSeconds($this->published_at, absolute: true) > 60
            && $this->updated_at->gt($this->published_at);
    }

    /** Apakah `body` memuat quicktag <!--more--> (teaser/potong "Baca selengkapnya"). */
    public function hasMoreTag(): bool
    {
        return (bool) preg_match('/<!--\s*more(?:\s+.*?)?\s*-->/is', (string) $this->body);
    }

    /**
     * Artikel terbit sebelumnya secara kronologis (wp: previous_post_link —
     * "lebih tua"). Null bila ini yang tertua.
     */
    public function previousPost(): ?Post
    {
        return static::query()
            ->published()
            ->where(fn (Builder $q) => $q
                ->where('published_at', '<', $this->published_at)
                ->orWhere(fn (Builder $w) => $w
                    ->where('published_at', $this->published_at)
                    ->where('id', '<', $this->id)))
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Artikel terbit berikutnya secara kronologis (wp: next_post_link —
     * "lebih baru"). Null bila ini yang terbaru.
     */
    public function nextPost(): ?Post
    {
        return static::query()
            ->published()
            ->where(fn (Builder $q) => $q
                ->where('published_at', '>', $this->published_at)
                ->orWhere(fn (Builder $w) => $w
                    ->where('published_at', $this->published_at)
                    ->where('id', '>', $this->id)))
            ->orderBy('published_at')
            ->orderBy('id')
            ->first();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PUBLISHED)
            ->where('published_at', '<=', now())
            // Post `private` tidak pernah tampil di jalur publik (daftar/feed/sitemap).
            ->where('visibility', '!=', self::VISIBILITY_PRIVATE);
    }

    /** Post yang jatuh tempo publish terjadwal (dipakai command posts:publish-due). */
    public function scopeDueForPublish(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SCHEDULED)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function isViewablePublicly(): bool
    {
        return $this->status === self::STATUS_PUBLISHED
            && $this->published_at !== null
            && $this->published_at->lte(now())
            && $this->visibility !== self::VISIBILITY_PRIVATE;
    }

    /** Post terbit tapi isinya terkunci sampai kata sandi benar dimasukkan. */
    public function isPasswordProtected(): bool
    {
        return $this->visibility === self::VISIBILITY_PASSWORD && filled($this->password);
    }

    public function checkPassword(?string $plain): bool
    {
        return $this->isPasswordProtected()
            && filled($plain)
            && Hash::check($plain, $this->password);
    }

    /**
     * @return array<string, string>
     */
    public static function visibilityOptions(): array
    {
        return [
            self::VISIBILITY_PUBLIC => 'Publik',
            self::VISIBILITY_PASSWORD => 'Dilindungi kata sandi',
            self::VISIBILITY_PRIVATE => 'Privat',
        ];
    }

    /**
     * Status yang boleh dipilih user tertentu di form. Kontributor (WordPress:
     * "Contributor") hanya boleh menyimpan draft atau mengajukan untuk ditinjau
     * — tidak bisa menerbitkan atau menjadwalkan sendiri.
     *
     * @return array<string, string>
     */
    public static function assignableStatuses(?Authenticatable $user): array
    {
        $all = [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING => 'Menunggu review',
            self::STATUS_SCHEDULED => 'Terjadwal',
            self::STATUS_PUBLISHED => 'Terbit',
        ];

        $isContributorOnly = $user
            && method_exists($user, 'hasRole')
            && $user->hasRole('kontributor')
            && ! $user->hasRole(['editor', 'super_admin']);

        if ($isContributorOnly) {
            return [
                self::STATUS_DRAFT => 'Draft',
                self::STATUS_PENDING => 'Ajukan untuk ditinjau',
            ];
        }

        return $all;
    }
}
