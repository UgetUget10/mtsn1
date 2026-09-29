import Link from "next/link";
import { IconTile } from "@/components/ui";
import type { IconBoxBlockData } from "@/lib/types";

export function IconBoxBlock({ data }: { data: IconBoxBlockData }) {
  const content =
    data.layout === "inline" ? (
      <div className="flex items-start gap-4">
        {data.icon && <IconTile path={data.icon} size="lg" interactive={Boolean(data.href)} />}
        <div>
          <h3 className="text-h3">{data.title}</h3>
          {data.description && <p className="mt-1.5 text-sm leading-relaxed text-ink-muted">{data.description}</p>}
        </div>
      </div>
    ) : (
      <div className="text-center">
        {data.icon && (
          <div className="mb-3 flex justify-center">
            <IconTile path={data.icon} size="lg" interactive={Boolean(data.href)} />
          </div>
        )}
        <h3 className="text-h3">{data.title}</h3>
        {data.description && <p className="mt-1.5 text-sm leading-relaxed text-ink-muted">{data.description}</p>}
      </div>
    );

  return data.href ? (
    <Link href={data.href} className="group block">
      {content}
    </Link>
  ) : (
    content
  );
}
