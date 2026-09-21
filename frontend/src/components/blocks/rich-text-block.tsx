import type { RichTextBlockData } from "@/lib/types";

export function RichTextBlock({ data }: { data: RichTextBlockData }) {
  return (
    <div>
      {data.heading && <h2 className="text-h2 heading-rule mb-4">{data.heading}</h2>}
      <div
        className="prose-content text-ink-soft"
        dangerouslySetInnerHTML={{ __html: data.body }}
      />
    </div>
  );
}
