import { Odometer } from "@/components/features";
import type { CounterBlockData } from "@/lib/types";

export function CounterBlock({ data }: { data: CounterBlockData }) {
  const display = `${data.prefix ?? ""}${data.value}${data.suffix ?? ""}`;

  return (
    <div className="text-center">
      <div className="text-h1 font-bold text-brand-dark">
        <Odometer value={display} />
      </div>
      <p className="mt-1.5 text-sm font-semibold text-ink-muted">{data.label}</p>
    </div>
  );
}
