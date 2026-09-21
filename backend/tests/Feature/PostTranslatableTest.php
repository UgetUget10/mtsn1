<?php

namespace Tests\Feature;

use App\Filament\Resources\Posts\Pages\CreatePost;
use App\Filament\Resources\Posts\Pages\EditPost;
use App\Models\Post;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PostTranslatableTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsSuperAdmin(): User
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seed(RoleSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);

        return $user;
    }

    public function test_post_saves_both_locales_through_filament_form(): void
    {
        $this->actingAsSuperAdmin();

        Livewire::test(CreatePost::class)
            ->fillForm([
                'title.id' => 'Judul Bahasa Indonesia',
                'title.en' => 'English Title',
                'excerpt.id' => 'Ringkasan ID',
                'excerpt.en' => 'Excerpt EN',
                'body.id' => '<p>Isi ID</p>',
                'body.en' => '<p>Body EN</p>',
                'slug' => 'judul-bahasa-indonesia',
                'status' => 'draft',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $post = Post::where('slug', 'judul-bahasa-indonesia')->firstOrFail();

        $this->assertSame('Judul Bahasa Indonesia', $post->getTranslation('title', 'id'));
        $this->assertSame('English Title', $post->getTranslation('title', 'en'));
        $this->assertSame('Ringkasan ID', $post->getTranslation('excerpt', 'id'));
        $this->assertSame('Excerpt EN', $post->getTranslation('excerpt', 'en'));

        // Edit ulang: field harus terisi dari kedua locale, dan menyimpan lagi
        // tidak boleh menghilangkan locale yang tidak diubah.
        Livewire::test(EditPost::class, ['record' => $post->getRouteKey()])
            ->assertFormSet([
                'title.id' => 'Judul Bahasa Indonesia',
                'title.en' => 'English Title',
            ])
            ->fillForm(['title.id' => 'Judul Diperbarui'])
            ->call('save')
            ->assertHasNoFormErrors();

        $post->refresh();
        $this->assertSame('Judul Diperbarui', $post->getTranslation('title', 'id'));
        $this->assertSame('English Title', $post->getTranslation('title', 'en'), 'Locale en tidak boleh hilang saat hanya locale id diubah.');
    }
}
