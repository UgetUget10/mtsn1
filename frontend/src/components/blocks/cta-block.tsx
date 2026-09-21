import { Button } from "@/components/ui";
import type { CtaBlockData } from "@/lib/types";

export function CtaBlock({ data }: { data: CtaBlockData }) {
  const external = data.button_href.startsWith("http");

  return (
    <div className="band-brand on-dark grain relative overflow-hidden rounded-2xl p-6 sm:p-9">
      <span aria-hidden className="stars" />
      <h2 className="text-h2 relative">{data.heading}</h2>
      {data.text && <p className="relative mt-2 max-w-xl text-white/85">{data.text}</p>}
      <Button
        href={data.button_href}
        variant={data.style === "outline" ? "glass" : "primary"}
        className="relative mt-6 w-fit"
        target={external ? "_blank" : undefined}
        rel={external ? "noreferrer" : undefined}
      >
        {data.button_label}
      </Button>
    </div>
  );
}
