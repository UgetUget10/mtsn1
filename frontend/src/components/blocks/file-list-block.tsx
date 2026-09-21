import type { FileListBlockData } from "@/lib/types";

export function FileListBlock({ data }: { data: FileListBlockData }) {
  const files = data.files.filter((f) => f.url);

  if (files.length === 0) return null;

  return (
    <div>
      {data.heading && <h2 className="text-h2 heading-rule mb-6">{data.heading}</h2>}
      <ul className="card divide-y divide-border overflow-hidden">
        {files.map((f) => (
          <li key={f.url}>
            <a
              href={f.download_url ?? f.url!}
              target="_blank"
              rel="noreferrer"
              className="flex items-center gap-3 p-4 transition hover:bg-surface-muted"
            >
              <span className="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-brand-light text-brand-dark">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M6 2h9l5 5v13a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2z" />
                </svg>
              </span>
              <span className="flex-1">
                <span className="block font-semibold text-foreground">{f.title}</span>
                {f.category && <span className="text-xs text-ink-muted">{f.category}</span>}
              </span>
              <span className="text-sm font-bold text-brand">Unduh</span>
            </a>
          </li>
        ))}
      </ul>
    </div>
  );
}
