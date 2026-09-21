import Image from "next/image";
import Link from "next/link";
import { IconTile } from "@/components/ui";
import type { CardGridBlockData } from "@/lib/types";

const colsClass: Record<number, string> = {
  2: "sm:grid-cols-2",
  3: "sm:grid-cols-2 lg:grid-cols-3",
  4: "sm:grid-cols-2 lg:grid-cols-4",
};

export function CardGridBlock({ data }: { data: CardGridBlockData }) {
  return (
    <div>
      {data.heading && <h2 className="text-h2 heading-rule mb-6">{data.heading}</h2>}
      <div className={`grid gap-4 ${colsClass[data.columns] ?? colsClass[2]}`}>
        {data.cards.map((card) => {
          const content = (
            <>
              {card.image ? (
                <div className="relative mb-3 aspect-16/10 overflow-hidden rounded-xl bg-surface-muted">
                  <Image src={card.image} alt="" fill className="object-cover" />
                </div>
              ) : (
                card.icon && <IconTile path={card.icon} size="lg" />
              )}
              <h3 className="text-h3 mt-3">{card.title}</h3>
              {card.description && (
                <p className="mt-1.5 text-sm leading-relaxed text-ink-muted">{card.description}</p>
              )}
            </>
          );

          return card.href ? (
            <Link key={card.title} href={card.href} className="group card card-hover h-full p-6">
              {content}
            </Link>
          ) : (
            <div key={card.title} className="card h-full p-6">
              {content}
            </div>
          );
        })}
      </div>
    </div>
  );
}
