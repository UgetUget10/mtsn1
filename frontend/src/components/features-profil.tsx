"use client";

import { useState } from "react";
import { Reveal } from "@/components/motion";

/* ============================================================
   Timeline sejarah madrasah
   ============================================================ */

export type HistoryItem = { year: string; title: string; text: string };

export function HistoryTimeline({ items }: { items: HistoryItem[] }) {
  if (items.length === 0) return null;
  return (
    <ol className="relative ml-3 border-l-2 border-brand-light">
      {items.map((it, i) => (
        <Reveal key={it.year + i} as="li" delay={i * 80} direction="right" className="relative mb-8 pl-8 last:mb-0">
          <span className="absolute -left-[9px] top-1 grid h-4 w-4 place-items-center rounded-full border-2 border-brand bg-surface">
            <span className="h-1.5 w-1.5 rounded-full bg-brand" />
          </span>
          <span className="inline-flex rounded-md bg-brand-light px-2 py-0.5 text-xs font-extrabold tracking-wide text-brand-dark">
            {it.year}
          </span>
          <h3 className="mt-2 text-lg font-bold text-foreground">{it.title}</h3>
          <p className="mt-1 text-sm leading-relaxed text-ink-muted">{it.text}</p>
        </Reveal>
      ))}
    </ol>
  );
}

/* ============================================================
   Struktur organisasi interaktif (bagan yang bisa dibuka-tutup)
   ============================================================ */

export type OrgNode = { name: string; role: string; children?: OrgNode[] };

function OrgBranch({ node, depth = 0 }: { node: OrgNode; depth?: number }) {
  const [open, setOpen] = useState(depth < 1);
  const hasKids = !!node.children?.length;

  return (
    <li className="relative">
      <div
        className={`flex items-center gap-3 rounded-xl border border-border bg-surface p-3 transition ${
          hasKids ? "cursor-pointer hover:border-brand" : ""
        }`}
        onClick={() => hasKids && setOpen((v) => !v)}
      >
        <span className={`grid h-9 w-9 shrink-0 place-items-center rounded-lg ${depth === 0 ? "bg-brand text-on-brand" : "bg-brand-light text-brand-dark"}`}>
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.9" strokeLinecap="round" strokeLinejoin="round">
            <circle cx="12" cy="8" r="3.2" />
            <path d="M5 20c0-3.3 3.1-5 7-5s7 1.7 7 5" />
          </svg>
        </span>
        <div className="min-w-0 flex-1">
          <p className="truncate font-bold text-foreground">{node.name}</p>
          <p className="truncate text-xs text-ink-muted">{node.role}</p>
        </div>
        {hasKids && (
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.6" strokeLinecap="round" strokeLinejoin="round" className={`shrink-0 text-ink-muted transition ${open ? "rotate-180" : ""}`}>
            <path d="M6 9l6 6 6-6" />
          </svg>
        )}
      </div>

      {hasKids && (
        <div className="grid transition-all duration-300 ease-out" style={{ gridTemplateRows: open ? "1fr" : "0fr" }}>
          <div className="overflow-hidden">
            <ul className="mt-2 space-y-2 border-l-2 border-border pl-4">
              {node.children!.map((c, i) => (
                <OrgBranch key={c.name + i} node={c} depth={depth + 1} />
              ))}
            </ul>
          </div>
        </div>
      )}
    </li>
  );
}

export function OrgChart({ root }: { root: OrgNode }) {
  return (
    <ul className="space-y-2">
      <OrgBranch node={root} />
    </ul>
  );
}

/* ============================================================
   Tombol unduh / cetak profil
   ============================================================ */

export function PrintButton({ label = "Unduh / Cetak" }: { label?: string }) {
  return (
    <button
      type="button"
      onClick={() => window.print()}
      className="inline-flex items-center gap-1.5 rounded-lg border border-border bg-surface px-3.5 py-2 text-sm font-semibold text-brand transition hover:border-brand hover:bg-brand-light print:hidden"
    >
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
        <path d="M6 9V2h12v7M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v8H6z" />
      </svg>
      {label}
    </button>
  );
}
