<?php

namespace App\Models;

use App\Models\Concerns\TriggersFrontendRevalidation;
use Database\Factories\UserFactory;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication;
use Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class User extends Authenticatable implements FilamentUser, HasAppAuthentication, HasAppAuthenticationRecovery, HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, HasSlug, InteractsWithMedia, Notifiable, TriggersFrontendRevalidation;

    /**
     * Jangan mem-ping frontend saat hanya kolom sesi/verifikasi yang berubah
     * (login menulis remember_token setiap kali). Hanya profil publik yang
     * memengaruhi halaman /penulis/{slug}.
     */
    protected static array $revalidationIgnoredAttributes = ['remember_token', 'email_verified_at', 'password'];

    protected $fillable = ['name', 'email', 'password', 'slug', 'job_title', 'bio', 'avatar', 'social', 'show_publicly'];

    protected $hidden = [
        'password', 'remember_token',
        'app_authentication_secret', 'app_authentication_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'social' => 'array',
            'show_publicly' => 'boolean',
            // Sama sensitifnya dengan password — secret TOTP & kode pemulihan
            // 2FA dienkripsi at-rest, bukan cuma disembunyikan dari $hidden.
            'app_authentication_secret' => 'encrypted',
            'app_authentication_recovery_codes' => 'encrypted:array',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->width(240)->height(240)->nonQueued();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['super_admin', 'editor', 'kontributor']);
    }

    /** Berita yang ditulis user ini (wp: author archive). */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Boleh tampil sebagai penulis publik? Punya slug, sakelar menyala, dan
     * minimal satu berita terbit — meniru cara WP hanya membuat author archive
     * untuk user yang benar-benar menulis.
     */
    public function isPublicAuthor(): bool
    {
        if (! $this->show_publicly || blank($this->slug)) {
            return false;
        }

        // Pakai hitungan yang sudah di-eager-load bila ada (hindari N+1 di daftar berita).
        if (array_key_exists('published_posts_count', $this->attributes)) {
            return (int) $this->attributes['published_posts_count'] > 0;
        }

        return $this->posts()->published()->exists();
    }

    public function avatarUrl(): ?string
    {
        $media = $this->getFirstMedia('avatar');
        if ($media) {
            return $media->hasGeneratedConversion('thumb') ? $media->getUrl('thumb') : $media->getUrl();
        }

        if ($this->avatar && Storage::disk('public')->exists($this->avatar)) {
            return asset('storage/'.$this->avatar);
        }

        return null;
    }

    /** Hanya super_admin yang boleh menyamar (debug izin/role kontributor tanpa tahu password). */
    public function canImpersonate(): bool
    {
        return $this->hasRole('super_admin');
    }

    /** Sesama super_admin tidak bisa saling menyamar — hindari kebingungan siapa "sebenarnya" login. */
    public function canBeImpersonated(): bool
    {
        return ! $this->hasRole('super_admin');
    }

    // --- Filament\Auth\MultiFactor\App\Contracts\HasAppAuthentication ---

    public function getAppAuthenticationSecret(): ?string
    {
        return $this->app_authentication_secret;
    }

    public function saveAppAuthenticationSecret(#[\SensitiveParameter] ?string $secret): void
    {
        $this->app_authentication_secret = $secret;
        $this->save();
    }

    public function getAppAuthenticationHolderName(): string
    {
        return $this->email;
    }

    // --- Filament\Auth\MultiFactor\App\Contracts\HasAppAuthenticationRecovery ---

    public function getAppAuthenticationRecoveryCodes(): ?array
    {
        return $this->app_authentication_recovery_codes;
    }

    public function saveAppAuthenticationRecoveryCodes(#[\SensitiveParameter] ?array $codes): void
    {
        $this->app_authentication_recovery_codes = $codes;
        $this->save();
    }
}
