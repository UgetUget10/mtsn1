<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Support\Revisions\RevisionDiff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RevisionDiffTest extends TestCase
{
    use RefreshDatabase;

    public function test_word_diff_marks_removed_and_added_words(): void
    {
        $html = RevisionDiff::wordDiff('rapat wali murid semester ganjil', 'rapat wali murid semester genap');

        $this->assertStringContainsString('<del>ganjil</del>', $html);
        $this->assertStringContainsString('<ins>genap</ins>', $html);
        // Kata yang tidak berubah tetap polos.
        $this->assertStringContainsString('rapat wali murid semester', strip_tags($html));
    }

    public function test_word_diff_escapes_html_in_content(): void
    {
        $html = RevisionDiff::wordDiff('aman', '<script>alert(1)</script>');

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_identical_text_produces_no_markers(): void
    {
        $html = RevisionDiff::wordDiff('teks sama persis', 'teks sama persis');

        $this->assertStringNotContainsString('<del>', $html);
        $this->assertStringNotContainsString('<ins>', $html);
    }

    public function test_against_flags_changed_fields_and_sorts_them_first(): void
    {
        $author = User::factory()->create();
        $post = Post::create([
            'title' => 'Judul Awal',
            'excerpt' => 'Ringkasan tetap',
            'body' => '<p>Isi mula-mula.</p>',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'user_id' => $author->id,
        ]);

        // Hanya ubah judul → revisi menyimpan nilai LAMA.
        $post->update(['title' => 'Judul Baru']);

        $revision = $post->revisions()->latest('id')->first();
        $rows = RevisionDiff::against($revision, $post->fresh());

        $byField = collect($rows)->keyBy('field');

        $this->assertTrue($byField['title']['changed']);
        $this->assertFalse($byField['excerpt']['changed']);
        $this->assertStringContainsString('<del>Awal</del>', $byField['title']['html']);
        $this->assertStringContainsString('<ins>Baru</ins>', $byField['title']['html']);

        // Field yang berubah harus di urutan pertama.
        $this->assertTrue($rows[0]['changed']);
    }

    public function test_translatable_json_is_compared_per_locale(): void
    {
        $author = User::factory()->create();
        $post = Post::create([
            'title' => ['id' => 'Halo dunia', 'en' => 'Hello world'],
            'body' => '<p>x</p>',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'user_id' => $author->id,
        ]);

        $post->update(['title' => ['id' => 'Halo bumi', 'en' => 'Hello world']]);

        $revision = $post->revisions()->latest('id')->first();
        $rows = collect(RevisionDiff::against($revision, $post->fresh()))->keyBy('field');

        $this->assertTrue($rows['title']['changed']);
        $this->assertStringContainsString('<del>dunia</del>', $rows['title']['html']);
        $this->assertStringContainsString('<ins>bumi</ins>', $rows['title']['html']);
    }

    public function test_html_tags_are_stripped_before_comparing(): void
    {
        $author = User::factory()->create();
        $post = Post::create([
            'title' => 'T',
            'body' => '<p>Kalimat pertama.</p>',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'user_id' => $author->id,
        ]);

        // Hanya bungkus tag yang berubah, teksnya sama → dianggap TIDAK berubah.
        $post->update(['body' => '<div>Kalimat pertama.</div>']);

        $revision = $post->revisions()->latest('id')->first();
        $rows = collect(RevisionDiff::against($revision, $post->fresh()))->keyBy('field');

        $this->assertFalse($rows['body']['changed']);
    }
}
