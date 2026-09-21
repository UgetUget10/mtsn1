import type { Metadata, Viewport } from "next";
import { Plus_Jakarta_Sans } from "next/font/google";
import "../globals.css";
import { SiteHeader } from "@/components/site-header";
import { SiteFooter } from "@/components/site-footer";
import { BackToTop, ScrollProgress } from "@/components/motion";
import { ThemeInit } from "@/components/theme-init";
import { AnnouncementBar } from "@/components/features";
import { FloatingUI } from "@/components/floating-ui";
import { PwaRegister } from "@/components/pwa-register";
import { PreviewBanner } from "@/components/preview-banner";
import { getMenu, getSettings, getWidgetArea } from "@/lib/api";
import { locales } from "@/lib/i18n";
import { getDictionary } from "@/dictionaries";

const jakarta = Plus_Jakarta_Sans({
  variable: "--font-sans",
  subsets: ["latin"],
});

const siteUrl = process.env.NEXT_PUBLIC_SITE_URL ?? "http://localhost:3000";

export const viewport: Viewport = {
  themeColor: [
    { media: "(prefers-color-scheme: light)", color: "#0f7a4e" },
    { media: "(prefers-color-scheme: dark)", color: "#0c110f" },
  ],
};

export async function generateMetadata({
  params,
}: LayoutProps<"/[locale]">): Promise<Metadata> {
  const { locale } = await params;
  const settings = await getSettings().catch(() => ({}) as Record<string, string>);
  const name = settings.site_name ?? "MTsN 1 Kota Malang";

  return {
    metadataBase: new URL(siteUrl),
    manifest: "/manifest.webmanifest",
    applicationName: name,
    appleWebApp: { capable: true, statusBarStyle: "default", title: "MTsN 1" },
    icons: {
      icon: [{ url: "/icon.svg", type: "image/svg+xml" }],
      apple: [{ url: "/icon.svg" }],
    },
    title: {
      default: `${name} — ${settings.site_tagline ?? "Website Resmi"}`,
      template: `%s — ${name}`,
    },
    description:
      settings.site_tagline ??
      "Website resmi madrasah: berita, agenda, profil, galeri, dan informasi PMBM.",
    // Penemuan otomatis feed RSS & kalender oleh browser/agregator (wp: <link rel="alternate">).
    alternates: {
      types: {
        "application/rss+xml": [{ url: `${siteUrl}/feed`, title: `${name} — Berita` }],
        "text/calendar": [{ url: `${siteUrl}/agenda.ics`, title: `${name} — Agenda` }],
      },
    },
    openGraph: {
      type: "website",
      locale: locale === "en" ? "en_US" : "id_ID",
      alternateLocale: locale === "en" ? "id_ID" : "en_US",
      siteName: name,
    },
  };
}

export async function generateStaticParams() {
  return locales.map((locale) => ({ locale }));
}

export default async function RootLayout({ children, params }: LayoutProps<"/[locale]">) {
  const { locale } = await params;
  const [settings, menu, dict, footerWidgets] = await Promise.all([
    getSettings().catch(() => ({}) as Record<string, string>),
    getMenu("header"),
    getDictionary(),
    getWidgetArea("footer"),
  ]);

  return (
    <html
      lang={locale}
      data-scroll-behavior="smooth"
      className={`${jakarta.variable} antialiased`}
      suppressHydrationWarning
    >
      <head>
        <script
          dangerouslySetInnerHTML={{
            __html: `(function(){try{var t=localStorage.getItem('mtsn1-theme');if(t==='dark'||t==='light'){document.documentElement.setAttribute('data-theme',t);}}catch(e){}})();`,
          }}
        />
      </head>
      <body className="min-h-dvh">
        <PreviewBanner />
        <ThemeInit />
        <ScrollProgress />
        <AnnouncementBar text={settings.announcement} href={settings.announcement_url} />
        <a
          href="#konten"
          className="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-[70] focus:rounded-lg focus:bg-brand focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-on-brand"
        >
          {dict.common.skipToContent}
        </a>
        <SiteHeader settings={settings} menu={menu} dict={dict} />
        <div className="flex min-h-dvh flex-col">
          <main id="konten" className="flex-1">
            {children}
          </main>
          <SiteFooter settings={settings} menu={menu} dict={dict} widgets={footerWidgets.blocks} />
        </div>
        <BackToTop />
        <FloatingUI
          whatsapp={settings.whatsapp}
          ppdbUrl={settings.ppdb_url}
          phone={settings.phone}
          address={settings.address}
          mapsUrl={settings.maps_url}
        />
        <PwaRegister />
      </body>
    </html>
  );
}
