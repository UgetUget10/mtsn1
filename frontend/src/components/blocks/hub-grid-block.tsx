import { HubGrid, type HubItem } from "@/components/ui";
import type { HubGridBlockData } from "@/lib/types";

export function HubGridBlock({ data }: { data: HubGridBlockData }) {
  const items: HubItem[] = data.items.map((it) => ({
    title: it.title,
    desc: it.desc ?? "",
    href: it.href,
    icon: it.icon ?? "M9 11l3 3L22 4M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11",
  }));

  return <HubGrid items={items} numbered={data.numbered} />;
}
