import type { QuoteBlockData } from "@/lib/types";

export function QuoteBlock({ data }: { data: QuoteBlockData }) {
  return (
    <blockquote className="card relative overflow-hidden p-7 sm:p-9">
      <span
        aria-hidden
        className="pointer-events-none absolute -right-3 -top-6 select-none font-serif text-[9rem] leading-none text-brand/10 sm:text-[12rem]"
      >
        &rdquo;
      </span>
      <div
        className="relative measure prose-content text-lg font-medium leading-relaxed text-foreground sm:text-xl"
        dangerouslySetInnerHTML={{ __html: data.text }}
      />
      {data.attribution && (
        <p className="relative mt-4 text-sm font-semibold text-ink-muted">— {data.attribution}</p>
      )}
    </blockquote>
  );
}
