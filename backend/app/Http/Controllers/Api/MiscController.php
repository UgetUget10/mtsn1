<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Achievement;
use App\Models\Agenda;
use App\Models\Document;
use App\Models\Extracurricular;
use App\Models\Gallery;
use App\Models\Setting;
use App\Models\Slider;
use App\Models\Teacher;
use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;

class MiscController extends Controller
{
    /**
     * URL media untuk sebuah model+koleksi. Utamakan media library (dengan
     * conversion bila ada); jatuh balik ke kolom path string lama selama
     * belum semua data dimigrasi (lihat App\Console\Commands\MigrateLegacyMedia)
     * — hanya jika file itu benar-benar masih ada di disk agar frontend
     * tidak menerima URL yang 403/404.
     */
    private function mediaUrl(HasMedia&Model $model, string $collection, ?string $legacyPath, ?string $conversion = null): ?string
    {
        $media = $model->getFirstMedia($collection);
        if ($media) {
            return $conversion && $media->hasGeneratedConversion($conversion)
                ? $media->getUrl($conversion)
                : $media->getUrl();
        }

        return $this->legacyUrl($legacyPath);
    }

    /** Alt text dari Pustaka Media untuk collection ini (null bila belum diisi). */
    private function mediaAlt(HasMedia&Model $model, string $collection): ?string
    {
        return $model->getFirstMedia($collection)?->getCustomProperty('alt');
    }

    private function legacyUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        if (str_starts_with($path, 'http')) {
            return $path;
        }
        $path = ltrim($path, '/');

        return Storage::disk('public')->exists($path) ? asset('storage/'.$path) : null;
    }

    public function sliders()
    {
        return Slider::where('is_active', true)->orderBy('order')->get()
            ->map(fn (Slider $s) => [
                'title' => $s->title,
                'subtitle' => $s->subtitle,
                'image' => $this->mediaUrl($s, 'image', $s->image, 'card'),
                'image_alt' => $this->mediaAlt($s, 'image'),
                'link' => $s->link,
            ]);
    }

    public function teachers(Request $request)
    {
        return Teacher::where('is_active', true)
            ->when($request->group, fn ($q, $g) => $q->where('group', $g))
            ->orderBy('order')->orderBy('name')
            ->get(['id', 'name', 'nip', 'position', 'subject', 'group', 'photo'])
            ->map(fn (Teacher $t) => [
                ...$t->only(['name', 'nip', 'position', 'subject', 'group']),
                'photo' => $this->mediaUrl($t, 'photo', $t->photo, 'avatar'),
                'photo_alt' => $this->mediaAlt($t, 'photo'),
            ]);
    }

    public function agendas(Request $request)
    {
        return Agenda::query()
            ->when($request->month, function ($q, $month) {
                [$y, $m] = array_pad(explode('-', $month), 2, null);
                $q->whereYear('start_at', $y)->whereMonth('start_at', $m);
            }, fn ($q) => $q->where('start_at', '>=', now()->startOfDay()))
            ->orderBy('start_at')
            ->get(['title', 'slug', 'description', 'start_at', 'end_at', 'location']);
    }

    public function galleries()
    {
        return Gallery::with('items')->latest('taken_on')->latest()->paginate(12)
            ->through(fn (Gallery $g) => [
                'title' => $g->title,
                'slug' => $g->slug,
                'description' => $g->description,
                'taken_on' => $g->taken_on?->toDateString(),
                'cover' => $this->mediaUrl($g, 'cover', $g->cover, 'card'),
                'cover_alt' => $this->mediaAlt($g, 'cover'),
                'items' => $g->items->map(fn ($i) => [
                    'type' => $i->type,
                    'url' => $i->type === 'video' ? $i->video_url : $this->mediaUrl($i, 'image', $i->path, 'card'),
                    'caption' => $i->caption,
                    'alt' => $i->type === 'video' ? null : ($this->mediaAlt($i, 'image') ?? $i->caption),
                ]),
            ]);
    }

    public function documents(Request $request)
    {
        return Document::with('category')
            ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->latest()->paginate(15)
            ->through(fn (Document $d) => [
                'id' => $d->id,
                'title' => $d->title,
                'category' => $d->category?->name,
                'size' => $this->humanSize($d->size),
                'url' => $this->mediaUrl($d, 'file', $d->file),
                // Tautan unduh yang menghitung: frontend memakai ini, bukan `url`
                // langsung, supaya kolom `downloads` bertambah (wp: download counter).
                'download_url' => route('documents.download', $d),
                'downloads' => $d->downloads,
            ]);
    }

    /**
     * Menambah penghitung unduhan lalu mengalihkan ke berkas asli — 302 supaya
     * browser/klien langsung menerima file. Mirip plugin "Download Monitor" WP.
     */
    public function downloadDocument(Document $document)
    {
        $url = $this->mediaUrl($document, 'file', $document->file);
        abort_unless($url, 404);

        // Hanya hitung unduhan yang benar-benar berhasil diarahkan ke berkas.
        // increment() memicu event `saved` → TriggersFrontendRevalidation akan
        // mem-ping frontend tiap unduhan; update langsung di query builder
        // menghindari itu (angka `downloads` tidak kritis untuk ISR).
        Document::withoutEvents(fn () => $document->newQuery()
            ->whereKey($document->getKey())
            ->increment('downloads'));

        return redirect()->away($url);
    }

    private function humanSize(?int $bytes): ?string
    {
        if (! $bytes) {
            return null;
        }
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        $n = (float) $bytes;
        while ($n >= 1024 && $i < count($units) - 1) {
            $n /= 1024;
            $i++;
        }

        return round($n, 1).' '.$units[$i];
    }

    public function achievements()
    {
        return Achievement::latest('year')->paginate(12)
            ->through(fn (Achievement $a) => [
                'title' => $a->title,
                'student_name' => $a->student_name,
                'level' => $a->level,
                'year' => $a->year,
                'description' => $a->description,
                'image' => $this->mediaUrl($a, 'image', $a->image, 'card'),
                'image_alt' => $this->mediaAlt($a, 'image'),
            ]);
    }

    public function testimonials()
    {
        return Testimonial::where('is_active', true)
            ->orderBy('order')
            ->get(['quote', 'name', 'role']);
    }

    public function extracurriculars()
    {
        return Extracurricular::orderBy('name')->get()
            ->map(fn (Extracurricular $e) => [
                'name' => $e->name,
                'slug' => $e->slug,
                'coach' => $e->coach,
                'schedule' => $e->schedule,
                'description' => $e->description,
                'image' => $this->mediaUrl($e, 'image', $e->image, 'card'),
                'image_alt' => $this->mediaAlt($e, 'image'),
            ]);
    }

    /**
     * Kunci Setting yang boleh diekspos ke frontend publik. Semua key di sini
     * memang konfigurasi situs publik (identitas, kontak, tautan portal,
     * statistik hero). Allowlist eksplisit ini mencegah kebocoran bila suatu
     * saat ada key sensitif (token, kredensial) yang tersimpan di tabel yang
     * sama — API tidak akan pernah membocorkannya tanpa ditambahkan di sini.
     */
    private const PUBLIC_SETTING_KEYS = [
        'site_name', 'site_tagline', 'school_name', 'npsn', 'nsm',
        'logo', 'favicon', 'og_image',
        'principal_name', 'principal_photo', 'principal_word',
        'address', 'phone', 'whatsapp', 'email', 'service_hours',
        'maps_embed', 'maps_url',
        'ppdb_url', 'ppdb_deadline', 'lms_url', 'ptsp_url', 'rdm_url',
        'sakip_url', 'survey_url', 'pmbm_url',
        'announcement', 'announcement_url',
        'facebook', 'instagram', 'youtube', 'twitter',
        'stat_students', 'stat_teachers', 'stat_staff', 'stat_classes',
        'stat_accreditation', 'stat_alumni',
        'homepage_sections',
        'badges',
        // Mirror "Settings -> Reading/General" WordPress.
        'front_page_id', 'posts_page_id', 'posts_per_page',
        'date_format', 'time_format', 'timezone', 'locale',
    ];

    public function settings()
    {
        $all = Setting::values();
        $values = array_intersect_key($all, array_flip(self::PUBLIC_SETTING_KEYS));

        // Ubah path berkas tersimpan menjadi URL penuh (dan buang jika file hilang).
        // Setting punya satu baris per key, jadi ambil model per key bertipe file
        // untuk cek media library-nya, baru jatuh balik ke path string lama.
        foreach (['logo', 'favicon', 'og_image', 'principal_photo'] as $key) {
            if (empty($values[$key])) {
                continue;
            }

            // principal_photo tampil besar (panel foto sambutan kepala madrasah),
            // jadi pakai file asli, bukan conversion 'thumb' 400x400 yang dipakai
            // logo/favicon/og_image — else foto akan blur saat direntang.
            $conversion = $key === 'principal_photo' ? null : 'thumb';

            $setting = Setting::where('key', $key)->first();
            $values[$key] = $setting
                ? $this->mediaUrl($setting, 'file', $values[$key], $conversion)
                : $this->legacyUrl($values[$key]);
        }

        return array_filter($values, fn ($v) => $v !== null);
    }
}
