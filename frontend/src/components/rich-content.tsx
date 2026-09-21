"use client";

import { useEffect, useRef } from "react";

/**
 * Merender HTML rich text (dari editor Filament, sudah dilewatkan AutoEmbed di
 * backend) dan memuat skrip widget resmi untuk sematan berbasis <blockquote>
 * ala WordPress — Instagram, X/Twitter, TikTok — yang tidak dieksekusi oleh
 * dangerouslySetInnerHTML.
 *
 * Sematan berbasis <iframe> (YouTube, Vimeo, Spotify, Google Maps) tidak
 * memerlukan skrip apa pun dan tampil langsung.
 */
const WIDGETS: { match: string; src: string; ping?: () => void }[] = [
  {
    match: "twitter-tweet",
    src: "https://platform.twitter.com/widgets.js",
    ping: () => window.twttr?.widgets?.load?.(),
  },
  {
    match: "instagram-media",
    src: "https://www.instagram.com/embed.js",
    ping: () => window.instgrm?.Embeds?.process?.(),
  },
  {
    match: "tiktok-embed",
    src: "https://www.tiktok.com/embed.js",
  },
];

function ensureScript(src: string): HTMLScriptElement {
  const existing = document.querySelector<HTMLScriptElement>(
    `script[src="${src}"]`,
  );
  if (existing) return existing;
  const s = document.createElement("script");
  s.src = src;
  s.async = true;
  document.body.appendChild(s);
  return s;
}

export function RichContent({
  html,
  className = "",
}: {
  html: string;
  className?: string;
}) {
  const ref = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const root = ref.current;
    if (!root) return;

    for (const w of WIDGETS) {
      if (!root.querySelector(`.${w.match}`)) continue;
      const s = ensureScript(w.src);
      // Skrip sudah dimuat sebelumnya → panggil ulang prosesornya.
      if (s.dataset.loaded === "1") {
        w.ping?.();
      } else {
        s.addEventListener("load", () => {
          s.dataset.loaded = "1";
          w.ping?.();
        });
      }
    }
  }, [html]);

  return (
    <div
      ref={ref}
      className={`prose-content ${className}`}
      dangerouslySetInnerHTML={{ __html: html }}
    />
  );
}

declare global {
  interface Window {
    twttr?: { widgets?: { load?: () => void } };
    instgrm?: { Embeds?: { process?: () => void } };
  }
}
