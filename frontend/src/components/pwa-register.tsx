"use client";

import { useEffect } from "react";

/**
 * Mendaftarkan service worker (public/sw.js) di produksi.
 * Di mode dev, service worker lama justru di-unregister supaya HMR tidak
 * terganggu cache.
 */
export function PwaRegister() {
  useEffect(() => {
    if (!("serviceWorker" in navigator)) return;

    if (process.env.NODE_ENV !== "production") {
      // Buang service worker + cache lama dari build produksi yang mungkin
      // masih menyajikan bundel usang saat pengembangan.
      navigator.serviceWorker.getRegistrations().then((rs) => rs.forEach((r) => r.unregister()));
      if (typeof caches !== "undefined") {
        caches.keys().then((keys) => keys.forEach((k) => caches.delete(k)));
      }
      return;
    }

    const onLoad = () => {
      navigator.serviceWorker.register("/sw.js").catch(() => {
        /* abaikan kegagalan registrasi */
      });
    };
    window.addEventListener("load", onLoad);
    return () => window.removeEventListener("load", onLoad);
  }, []);

  return null;
}
