import type { AccordionBlockData } from "@/lib/types";

export function AccordionBlock({ data }: { data: AccordionBlockData }) {
  return (
    <div>
      {data.heading && <h2 className="text-h2 heading-rule mb-6">{data.heading}</h2>}
      <div className="space-y-3">
        {data.items.map((item) => (
          <details key={item.question} className="card group p-5 open:pb-6">
            <summary className="flex cursor-pointer list-none items-center justify-between gap-4 font-bold text-foreground">
              {item.question}
              <span className="shrink-0 text-brand transition-transform group-open:rotate-45">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2.4" strokeLinecap="round" strokeLinejoin="round">
                  <path d="M12 5v14M5 12h14" />
                </svg>
              </span>
            </summary>
            <div
              className="prose-content mt-3 text-sm text-ink-soft"
              dangerouslySetInnerHTML={{ __html: item.answer }}
            />
          </details>
        ))}
      </div>
    </div>
  );
}
