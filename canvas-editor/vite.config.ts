import { defineConfig } from "vite";
import react from "@vitejs/plugin-react";

/**
 * Build satu bundle statis (JS+CSS bernama tetap, bukan hashed) yang dimuat
 * langsung oleh resources/views/filament/pages/page-canvas-editor.blade.php
 * lewat <script type="module"> — nama file harus tetap "canvas-editor.js"/
 * ".css" karena Blade merujuknya secara harfiah, bukan lewat manifest.
 *
 * Output SENGAJA ditulis ke storage/app/public/ (bukan public/ langsung) —
 * reverse proxy Apache/nginx proyek ini hanya meneruskan path tetap
 * (/api, /admin, /storage, /livewire, /filament, dst) ke Laravel; path lain
 * jatuh ke catch-all Next.js. /storage sudah diproxy dan sudah punya symlink
 * bawaan Laravel (public/storage -> storage/app/public), jadi menaruh bundle
 * di sini membuatnya otomatis reachable tanpa perlu ubah config server.
 */
export default defineConfig({
  plugins: [react()],
  build: {
    outDir: "../backend/storage/app/public/canvas-editor/assets",
    emptyOutDir: true,
    rollupOptions: {
      input: "src/main.tsx",
      output: {
        entryFileNames: "canvas-editor.js",
        assetFileNames: (info) =>
          info.name?.endsWith(".css") ? "canvas-editor.css" : "[name][extname]",
      },
    },
  },
});
