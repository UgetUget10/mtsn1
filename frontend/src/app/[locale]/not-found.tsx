import { Button, Container } from "@/components/ui";
import { getDictionary } from "@/dictionaries";

export default async function NotFound() {
  const dict = await getDictionary();

  return (
    <Container className="flex min-h-[62vh] flex-col items-center justify-center py-24 text-center">
      <span className="grid h-14 w-14 place-items-center rounded-2xl bg-brand-light text-brand">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round">
          <circle cx="11" cy="11" r="7" />
          <path d="m21 21-4.3-4.3M8 11h6" />
        </svg>
      </span>
      <p className="mt-6 text-xs font-bold uppercase tracking-[0.2em] text-brand">Error 404</p>
      <h1 className="text-h2 mt-2">{dict.notFound.title}</h1>
      <p className="mt-3 max-w-md text-lead">{dict.notFound.description}</p>
      <div className="mt-8 flex flex-wrap justify-center gap-3">
        <Button href="/" size="lg">
          {dict.notFound.cta}
        </Button>
        <Button href="/berita" size="lg" variant="outline">
          {dict.notFound.viewNews}
        </Button>
      </div>
    </Container>
  );
}
