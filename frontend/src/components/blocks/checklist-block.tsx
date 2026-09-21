import type { ChecklistBlockData } from "@/lib/types";

export function ChecklistBlock({ data }: { data: ChecklistBlockData }) {
  return (
    <div>
      {data.heading && (
        <h2 className="text-h2 heading-rule mb-6">{data.heading}</h2>
      )}
      <ul className="card divide-y divide-border/70 p-2">
        {data.items.map((item) => (
          <li
            key={item.text}
            className="flex items-start gap-3 px-4 py-3.5 text-sm leading-relaxed text-ink-soft"
          >
            <span className="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full bg-brand text-on-brand">
              <svg
                width="12"
                height="12"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                strokeWidth="3.5"
                strokeLinecap="round"
                strokeLinejoin="round"
              >
                <path d="M5 13l4 4L19 7" />
              </svg>
            </span>
            <span className="flex-1">{item.text}</span>
          </li>
        ))}
      </ul>
    </div>
  );
}
