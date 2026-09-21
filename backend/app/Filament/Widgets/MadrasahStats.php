<?php

namespace App\Filament\Widgets;

use App\Models\Achievement;
use App\Models\Agenda;
use App\Models\Comment;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Gallery;
use App\Models\Page;
use App\Models\Post;
use App\Models\Teacher;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * "Sekilas" — ringkasan seluruh isi situs, meniru widget "At a Glance" di
 * dashboard WordPress. Setiap kartu menautkan ke resource-nya.
 */
class MadrasahStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $pending = Post::where('status', Post::STATUS_PENDING)->count();
        $scheduled = Post::where('status', Post::STATUS_SCHEDULED)->count();
        $draft = Post::where('status', Post::STATUS_DRAFT)->count();
        $unread = Contact::where('is_read', false)->count();
        $pendingComments = Comment::pending()->count();

        return [
            Stat::make('Berita terbit', Post::where('status', Post::STATUS_PUBLISHED)->count())
                ->description(trim(implode(' · ', array_filter([
                    $draft ? "{$draft} draft" : null,
                    $pending ? "{$pending} menunggu tinjauan" : null,
                    $scheduled ? "{$scheduled} terjadwal" : null,
                ]))) ?: 'semua terbit')
                ->descriptionIcon($pending ? 'heroicon-m-exclamation-circle' : null)
                ->icon('heroicon-o-newspaper')
                ->color($pending ? 'warning' : 'success')
                ->url(route('filament.admin.resources.posts.index')),

            Stat::make('Halaman', Page::where('slug', '!=', Page::HOMEPAGE_SLUG)->count())
                ->description(Page::where('is_published', false)
                    ->where('slug', '!=', Page::HOMEPAGE_SLUG)->count().' belum terbit')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->url(route('filament.admin.resources.pages.index')),

            Stat::make('Guru & Tendik', Teacher::where('is_active', true)->count())
                ->icon('heroicon-o-users')
                ->color('info')
                ->url(route('filament.admin.resources.teachers.index')),

            Stat::make('Agenda mendatang', Agenda::where('start_at', '>=', now())->count())
                ->icon('heroicon-o-calendar-days')
                ->color('warning')
                ->url(route('filament.admin.resources.agendas.index')),

            Stat::make('Galeri', Gallery::count())
                ->icon('heroicon-o-photo')
                ->color('gray')
                ->url(route('filament.admin.resources.galleries.index')),

            Stat::make('Dokumen', Document::count())
                ->description(Document::sum('downloads').'× diunduh')
                ->icon('heroicon-o-folder')
                ->color('gray')
                ->url(route('filament.admin.resources.documents.index')),

            Stat::make('Prestasi', Achievement::count())
                ->icon('heroicon-o-trophy')
                ->color('gray')
                ->url(route('filament.admin.resources.achievements.index')),

            Stat::make('Komentar', Comment::approved()->count())
                ->description($pendingComments
                    ? "{$pendingComments} menunggu moderasi"
                    : 'semua termoderasi')
                ->descriptionIcon($pendingComments ? 'heroicon-m-exclamation-circle' : null)
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color($pendingComments ? 'warning' : 'gray')
                ->url(route('filament.admin.resources.comments.index')),

            Stat::make('Pesan belum dibaca', $unread)
                ->description('dari '.Contact::count().' total pesan')
                ->icon('heroicon-o-inbox')
                ->color($unread ? 'danger' : 'gray')
                ->url(route('filament.admin.resources.contacts.index')),
        ];
    }
}
