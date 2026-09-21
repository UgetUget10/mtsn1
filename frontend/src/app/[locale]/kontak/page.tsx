import type { Metadata } from "next";
import { getSettings } from "@/lib/api";
import { PageBody, PageHeader, IconTile } from "@/components/ui";
import { ContactForm } from "./contact-form";

export const metadata: Metadata = { title: "Kontak" };
export const revalidate = 600;

const infoIcons = {
  address: "M12 21s-7-5.2-7-11a7 7 0 1 1 14 0c0 5.8-7 11-7 11zM12 10a2 2 0 1 0 0-4 2 2 0 0 0 0 4z",
  phone:
    "M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3 19.5 19.5 0 0 1-6-6 19.8 19.8 0 0 1-3-8.7A2 2 0 0 1 4.1 2H7a2 2 0 0 1 2 1.7c.1 1.2.4 2.4.8 3.5a2 2 0 0 1-.5 2.1L8 8.6a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c1.1.4 2.3.7 3.5.8A2 2 0 0 1 22 16.9z",
  email: "M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zm0 2 8 6 8-6",
};

export default async function KontakPage() {
  const settings = await getSettings().catch(() => ({}) as Record<string, string>);

  const rows = [
    ["Alamat", settings.address, infoIcons.address],
    ["Telepon", settings.phone, infoIcons.phone],
    ["Email", settings.email, infoIcons.email],
  ].filter(([, v]) => v) as [string, string, string][];

  return (
    <>
      <PageHeader
        eyebrow="Hubungi Kami"
        title="Kontak"
        subtitle="Sampaikan pertanyaan, masukan, atau permohonan informasi kepada kami."
        breadcrumb={[{ label: "Kontak" }]}
      />
      <PageBody>
        <div className="grid gap-6 lg:grid-cols-2">
          <div className="space-y-4">
            <div className="card p-6">
              <ul className="space-y-5">
                {rows.map(([label, value, icon]) => (
                  <li key={label} className="flex gap-4">
                    <IconTile path={icon} />
                    <div>
                      <p className="text-xs font-bold uppercase tracking-wide text-ink-muted">{label}</p>
                      <p className="mt-0.5 font-medium text-foreground">{value}</p>
                    </div>
                  </li>
                ))}
              </ul>
            </div>

            {settings.maps_embed && (
              <div className="overflow-hidden rounded-2xl border border-border shadow-sm">
                <iframe
                  src={settings.maps_embed}
                  className="h-72 w-full"
                  loading="lazy"
                  referrerPolicy="no-referrer-when-downgrade"
                  title="Peta lokasi"
                />
              </div>
            )}
          </div>

          <ContactForm />
        </div>
      </PageBody>
    </>
  );
}
