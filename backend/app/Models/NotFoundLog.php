<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Satu URL yang frontend jawab 404, ala tab "404s" plugin Redirection.
 * Dicatat lewat NotFoundLog::record() dari endpoint API POST /log-404 yang
 * dipanggil proxy.ts tiap kali memancarkan 404 sungguhan.
 */
class NotFoundLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'hits' => 'integer',
        'last_seen_at' => 'datetime',
        'resolved_at' => 'datetime',
        'ignored_at' => 'datetime',
    ];

    /**
     * Catat satu kunjungan 404. Upsert per path: baris baru mulai di hits 1,
     * path yang sudah ada bertambah hits + segarkan last_seen_at/referrer/UA.
     * Path dinormalkan (selalu diawali "/", buang query string & trailing slash).
     */
    public static function record(string $path, ?string $referrer = null, ?string $userAgent = null): void
    {
        $path = '/'.ltrim(Str::before($path, '?'), '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        // Abaikan path yang jelas bukan halaman (aset, file).
        if (Str::contains($path, ['..']) || preg_match('/\.[a-z0-9]{1,5}$/i', $path)) {
            return;
        }

        $log = static::firstOrNew(['path' => $path]);
        $log->hits = ($log->exists ? $log->hits : 0) + 1;
        $log->last_seen_at = now();
        $log->referrer = $referrer ? Str::limit($referrer, 250, '') : $log->referrer;
        $log->user_agent = $userAgent ? Str::limit($userAgent, 500, '') : $log->user_agent;

        // Kunjungan baru pada path yang sudah "selesai" membuka lagi statusnya —
        // artinya redirect sebelumnya tak menutup semua jalur ke URL ini.
        if ($log->exists && $log->resolved_at) {
            $log->resolved_at = null;
        }

        $log->save();
    }

    /** Belum dibuatkan redirect dan belum diabaikan (yang dihitung di badge). */
    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereNull('resolved_at')->whereNull('ignored_at');
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->whereNotNull('resolved_at');
    }

    public function scopeIgnored(Builder $query): Builder
    {
        return $query->whereNotNull('ignored_at');
    }
}
