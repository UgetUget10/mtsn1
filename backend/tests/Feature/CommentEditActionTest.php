<?php

namespace Tests\Feature;

use App\Filament\Resources\Comments\Pages\ListComments;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * wp: Comments → Edit — staf boleh merapikan isi / identitas penulis sebuah
 * komentar (mis. buang tautan spam) sebelum menyetujuinya.
 */
class CommentEditActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RoleSeeder::class);

        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);
    }

    private function comment(string $status = Comment::STATUS_PENDING): Comment
    {
        $post = Post::create([
            'title' => 'Artikel', 'body' => '<p>Isi.</p>',
            'status' => Post::STATUS_PUBLISHED, 'published_at' => now()->subDay(),
            'user_id' => User::factory()->create()->id,
        ]);

        return $post->comments()->create([
            'author_name' => 'Budi', 'author_email' => 'budi@example.com',
            'author_ip' => '127.0.0.1', 'body' => 'Komentar asli', 'status' => $status,
        ]);
    }

    public function test_staff_can_edit_a_comment_body_and_author_fields(): void
    {
        $comment = $this->comment();

        Livewire::test(ListComments::class)
            ->callTableAction('edit', $comment, data: [
                'author_name' => 'Budi Santoso',
                'author_email' => 'budi.s@example.com',
                'author_url' => 'https://budi.example.com',
                'body' => 'Komentar yang sudah dirapikan.',
            ])
            ->assertHasNoTableActionErrors();

        $comment->refresh();
        $this->assertSame('Budi Santoso', $comment->author_name);
        $this->assertSame('budi.s@example.com', $comment->author_email);
        $this->assertSame('https://budi.example.com', $comment->author_url);
        $this->assertSame('Komentar yang sudah dirapikan.', $comment->body);
    }

    public function test_edit_strips_html_from_body(): void
    {
        $comment = $this->comment();

        Livewire::test(ListComments::class)
            ->callTableAction('edit', $comment, data: [
                'author_name' => 'Budi',
                'body' => 'Lihat <a href="http://spam.example">ini</a> <script>alert(1)</script>',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertSame('Lihat ini alert(1)', $comment->refresh()->body);
    }

    public function test_edit_requires_name_and_body(): void
    {
        $comment = $this->comment();

        Livewire::test(ListComments::class)
            ->callTableAction('edit', $comment, data: ['author_name' => '', 'body' => ''])
            ->assertHasTableActionErrors(['author_name', 'body']);
    }
}
