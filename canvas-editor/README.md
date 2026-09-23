# canvas-editor

SPA React untuk Kanvas Visual (Page Builder Phase 1) — dimuat oleh
`backend/resources/views/filament/pages/page-canvas-editor.blade.php` di
dalam panel Filament (`admin/pages/{slug}/canvas`).

## Build

```
npm install
npm run build
```

Output ditulis ke `../backend/storage/app/public/canvas-editor/assets/`
(`canvas-editor.js` + `canvas-editor.css`, nama file tetap/tidak di-hash,
karena Blade merujuknya secara harfiah) — BUKAN `public/canvas-editor`
langsung. Reverse proxy Apache/nginx proyek ini (lihat
`laragon/etc/apache2/sites-enabled/000-mtsn1.conf` untuk dev, atau
`nginx/*.conf` untuk produksi) hanya meneruskan path tetap (`/api`, `/admin`,
`/storage`, `/livewire`, `/filament`, dst) ke Laravel — path lain jatuh ke
catch-all Next.js dan 404. `/storage` sudah diproxy dan sudah punya symlink
bawaan Laravel (`public/storage` → `storage/app/public`, dibuat oleh
`php artisan storage:link`), jadi menaruh bundle di sini membuatnya otomatis
reachable tanpa perlu ubah config server.

**Prasyarat:** `php artisan storage:link` sudah pernah dijalankan di server
(biasanya sudah, karena upload media lain juga bergantung padanya).

**Wajib dijalankan ulang setiap kali mengubah source di `src/`** — tidak ada
watch/hot-reload di produksi, dan belum ada langkah build ini di proses
deploy otomatis backend (lihat catatan di rencana Phase 1) — jalankan manual
setelah `git pull` di server.
