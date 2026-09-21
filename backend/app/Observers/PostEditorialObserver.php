<?php

namespace App\Observers;

use App\Models\Post;
use App\Notifications\PostPendingReview;
use App\Support\EditorialStaff;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

/**
 * Notifikasi editorial ala WordPress:
 * - Post masuk status `pending` (kontributor "Ajukan untuk ditinjau")
 *   → beri tahu semua editor & super_admin lewat lonceng panel.
 *
 * Hanya memicu saat status BERUBAH menjadi pending, bukan setiap simpan.
 */
class PostEditorialObserver
{
    public function created(Post $post): void
    {
        if ($post->status === Post::STATUS_PENDING) {
            $this->notifyPending($post);
        }
    }

    public function updated(Post $post): void
    {
        if ($post->wasChanged('status') && $post->status === Post::STATUS_PENDING) {
            $this->notifyPending($post);
        }
    }

    private function notifyPending(Post $post): void
    {
        $staff = EditorialStaff::all();
        if ($staff->isEmpty()) {
            return;
        }

        Notification::send($staff, new PostPendingReview($post, Auth::user()?->name));
    }
}
