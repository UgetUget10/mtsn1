<x-filament-panels::page>
    {{--
        Shell kanvas visual (Phase 1). Isi sesungguhnya dirender oleh SPA React
        di canvas-editor/ (build ke storage/app/public/canvas-editor/assets/*,
        reachable lewat symlink public/storage bawaan Laravel). Sengaja BUKAN
        public/canvas-editor langsung — reverse proxy Apache/nginx proyek ini
        (lihat laragon/etc/apache2/sites-enabled/000-mtsn1.conf) cuma
        meneruskan path tetap (/api, /admin, /storage, dst) ke Laravel; path
        lain jatuh ke catch-all Next.js dan 404. /storage sudah diproxy.
        Data awal disuntik lewat atribut data-* di bawah, bukan Blade @json
        langsung ke script inline, supaya bundle SPA tetap bisa di-cache
        statis oleh browser terlepas dari halaman mana yang membukanya.
    --}}
    <div
        id="canvas-root"
        data-page-id="{{ $record->id }}"
        data-page-slug="{{ $record->slug }}"
        data-tree-url="{{ url("/admin/api/pages/{$record->slug}/tree") }}"
        data-preview-url="{{ rtrim((string) env('FRONTEND_URL', ''), '/') }}/api/preview?type=page&slug={{ $record->slug }}&token={{ $record->preview_token }}"
        data-csrf-token="{{ csrf_token() }}"
        class="min-h-[70vh] w-full"
    ></div>

    <link rel="stylesheet" href="{{ asset('storage/canvas-editor/assets/canvas-editor.css') }}">
    <script type="module" src="{{ asset('storage/canvas-editor/assets/canvas-editor.js') }}"></script>
</x-filament-panels::page>
