"use client";

import { useState } from "react";
import type { TabsBlockData } from "@/lib/types";

export function TabsBlock({ data }: { data: TabsBlockData }) {
  const [active, setActive] = useState(0);
  const items = data.items;

  if (items.length === 0) return null;

  return (
    <div>
      <div role="tablist" className="flex flex-wrap gap-2 border-b border-border">
        {items.map((item, i) => (
          <button
            key={item.title}
            type="button"
            role="tab"
            aria-selected={i === active}
            onClick={() => setActive(i)}
            className={`-mb-px border-b-2 px-4 py-2 text-sm font-semibold transition ${
              i === active
                ? "border-brand text-brand-dark"
                : "border-transparent text-ink-muted hover:text-brand-dark"
            }`}
          >
            {item.title}
          </button>
        ))}
      </div>
      <div role="tabpanel" className="prose-content pt-5 text-sm text-ink-soft" dangerouslySetInnerHTML={{ __html: items[active].content }} />
    </div>
  );
}
