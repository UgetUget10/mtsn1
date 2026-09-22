import Image from "@/components/media-image";
import { Button } from "@/components/ui";
import type { HeroBlockData } from "@/lib/types";

export function HeroBlock({ data }: { data: HeroBlockData }) {
  return (
    <div className="mesh card relative overflow-hidden p-8 sm:p-11">
      {data.image && (
        <div className="absolute inset-0 -z-10">
          <Image
            src={data.image}
            alt=""
            fill
            className="object-cover opacity-15"
          />
        </div>
      )}

      {/* Ornamen geometris samar di pojok — memberi bobot "pernyataan resmi" */}
      <span
        aria-hidden
        className="pointer-events-none absolute -right-10 -top-10 h-40 w-40 rotate-12 rounded-[2rem] border-2 border-brand/15 sm:h-52 sm:w-52"
      />
      <span
        aria-hidden
        className="pointer-events-none absolute -right-4 top-8 hidden h-24 w-24 rotate-45 rounded-2xl border-2 border-accent/15 sm:block"
      />

      <div className="relative max-w-3xl">
        {data.eyebrow && <p className="kicker">{data.eyebrow}</p>}
        <h2 className="text-h2 heading-rule mt-2 text-balance">{data.title}</h2>
        {data.subtitle && (
          <p className="mt-4 text-lead text-pretty">{data.subtitle}</p>
        )}
        {data.cta_label && data.cta_href && (
          <Button href={data.cta_href} className="mt-6 w-fit">
            {data.cta_label}
          </Button>
        )}
      </div>
    </div>
  );
}
