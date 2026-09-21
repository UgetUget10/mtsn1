{{--
    Tampilan diff revisi — setara layar "Compare revisions" WordPress.
    Kata yang dihapus ditandai merah (<del>), yang ditambahkan hijau (<ins>).
    $rows berasal dari App\Support\Revisions\RevisionDiff::against().
--}}
<div class="space-y-4">
    <div class="flex flex-wrap items-center gap-3 text-sm">
        <span class="inline-flex items-center gap-1.5">
            <span class="inline-block h-3 w-3 rounded-sm bg-danger-100 dark:bg-danger-500/30"></span>
            <span class="text-gray-600 dark:text-gray-400">Dihapus (versi lama)</span>
        </span>
        <span class="inline-flex items-center gap-1.5">
            <span class="inline-block h-3 w-3 rounded-sm bg-success-100 dark:bg-success-500/30"></span>
            <span class="text-gray-600 dark:text-gray-400">Ditambahkan (versi sekarang)</span>
        </span>
    </div>

    @foreach ($rows as $row)
        <div class="rounded-lg border border-gray-200 dark:border-white/10">
            <div class="flex items-center justify-between gap-2 border-b border-gray-200 px-4 py-2 dark:border-white/10">
                <span class="font-semibold text-gray-900 dark:text-white">{{ $row['label'] }}</span>
                @if ($row['changed'])
                    <span class="rounded-full bg-warning-100 px-2 py-0.5 text-xs font-medium text-warning-700 dark:bg-warning-500/20 dark:text-warning-400">
                        Berubah
                    </span>
                @else
                    <span class="text-xs text-gray-500 dark:text-gray-400">Sama</span>
                @endif
            </div>
            <div class="revision-diff px-4 py-3 text-sm leading-relaxed text-gray-800 dark:text-gray-200">
                {!! $row['html'] !!}
            </div>
        </div>
    @endforeach
</div>

<style>
    .revision-diff del {
        background-color: rgb(254 226 226);
        color: rgb(153 27 27);
        text-decoration: line-through;
        border-radius: 0.2rem;
        padding: 0 0.15rem;
    }
    .revision-diff ins {
        background-color: rgb(220 252 231);
        color: rgb(22 101 52);
        text-decoration: none;
        border-radius: 0.2rem;
        padding: 0 0.15rem;
    }
    .dark .revision-diff del {
        background-color: rgb(153 27 27 / 0.35);
        color: rgb(254 202 202);
    }
    .dark .revision-diff ins {
        background-color: rgb(22 101 52 / 0.35);
        color: rgb(187 247 208);
    }
</style>
