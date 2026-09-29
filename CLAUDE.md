# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

Website for MTsN 1 Kota Malang — a two-part monorepo (see `README.md` for full dev/deploy setup, which is kept up to date and should be your first read):

| Folder | Stack | Role |
|---|---|---|
| `backend/` | Laravel 13 + Filament 5 + MySQL | CMS/admin panel + REST API (`/api/v1`) |
| `frontend/` | Next.js 16 (App Router) + Tailwind 4 | Public site (SSR/ISR, SEO) |
| `canvas-editor/` | React + Vite + dnd-kit | Standalone SPA for the visual drag-and-drop page builder, embedded inside a Filament page |

`backend` and `frontend` are two independently-running processes (`:8000` and `:3000`) stitched together in dev by an Apache reverse proxy on `mtsn1.test` (see README "Akses lewat satu domain"), and in prod by nginx + PM2 (`ecosystem.config.js`) / Docker (`docker-compose.yml`).

## Commands

From repo root (`npm run <script>` uses `concurrently` to drive both processes):

```bash
npm run dev        # Laravel :8000 + Next.js :3000 together
npm run migrate      # php backend/artisan migrate
npm run seed          # php backend/artisan db:seed
npm run build          # frontend build only
npm run lint            # frontend eslint only
npm run revalidate        # manually trigger Next.js ISR revalidation
```

Backend (`cd backend`):
```bash
php artisan test --filter=TestClassName::test_method
php artisan test tests/Feature/SomeTest.php
vendor/bin/pint
```
There's real Feature test coverage here (unlike sibling projects) — `PageBlocksTest`, `PostVisibilityTest`, `MenuApiTest`, `ArchiveFeedTest`, `PortalSettingsTest`, `ReadingSettingsTest`, `DuplicateContentTest`, `FilamentSmokeTest` — run the relevant one after touching pages/blocks, feeds, or CMS settings.

Frontend (`cd frontend`): `npm run dev` / `npm run build` / `npm run lint`.

canvas-editor (`cd canvas-editor`): `npm run build` writes straight to `../backend/storage/app/public/canvas-editor/assets/` with fixed (non-hashed) filenames `canvas-editor.js`/`.css`, because the Blade view (`backend/resources/views/filament/pages/page-canvas-editor.blade.php`) references them literally. **Must be rebuilt manually after every `src/` change** — there's no watch mode wired into it and no deploy step runs it automatically yet.

## Architecture

### Content = a page-builder block schema, shared across three layers

`backend/app/Support/Blocks/BlockTypes.php` is the single source of truth for block types (`hero`, `rich_text`, `card_grid`, `accordion`, `cta`, `gallery_block`, `stats`, `table`, `steps`, `timeline`, `reusable`, etc.). The same constant list is depended on by:
1. `App\Filament\Resources\Pages\Schemas\PageForm` — the admin Builder field for authoring
2. `App\Http\Resources\PageResource` — API transformation of block data to JSON
3. `frontend/src/components/blocks/BlockRenderer.tsx` — matches on `type` to render

Adding a new block type means touching all three. Related: `BlockDataResolver`, `StyleResolver`, `TreeNormalizer`/`TreeResolver` in the same `Support/Blocks/` namespace handle resolving nested/tree block data and per-block style options — read these before changing how blocks nest or style.

`reusable` blocks reference `App\Models\ReusableBlock` by slug (WordPress "Synced Pattern" equivalent).

### On-demand ISR revalidation, not full-site rebuilds

Every content model uses the `App\Models\Concerns\TriggersFrontendRevalidation` trait (booted hook), which POSTs `{secret, tags, paths}` to `REVALIDATE_URL` (the Next.js `/revalidate` route) whenever a model is saved/deleted — revalidating only the affected cache tags/paths, not the whole site (WordPress `clean_post_cache` equivalent). It also busts the backend's own feed cache (sitemap/RSS/iCal — see `FeedController::cacheFeed()`). Changes made via `tinker`/seeders intentionally do **not** trigger this (would flood revalidation during seeding) — trigger manually with `npm run revalidate` if needed after seeding. A model can list `$revalidationIgnoredAttributes` to skip revalidation when only those columns change (e.g. `remember_token` on login).

### Visual page builder (canvas-editor) is a separate SPA, not part of either main app

It's mounted inside Filament (`admin/pages/{slug}/canvas`) via a Blade view that loads its pre-built static JS/CSS bundle from `storage/app/public/canvas-editor/` (reachable because `php artisan storage:link` symlinks `public/storage` → `storage/app/public`, and that path is already proxied in both dev and prod configs — no server config changes needed to serve it). It's built and versioned independently of `backend`'s own asset pipeline.

### Single-domain proxying is load-bearing for local dev

Next.js dev server hot-reload (`/_next/hmr` WebSocket) and cross-origin resource blocking (`allowedDevOrigins` in `frontend/next.config.ts`) both depend on the Apache proxy config described in the README. If dark mode/dropdowns/mobile menu stop working in dev, it's almost always a `_next/*` cross-origin or missing `mod_proxy_wstunnel` issue, not app code — check the README's proxy section first.

### CMS module surface (Filament)

Berita (posts), Kategori, Halaman (pages), Guru & Tendik (staff), Agenda, Galeri, Dokumen, Slider, Prestasi, Ekstrakurikuler, Pesan Kontak, and Pengaturan Situs (site-wide settings) — each typically has a matching `App\Http\Resources\*Resource` for the public API and a frontend route/page consuming it.

## Docs

`docs/` (Indonesian session write-ups, `.docx`) covers the project history — frontend reset, CMS/Filament sessions, WordPress-parity work — useful for rationale, not authoritative for current code.
