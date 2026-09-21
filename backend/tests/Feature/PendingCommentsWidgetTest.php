<?php

namespace Tests\Feature;

use App\Filament\Widgets\PendingComments;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Filament\Pages\Dashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Widget dashboard "Komentar menunggu moderasi" — meniru bagian Comments pada
 * widget Activity WordPress. Antrean + aksi cepat approve/spam/tolak.
 */
class PendingCommentsWidgetTest extends TestCase
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

    private function pendingComment(): Comment
    {
        $post = Post::create([
            'title' => 'Artikel Widget',
            'body' => '<p>Isi.</p>',
            'status' => Post::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
            'user_id' => User::factory()->create()->id,
        ]);

        return $post->comments()->create([
            'author_name' => 'Budi',
            'author_email' => 'budi@example.com',
            'author_ip' => '127.0.0.1',
            'body' => 'Komentar mantap sekali',
            'status' => Comment::STATUS_PENDING,
        ]);
    }

    public function test_widget_lists_pending_comments(): void
    {
        $comment = $this->pendingComment();

        Livewire::test(PendingComments::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$comment]);
    }

    public function test_widget_is_hidden_when_no_pending_comments(): void
    {
        $this->assertFalse(PendingComments::canView());

        $this->pendingComment();

        $this->assertTrue(PendingComments::canView());
    }

    public function test_approve_action_sets_status_approved(): void
    {
        $comment = $this->pendingComment();

        Livewire::test(PendingComments::class)
            ->callTableAction('approve', $comment)
            ->assertOk();

        $this->assertSame(Comment::STATUS_APPROVED, $comment->refresh()->status);
    }

    public function test_spam_action_sets_status_spam(): void
    {
        $comment = $this->pendingComment();

        Livewire::test(PendingComments::class)
            ->callTableAction('spam', $comment)
            ->assertOk();

        $this->assertSame(Comment::STATUS_SPAM, $comment->refresh()->status);
    }

    public function test_trash_action_sets_status_trash(): void
    {
        $comment = $this->pendingComment();

        Livewire::test(PendingComments::class)
            ->callTableAction('trash', $comment)
            ->assertOk();

        $this->assertSame(Comment::STATUS_TRASH, $comment->refresh()->status);
    }

    public function test_dashboard_still_renders(): void
    {
        $this->pendingComment();

        Livewire::test(Dashboard::class)->assertOk();
    }
}
