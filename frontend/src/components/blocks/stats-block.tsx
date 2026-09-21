import { Icon } from "@/components/ui";
import type { StatsBlockData } from "@/lib/types";

export function StatsBlock({ data }: { data: StatsBlockData }) {
  return (
    <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
      {data.items.map((s) => (
        <div key={s.label} className="card p-5 text-center">
          {s.icon && <Icon path={s.icon} size={22} className="mx-auto mb-2 text-brand" />}
          <p className="tabular text-3xl font-black text-foreground">{s.value}</p>
          <p className="mt-1 text-xs font-semibold uppercase tracking-wide text-ink-muted">
            {s.label}
          </p>
        </div>
      ))}
    </div>
  );
}
