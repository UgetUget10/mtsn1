<?php

namespace App\Filament\Widgets;

use App\Models\Post;
use App\Models\User;
use Filament\Widgets\Widget;

/**
 * Checklist onboarding untuk kontributor/editor baru — highlight langkah
 * dasar sebelum menerbitkan artikel pertama. Hanya tampil untuk role
 * kontributor/editor (super_admin dianggap sudah paham sistem), dan otomatis
 * hilang begitu semua langkah selesai supaya tidak menumpuk sebagai gangguan
 * permanen di dashboard.
 */
class OnboardingChecklist extends Widget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.onboarding-checklist';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && ($user->hasRole('kontributor') || $user->hasRole('editor')) && ! self::isComplete($user);
    }

    private static function isComplete(User $user): bool
    {
        return filled($user->avatarUrl())
            && Post::where('user_id', $user->id)->exists();
    }

    /** @return array<int, array{label: string, done: bool, url: string}> */
    public function getSteps(): array
    {
        $user = auth()->user();

        return [
            [
                'label' => 'Lengkapi profil Anda (nama & foto)',
                'done' => filled($user->avatarUrl()),
                'url' => route('filament.admin.auth.profile'),
            ],
            [
                'label' => 'Buat berita pertama Anda',
                'done' => Post::where('user_id', $user->id)->exists(),
                'url' => route('filament.admin.resources.posts.create'),
            ],
            [
                'label' => 'Kenali status berita: Draf → Menunggu tinjauan → Terbit',
                'done' => Post::where('user_id', $user->id)->whereIn('status', [Post::STATUS_PENDING, Post::STATUS_PUBLISHED])->exists(),
                'url' => route('filament.admin.resources.posts.index'),
            ],
        ];
    }
}
