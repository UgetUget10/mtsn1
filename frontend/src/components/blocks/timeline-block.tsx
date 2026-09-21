import type { TimelineBlockData } from "@/lib/types";

export function TimelineBlock({ data }: { data: TimelineBlockData }) {
  return (
    <div>
      {data.heading && (
        <h2 className="text-h2 heading-rule mb-6">{data.heading}</h2>
      )}
      <div className="card divide-y divide-border overflow-hidden">
        {data.items.map((item, i) => (
          <div
            key={i}
            className="flex flex-col gap-1 p-4 transition hover:bg-surface-muted sm:flex-row sm:gap-5"
          >
            <span className="shrink-0 text-sm font-extrabold tabular-nums text-brand sm:w-36">
              {item.label}
            </span>
            <span className="text-sm leading-relaxed text-ink-soft">
              {item.title && (
                <span className="mr-1 font-semibold text-foreground">
                  {item.title}
                  {item.description ? " —" : ""}
                </span>
              )}
              {item.description}
            </span>
          </div>
        ))}
      </div>
    </div>
  );
}
