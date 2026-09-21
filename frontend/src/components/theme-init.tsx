"use client";

import { useEffect } from "react";

/**
 * Memulihkan atribut `data-theme` pada <html> dari localStorage.
 *
 * Kenapa perlu: skrip inline di <head> (layout.tsx) hanya dieksekusi browser
 * saat load dokumen penuh. Ganti bahasa lewat LanguageSwitcher = navigasi
 * client-side yang me-remount root layout ([locale] adalah root param),
 * sehingga <html> dirender ulang TANPA data-theme dan skrip <head> TIDAK
 * jalan lagi. Tanpa ini, tema "loncat" ke preferensi sistem setelah ganti
 * bahasa.
 *
 * Komponen ini ikut di-remount bersama layout, jadi efeknya jalan lagi tiap
 * pindah locale. `useEffect` cukup: pergantian locale memicu navigasi penuh
 * sehingga sedikit koreksi setelah paint tidak terlihat sebagai flicker
 * (berbeda dengan hydrasi awal yang ditangani skrip <head>).
 */
export function ThemeInit() {
  useEffect(() => {
    try {
      const t = localStorage.getItem("mtsn1-theme");
      const el = document.documentElement;
      if (t === "dark" || t === "light") {
        if (el.getAttribute("data-theme") !== t) el.setAttribute("data-theme", t);
      } else if (el.hasAttribute("data-theme")) {
        // Tidak ada preferensi tersimpan → ikut sistem, lepas atribut.
        el.removeAttribute("data-theme");
      }
    } catch {
      /* localStorage tidak tersedia */
    }
  });

  return null;
}
