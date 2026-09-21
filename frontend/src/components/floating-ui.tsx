"use client";

import dynamic from "next/dynamic";

// Elemen mengambang tidak dibutuhkan untuk first paint / SEO —
// dimuat setelah halaman interaktif agar bundel awal lebih ringan.
const QuickDock = dynamic(() => import("./features").then((m) => m.QuickDock), { ssr: false });
const HelpBot = dynamic(() => import("./features-help").then((m) => m.HelpBot), { ssr: false });
const A11yToolbar = dynamic(() => import("./features-more").then((m) => m.A11yToolbar), {
  ssr: false,
});

export function FloatingUI({
  whatsapp,
  ppdbUrl,
  phone,
  address,
  mapsUrl,
}: {
  whatsapp?: string;
  ppdbUrl?: string;
  phone?: string;
  address?: string;
  mapsUrl?: string;
}) {
  return (
    <>
      <QuickDock whatsapp={whatsapp} ppdbUrl={ppdbUrl} phone={phone} mapsUrl={mapsUrl} />
      <HelpBot ppdbUrl={ppdbUrl} whatsapp={whatsapp} phone={phone} address={address} />
      <A11yToolbar />
    </>
  );
}
