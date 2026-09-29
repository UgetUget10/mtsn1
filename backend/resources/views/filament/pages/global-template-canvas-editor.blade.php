<x-filament-panels::page>
    {{-- Sama seperti page-canvas-editor.blade.php, tapi tree-url menunjuk ke
         admin/api/global-templates/{id}/tree — lihat komentar di sana untuk
         alasan symlink storage/ dan injeksi data-* alih-alih @json inline. --}}
    <div
        id="canvas-root"
        data-page-id="{{ $record->id }}"
        data-page-slug="{{ $record->slot }}"
        data-tree-url="{{ url("/admin/api/global-templates/{$record->id}/tree") }}"
        data-preview-url="{{ rtrim((string) env('FRONTEND_URL', ''), '/') }}/"
        data-csrf-token="{{ csrf_token() }}"
        class="min-h-[70vh] w-full"
    ></div>

    <link rel="stylesheet" href="{{ asset('storage/canvas-editor/assets/canvas-editor.css') }}">
    <script type="module" src="{{ asset('storage/canvas-editor/assets/canvas-editor.js') }}"></script>
</x-filament-panels::page>
