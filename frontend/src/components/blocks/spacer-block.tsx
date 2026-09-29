import type { SpacerBlockData } from "@/lib/types";

const heightClass: Record<SpacerBlockData["height"], string> = {
  sm: "h-6",
  md: "h-12",
  lg: "h-20",
  xl: "h-32",
};

export function SpacerBlock({ data }: { data: SpacerBlockData }) {
  return <div aria-hidden className={heightClass[data.height] ?? heightClass.md} />;
}
