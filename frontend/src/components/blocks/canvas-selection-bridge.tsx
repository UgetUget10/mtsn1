"use client";

import { useEffect } from "react";
import { applyBaseStyleToElement } from "@/lib/style-engine";
import type { TreeNodeStyle } from "@/lib/types";

/**
 * Jembatan klik-untuk-pilih + live style patch antara preview (iframe kanan
 * kanvas visual) dan SPA React (canvas-editor/, panel kiri) — dipasang hanya
 * di jalur TreeRenderer (lihat block-renderer.tsx), yang HANYA aktif untuk
 * pratinjau kanvas, tidak pernah untuk halaman publik biasa (PageResource
 * publik tidak pernah mengirim `tree`).
 *
 * Tidak melakukan apa pun bila halaman ini dibuka bukan di dalam iframe
 * (window.self === window.top) — jadi aman dipasang tanpa syarat tambahan
 * dari sisi pemanggil.
 */
export function CanvasSelectionBridge() {
  useEffect(() => {
    if (typeof window === "undefined" || window.self === window.top) return;

    function onClick(e: MouseEvent) {
      const target = (e.target as HTMLElement)?.closest("[data-node-id]");
      if (!target) return;

      // Jangan tangkap klik di dalam elemen interaktif milik widget itu
      // sendiri (link, tombol) — biarkan berperilaku normal di preview;
      // seleksi tetap jalan lewat elemen pembungkus (data-node-id ada di
      // section/column/widget wrapper, bukan di dalam konten widget).
      const nodeId = target.getAttribute("data-node-id");
      if (!nodeId) return;

      e.preventDefault();
      window.parent.postMessage({ source: "mtsn1-preview", type: "node-clicked", nodeId }, "*");
    }

    document.addEventListener("click", onClick, { capture: true });
    return () => document.removeEventListener("click", onClick, { capture: true });
  }, []);

  useEffect(() => {
    if (typeof window === "undefined" || window.self === window.top) return;

    let lastHighlighted: Element | null = null;

    function onMessage(e: MessageEvent) {
      if (e.data?.source !== "mtsn1-canvas") return;

      if (e.data.type === "highlight-node") {
        lastHighlighted?.classList.remove("mtsn1-preview-selected");
        lastHighlighted = null;

        const nodeId = e.data.nodeId as string | null;
        if (!nodeId) return;

        const el = document.querySelector(`[data-node-id="${CSS.escape(nodeId)}"]`);
        el?.classList.add("mtsn1-preview-selected");
        lastHighlighted = el;
        return;
      }

      if (e.data.type === "patch-style") {
        // Style-only autosave (App.tsx: isSameStructure()+diffStyles()) —
        // terapkan langsung ke DOM tanpa reload halaman ini. Hanya menutupi
        // breakpoint `base` (satu-satunya yang diekspos StylePanel saat ini,
        // lihat components/StylePanel.tsx) — kalau nanti kontrol per-
        // breakpoint (md/lg/xl) diekspos, perubahan itu tetap harus lewat
        // reload karena disuntik sebagai <style> ber-media-query terpisah
        // (compileResponsiveCss), bukan style inline yang bisa di-patch di sini.
        const changes = e.data.changes as { nodeId: string; style: TreeNodeStyle | undefined }[] | undefined;
        for (const change of changes ?? []) {
          const el = document.querySelector<HTMLElement>(`[data-node-id="${CSS.escape(change.nodeId)}"]`);
          if (el) applyBaseStyleToElement(el, change.style);
        }
      }
    }

    window.addEventListener("message", onMessage);
    return () => window.removeEventListener("message", onMessage);
  }, []);

  return null;
}
