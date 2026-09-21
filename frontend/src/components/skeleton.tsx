import { Container } from "@/components/ui";

/** Blok placeholder dengan sapuan cahaya. */
export function Skel({ className = "" }: { className?: string }) {
  return <div className={`skel ${className}`} />;
}

/** Kerangka satu kartu berita. */
export function CardSkeleton() {
  return (
    <div className="card overflow-hidden">
      <Skel className="aspect-16/10 rounded-none" />
      <div className="space-y-3 p-5">
        <Skel className="h-4 w-3/4" />
        <Skel className="h-3 w-full" />
        <Skel className="h-3 w-2/3" />
        <Skel className="h-3 w-24" />
      </div>
    </div>
  );
}

/** Kerangka header halaman + grid kartu. */
export function ListPageSkeleton({ cards = 6 }: { cards?: number }) {
  return (
    <>
      <section className="border-b border-border bg-surface-muted">
        <Container className="section-y">
          <Skel className="h-3 w-24" />
          <Skel className="mt-4 h-9 w-2/3 max-w-md" />
          <Skel className="mt-4 h-4 w-full max-w-lg" />
        </Container>
      </section>
      <Container className="section-y">
        <Skel className="mb-8 h-11 w-full max-w-sm rounded-full" />
        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          {Array.from({ length: cards }).map((_, i) => (
            <CardSkeleton key={i} />
          ))}
        </div>
      </Container>
    </>
  );
}
