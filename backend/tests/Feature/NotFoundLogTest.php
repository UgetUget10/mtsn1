<?php

namespace Tests\Feature;

use App\Models\NotFoundLog;
use App\Models\Redirect;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class NotFoundLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('log-404');
    }

    public function test_endpoint_records_a_404(): void
    {
        $this->postJson('/api/v1/log-404', [
            'path' => '/berita/judul-yang-salah',
            'referrer' => 'https://www.google.com/',
        ])->assertNoContent();

        $this->assertDatabaseHas('not_found_logs', [
            'path' => '/berita/judul-yang-salah',
            'hits' => 1,
            'referrer' => 'https://www.google.com/',
        ]);
    }

    public function test_repeat_hits_increment_the_counter(): void
    {
        NotFoundLog::record('/x');
        NotFoundLog::record('/x');
        NotFoundLog::record('/x');

        $this->assertSame(3, NotFoundLog::firstWhere('path', '/x')->hits);
        $this->assertSame(1, NotFoundLog::count());
    }

    public function test_path_is_normalised(): void
    {
        NotFoundLog::record('/berita/foo/?utm=1');
        NotFoundLog::record('/berita/foo');

        $this->assertSame(1, NotFoundLog::count());
        $this->assertSame(2, NotFoundLog::first()->hits);
    }

    public function test_asset_like_paths_are_ignored(): void
    {
        NotFoundLog::record('/wp-content/uploads/x.php');
        NotFoundLog::record('/favicon.ico');

        $this->assertSame(0, NotFoundLog::count());
    }

    public function test_outstanding_scope_excludes_resolved_and_ignored(): void
    {
        NotFoundLog::record('/a');
        NotFoundLog::record('/b');
        NotFoundLog::record('/c');
        NotFoundLog::firstWhere('path', '/b')->update(['resolved_at' => now()]);
        NotFoundLog::firstWhere('path', '/c')->update(['ignored_at' => now()]);

        $this->assertSame(['/a'], NotFoundLog::query()->outstanding()->pluck('path')->all());
    }

    public function test_new_hit_reopens_a_resolved_log(): void
    {
        NotFoundLog::record('/a');
        NotFoundLog::firstWhere('path', '/a')->update(['resolved_at' => now()]);

        NotFoundLog::record('/a');

        $this->assertNull(NotFoundLog::firstWhere('path', '/a')->resolved_at);
    }

    public function test_creating_a_redirect_from_a_log_marks_it_resolved(): void
    {
        // Simulasikan aksi tabel "Buatkan pengalihan".
        $log = tap(new NotFoundLog(['path' => '/berita/lama']))->save();

        Redirect::updateOrCreate(
            ['from_path' => $log->path],
            ['to_path' => '/berita/baru', 'status' => 301, 'source' => 'manual'],
        );
        $log->update(['resolved_at' => now()]);

        $this->assertDatabaseHas('redirects', [
            'from_path' => '/berita/lama',
            'to_path' => '/berita/baru',
            'status' => 301,
        ]);
        $this->assertNotNull($log->fresh()->resolved_at);
    }
}
