/* Service worker MTsN 1 Kota Malang
   Strategi ringan:
   - navigasi (HTML)  -> network-first, fallback cache, lalu /offline
   - gambar/_next aset -> stale-while-revalidate
   - API & /admin      -> selalu lewat jaringan (tidak di-cache)
*/
const VERSION = "v1";
const STATIC_CACHE = `mtsn1-static-${VERSION}`;
const RUNTIME_CACHE = `mtsn1-runtime-${VERSION}`;
const OFFLINE_URL = "/offline";
const PRECACHE = ["/", OFFLINE_URL, "/manifest.webmanifest", "/icon.svg"];

self.addEventListener("install", (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE).then((c) => c.addAll(PRECACHE)).then(() => self.skipWaiting()),
  );
});

self.addEventListener("activate", (event) => {
  event.waitUntil(
    caches
      .keys()
      .then((keys) =>
        Promise.all(
          keys
            .filter((k) => ![STATIC_CACHE, RUNTIME_CACHE].includes(k))
            .map((k) => caches.delete(k)),
        ),
      )
      .then(() => self.clients.claim()),
  );
});

self.addEventListener("message", (event) => {
  if (event.data === "SKIP_WAITING") self.skipWaiting();
});

function isBypassed(url) {
  return (
    url.pathname.startsWith("/api") ||
    url.pathname.startsWith("/admin") ||
    url.pathname.startsWith("/livewire") ||
    url.pathname.startsWith("/storage") ||
    url.pathname.startsWith("/revalidate")
  );
}

self.addEventListener("fetch", (event) => {
  const { request } = event;
  if (request.method !== "GET") return;

  const url = new URL(request.url);
  if (url.origin !== self.location.origin || isBypassed(url)) return;

  if (request.mode === "navigate") {
    event.respondWith(
      fetch(request)
        .then((res) => {
          const copy = res.clone();
          caches.open(RUNTIME_CACHE).then((c) => c.put(request, copy));
          return res;
        })
        .catch(async () => (await caches.match(request)) || (await caches.match(OFFLINE_URL))),
    );
    return;
  }

  const dest = request.destination;
  if (dest === "image" || dest === "font" || dest === "style" || dest === "script") {
    event.respondWith(
      caches.match(request).then((cached) => {
        const network = fetch(request)
          .then((res) => {
            const copy = res.clone();
            caches.open(RUNTIME_CACHE).then((c) => c.put(request, copy));
            return res;
          })
          .catch(() => cached);
        return cached || network;
      }),
    );
  }
});
