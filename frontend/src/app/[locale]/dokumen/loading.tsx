import { Container } from "@/components/ui";
import { Skel } from "@/components/skeleton";

export default function Loading() {
  return (
    <>
      <section className="mesh border-b border-border">
        <Container className="py-14 sm:py-20">
          <Skel className="h-3 w-28" />
          <Skel className="mt-4 h-9 w-72 max-w-full" />
          <Skel className="mt-4 h-4 w-full max-w-lg" />
        </Container>
      </section>
      <Container className="section-y">
        <Skel className="mb-8 h-11 w-full max-w-md rounded-xl" />
        <div className="grid gap-4 sm:grid-cols-2">
          {Array.from({ length: 6 }).map((_, i) => (
            <Skel key={i} className="h-20 rounded-xl" />
          ))}
        </div>
      </Container>
    </>
  );
}
