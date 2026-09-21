import type { Metadata } from "next";
import { Container, PageHeader } from "@/components/ui";
import { UnsubscribeClient } from "./unsubscribe-client";

export const dynamic = "force-dynamic";

export const metadata: Metadata = {
  title: "Berhenti langganan balasan komentar",
  robots: { index: false, follow: false },
};

export default async function UnsubscribePage({
  searchParams,
}: PageProps<"/[locale]/berita/komentar/berhenti-langganan">) {
  const sp = await searchParams;
  const token = typeof sp.token === "string" ? sp.token : "";

  return (
    <>
      <PageHeader
        eyebrow="Komentar"
        title="Berhenti langganan balasan"
        subtitle="Kelola pemberitahuan email untuk balasan komentar Anda."
        breadcrumb={[
          { label: "Berita", href: "/berita" },
          { label: "Berhenti langganan" },
        ]}
      />
      <Container className="section-y">
        <div className="max-w-xl">
          <UnsubscribeClient token={token} />
        </div>
      </Container>
    </>
  );
}
