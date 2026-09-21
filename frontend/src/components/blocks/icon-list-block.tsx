import type { IconListBlockData } from "@/lib/types";

export function IconListBlock({ data }: { data: IconListBlockData }) {
  return (
    <div className="card p-6">
      {data.heading && <h3 className="text-h3">{data.heading}</h3>}
      <ul className={`space-y-2 text-sm text-ink-soft ${data.heading ? "mt-3" : ""}`}>
        {data.items.map((item) => (
          <li key={item.text} className="flex gap-2">
            <span className="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-brand" />
            {item.text}
          </li>
        ))}
      </ul>
    </div>
  );
}
