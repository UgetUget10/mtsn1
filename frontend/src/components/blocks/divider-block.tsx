import type { DividerBlockData } from "@/lib/types";

const borderStyleClass: Record<DividerBlockData["style"], string> = {
  solid: "border-solid",
  dashed: "border-dashed",
  dotted: "border-dotted",
};

export function DividerBlock({ data }: { data: DividerBlockData }) {
  if (!data.label) {
    return <hr className={`border-t border-border ${borderStyleClass[data.style] ?? borderStyleClass.solid}`} />;
  }

  return (
    <div className="flex items-center gap-4" role="separator">
      <hr className={`flex-1 border-t border-border ${borderStyleClass[data.style] ?? borderStyleClass.solid}`} />
      <span className="text-xs font-semibold uppercase tracking-wide text-ink-muted">{data.label}</span>
      <hr className={`flex-1 border-t border-border ${borderStyleClass[data.style] ?? borderStyleClass.solid}`} />
    </div>
  );
}
