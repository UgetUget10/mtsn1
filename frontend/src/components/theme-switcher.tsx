"use client";

import { useSyncExternalStore } from "react";

/**
 * Peralih tema terang / gelap (ikon, dipasang di header).
 * - Pilihan disimpan di localStorage ("mtsn1-theme": "light" | "dark").
 * - Tanpa pilihan tersimpan, mengikuti preferensi sistem.
 * - Skrip kecil di <head> (layout.tsx) menerapkan pilihan sebelum paint saat
 *   full page load; ThemeInit memulihkannya setelah navigasi client-side
 *   antar-locale (lihat komponen ThemeInit).
 */

const STORAGE_KEY = "mtsn1-theme";

function readTheme(): "light" | "dark" {
  if (typeof document === "undefined") return "light";
  const attr = document.documentElement.getAttribute("data-theme");
  if (attr === "light" || attr === "dark") return attr;
  return typeof window !== "undefined" &&
    window.matchMedia("(prefers-color-scheme: dark)").matches
    ? "dark"
    : "light";
}

function subscribe(onChange: () => void): () => void {
  const observer = new MutationObserver(onChange);
  observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ["data-theme"],
  });
  const mq = window.matchMedia("(prefers-color-scheme: dark)");
  mq.addEventListener("change", onChange);
  return () => {
    observer.disconnect();
    mq.removeEventListener("change", onChange);
  };
}

export function ThemeToggle({ className = "" }: { className?: string }) {
  const theme = useSyncExternalStore(subscribe, readTheme, () => "light" as const);
  const isDark = theme === "dark";

  function toggle() {
    const next = isDark ? "light" : "dark";
    document.documentElement.setAttribute("data-theme", next);
    try {
      localStorage.setItem(STORAGE_KEY, next);
    } catch {
      /* localStorage tidak tersedia */
    }
    // MutationObserver di subscribe() memicu re-render lewat store.
  }

  return (
    <button
      type="button"
      onClick={toggle}
      aria-label={isDark ? "Beralih ke mode terang" : "Beralih ke mode gelap"}
      title={isDark ? "Mode terang" : "Mode gelap"}
      className={`grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-border text-foreground transition hover:border-brand hover:text-brand ${className}`}
    >
      {isDark ? (
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round">
          <circle cx="12" cy="12" r="4" />
          <path d="M12 2v2M12 20v2M4 12H2M22 12h-2M5 5l1.5 1.5M17.5 17.5 19 19M19 5l-1.5 1.5M6.5 17.5 5 19" />
        </svg>
      ) : (
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round">
          <path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z" />
        </svg>
      )}
    </button>
  );
}
