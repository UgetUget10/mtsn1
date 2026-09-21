import type { LinkCardsBlockData } from "@/lib/types";

export function LinkCardsBlock({ data }: { data: LinkCardsBlockData }) {
  return (
    <div>
      {data.heading && <h2 className="text-h2 heading-rule mb-6">{data.heading}</h2>}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        {data.items.map((c) => (
          <a
            key={c.title}
            href={c.href}
            target={c.external ? "_blank" : undefined}
            rel={c.external ? "noreferrer" : undefined}
            className="group card card-hover flex flex-col p-6"
          >
            <h3 className="flex items-center gap-1.5 text-h3 group-hover:text-brand">
              {c.title}
              {c.external && (
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.6" strokeLinecap="round" strokeLinejoin="round" className="opacity-60">
                  <path d="M7 17 17 7M8 7h9v9" />
                </svg>
              )}
            </h3>
            {c.description && <p className="mt-1.5 text-sm text-ink-muted">{c.description}</p>}
          </a>
        ))}
      </div>
    </div>
  );
}
