# Website MTsN 1 Kota Malang

Monorepo dua bagian:

| Folder      | Stack                                   | Peran                          |
|-------------|-----------------------------------------|-------------------------------|
| `backend/`  | Laravel 13 + Filament 5 + MySQL          | CMS/admin panel + REST API     |
| `frontend/` | Next.js 16 (App Router) + Tailwind CSS 4 | Website publik (SSR/ISR, SEO)  |

## Prasyarat

- PHP 8.3+ , Composer 2 (tersedia via Laragon)
- Node.js 20+ , npm
- MySQL 8 (database `mtsn1` sudah dibuat)

## Akses lewat satu domain: `http://mtsn1.test`

Apache Laragon dikonfigurasi sebagai reverse proxy
(`C:\laragon\etc\apache2\sites-enabled\000-mtsn1.conf`):

| Path                         | Diteruskan ke            |
|------------------------------|--------------------------|
| `/`                          | Next.js  (port 3000)     |
| `/_next/hmr` (WebSocket)      | Next.js  (hot-reload dev) |
| `/api/*`                     | Laravel  (port 8000)     |
| `/admin`, `/livewire`, aset Filament | Laravel  (port 8000)     |
| `/storage/*`                 | Laravel  (file upload)   |

Syarat sekali saja:

1. Aktifkan modul proxy Apache — sudah di-uncomment di
   `httpd.conf`: `mod_proxy`, `mod_proxy_http`, dan **`mod_proxy_wstunnel`**
   (yang terakhir wajib untuk hot-reload Next 16 — tanpa itu muncul
   `GET /_next/hmr 404` dan edit tidak auto-refresh). Backup: `httpd.conf.bak-mtsn1`,
   `httpd.conf.bak-mtsn1-ws`.
2. `frontend/next.config.ts` → `allowedDevOrigins: ["mtsn1.test", ...]` supaya
   Next 16 tidak memblokir `/_next/*` lintas origin (kalau diblokir, JS client
   tidak ter-hydrate: dark mode/dropdown/menu mati).
3. Di Laragon: **Menu → Apache → Reload** (atau Stop lalu Start) agar
   `000-mtsn1.conf` + modul wstunnel terbaca.
4. Jalankan kedua server dev. **Dari folder root `C:\laragon\www\mtsn1`:**

   ```powershell
   npm install      # sekali saja (memasang "concurrently")
   npm run dev       # menyalakan Laravel :8000 + Next.js :3000 sekaligus
   ```

   Perintah root lain: `npm run build` (build frontend), `npm run start`
   (mode produksi), `npm run migrate` / `npm run seed`, `npm run revalidate`.
   Alternatif: klik dua kali `start-dev.bat`, atau jalankan manual per folder
   (lihat di bawah). Selama keduanya jalan, `http://mtsn1.test` menampilkan
   website; `http://mtsn1.test/admin` membuka panel admin.

> Jika ingin tanpa proxy: cukup `php artisan serve` + `npm run dev`, lalu buka
> `http://localhost:3000` (website) dan `http://localhost:8000/admin` (admin).
> Samakan `frontend/.env.local` → `NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1`.

> **Penting saat pakai proxy `mtsn1.test`:** dev server Next ada di
> `localhost:3000`, sedangkan browser mengaksesnya lewat origin `mtsn1.test`.
> Next 16 memblokir resource `/_next/*` lintas origin sehingga JavaScript
> client tidak ter-hydrate (dark mode, dropdown, menu mobile jadi mati).
> Sudah diatasi di `frontend/next.config.ts` → `allowedDevOrigins`. Kalau
> domain/host proxy diubah, tambahkan host baru ke daftar itu lalu
> restart `npm run dev`.

## Menjalankan backend

```bash
cd backend
composer install
cp .env.example .env   # sudah dikonfigurasi ke MySQL "mtsn1"
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
php artisan serve        # http://127.0.0.1:8000
```

- Admin panel: `http://127.0.0.1:8000/admin`
  - Email: `admin@mtsn1.sch.id`
  - Password: `password`
- Base API: `http://127.0.0.1:8000/api/v1`

### Endpoint API utama

```
GET  /api/v1/settings
GET  /api/v1/sliders
GET  /api/v1/pages                 GET /api/v1/pages/{slug}
GET  /api/v1/posts?category=&q=&page=&per_page=&featured=
GET  /api/v1/posts/{slug}
GET  /api/v1/teachers?group=guru|tendik|pimpinan
GET  /api/v1/agendas?month=YYYY-MM
GET  /api/v1/galleries
GET  /api/v1/documents?search=
GET  /api/v1/achievements
GET  /api/v1/extracurriculars
POST /api/v1/contacts              (throttle 6/menit)
```

CORS dibatasi ke `FRONTEND_URL` di `backend/.env` (default `http://localhost:3000`).

## Menjalankan frontend

```bash
cd frontend
npm install
# .env.local sudah diisi:
#   NEXT_PUBLIC_API_URL=http://127.0.0.1:8000/api/v1
#   NEXT_PUBLIC_SITE_URL=http://localhost:3000
npm run dev             # http://localhost:3000
npm run build && npm start   # produksi
```

## Pencarian & PWA (frontend)

- **Pencarian situs**: tombol 🔍 di header (juga shortcut `/` atau `Ctrl/Cmd-K`)
  membuka panel pencarian cepat — mencari berita via `GET /api/v1/posts?search=`
  plus tautan halaman/layanan. Halaman hasil lengkap: `/pencarian?q=`.
- **PWA**: `frontend/public/manifest.webmanifest` + `frontend/public/sw.js`
  (didaftarkan `PwaRegister` hanya di `NODE_ENV=production`). Situs bisa
  di-*Add to Home Screen*; halaman yang pernah dibuka tetap terbuka saat offline,
  sisanya jatuh ke `/offline`. Path `/api`, `/admin`, `/livewire`, `/storage`
  tidak pernah di-cache. Bump `VERSION` di `sw.js` saat ingin memaksa cache baru.

## Konten tidak langsung berubah di frontend?

Frontend Next.js memakai **ISR** — halaman dirender jadi HTML statis lalu
di-cache. Sudah dipasang **revalidasi on-demand**: setiap kali data disimpan/
dihapus di panel admin, Laravel otomatis memanggil
`POST http://127.0.0.1:3000/revalidate?secret=...` (trait
`App\Models\Concerns\TriggersFrontendRevalidation` di semua model konten),
sehingga halaman langsung diperbarui.

- Secret dibagi di `backend/.env` (`REVALIDATE_URL`, `REVALIDATE_SECRET`) dan
  `frontend/.env.local` (`REVALIDATE_SECRET`).
- Perubahan lewat `php artisan tinker`/seeder **tidak** memicu revalidasi
  (sengaja, agar seeding tidak membanjiri). Picu manual:
  `curl -X POST "http://127.0.0.1:3000/revalidate?secret=mtsn1-local-revalidate"`
- Saat pengembangan tampilan, jalankan `npm run dev` (bukan `npm run start`) —
  mode dev selalu mengambil data terbaru tanpa cache.

## Modul CMS (Filament)

Berita, Kategori, Halaman (Profil), Guru & Tendik, Agenda, Galeri (+ item),
Dokumen, Slider, Prestasi, Ekstrakurikuler, Pesan Kontak, dan
**Pengaturan Situs** (nama madrasah, alamat, kontak, sosial media, sambutan
kepala, link PPDB).

## Deploy singkat

- **Backend**: VPS (Nginx + PHP-FPM + MySQL) atau Laravel Forge. Jalankan
  `php artisan migrate --force`, `php artisan config:cache`, queue worker untuk email.
- **Frontend**: Vercel (paling mudah) atau VPS Node + PM2. Set
  `NEXT_PUBLIC_API_URL` & `NEXT_PUBLIC_SITE_URL` ke domain produksi (HTTPS),
  lalu tambahkan domain frontend ke `FRONTEND_URL` backend.
