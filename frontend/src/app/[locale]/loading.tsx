import { Container } from "@/components/ui";

export default function Loading() {
  return (
    <Container className="py-16">
      <div className="h-8 w-56 animate-pulse rounded bg-surface-muted" />
      <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {Array.from({ length: 6 }).map((_, i) => (
          <div key={i} className="overflow-hidden rounded-xl border border-border bg-surface">
            <div className="aspect-16/10 animate-pulse bg-surface-muted" />
            <div className="space-y-3 p-4">
              <div className="h-4 w-3/4 animate-pulse rounded bg-surface-muted" />
              <div className="h-3 w-full animate-pulse rounded bg-surface-muted" />
              <div className="h-3 w-2/3 animate-pulse rounded bg-surface-muted" />
            </div>
          </div>
        ))}
      </div>
    </Container>
  );
}
