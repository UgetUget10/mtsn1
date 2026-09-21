<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Support\Revalidation\RevalidationTargets;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Menerbitkan post berstatus `scheduled` yang sudah lewat waktu tayang —
 * setara cron `publish_future_post` di WordPress (status `future`).
 *
 * Dijadwalkan tiap menit di routes/console.php.
 */
class PublishScheduledPosts extends Command
{
    protected $signature = 'posts:publish-due';

    protected $description = 'Terbitkan post terjadwal yang sudah jatuh tempo (wp: publish_future_post)';

    /**
     * Kunci cache "detak jantung" — ditulis setiap kali command ini benar-benar
     * jalan (lewat scheduler `schedule:run`), dibaca App\Filament\Widgets\NeedsAttention
     * untuk mendeteksi bila server cron/Task Scheduler berhenti memanggil
     * `php artisan schedule:run`. Tanpa ini, post `scheduled` yang jatuh tempo
     * bisa diam-diam tidak pernah terbit tanpa ada peringatan ke admin.
     */
    public const HEARTBEAT_KEY = 'scheduler.posts-publish-due.last-run';

    public function handle(): int
    {
        Cache::put(self::HEARTBEAT_KEY, now()->toIso8601String(), now()->addDays(7));

        $due = Post::dueForPublish()->get();

        if ($due->isEmpty()) {
            $this->info('Tidak ada post terjadwal yang jatuh tempo.');

            return self::SUCCESS;
        }

        foreach ($due as $post) {
            $post->update(['status' => Post::STATUS_PUBLISHED]);
            $this->line("Diterbitkan: [{$post->id}] {$post->getRawOriginal('title')}");

            // TriggersFrontendRevalidation nonaktif di konsol — panggil manual
            // agar halaman berita langsung tampil saat post terjadwal terbit.
            $this->revalidate($post);
        }

        $this->info("Selesai. {$due->count()} post diterbitkan.");

        return self::SUCCESS;
    }

    private function revalidate(Post $post): void
    {
        $url = config('services.frontend.revalidate_url');
        $secret = config('services.frontend.revalidate_secret');

        if (! $url || ! $secret) {
            return;
        }

        $targets = RevalidationTargets::for($post);

        try {
            Http::timeout(3)->acceptJson()->post($url, [
                'secret' => $secret,
                'tags' => $targets['tags'],
                'paths' => $targets['paths'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('Revalidate saat publish terjadwal gagal: '.$e->getMessage());
        }
    }
}
