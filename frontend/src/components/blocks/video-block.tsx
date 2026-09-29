import type { VideoBlockData } from "@/lib/types";

/** Cermin ringkas backend/app/Support/Oembed/AutoEmbed.php::youtube()/vimeo() — ekstrak id video. */
function toEmbedUrl(url: string): string | null {
  let host: string;
  try {
    host = new URL(url).hostname.replace(/^www\./, "");
  } catch {
    return null;
  }

  if (host === "youtube.com" || host === "m.youtube.com" || host === "youtu.be") {
    const id =
      url.match(/youtu\.be\/([\w-]{6,})/)?.[1] ??
      url.match(/[?&]v=([\w-]{6,})/)?.[1] ??
      url.match(/\/(?:embed|shorts)\/([\w-]{6,})/)?.[1];
    return id ? `https://www.youtube-nocookie.com/embed/${id}` : null;
  }

  if (host === "vimeo.com" || host === "player.vimeo.com") {
    const id = url.match(/vimeo\.com\/(?:video\/)?(\d+)/)?.[1];
    return id ? `https://player.vimeo.com/video/${id}` : null;
  }

  return null;
}

export function VideoBlock({ data }: { data: VideoBlockData }) {
  const embedUrl = toEmbedUrl(data.url);

  if (embedUrl) {
    return (
      <div className="oembed overflow-hidden rounded-xl" style={{ aspectRatio: "16 / 9" }}>
        <iframe
          src={embedUrl}
          title="Video"
          loading="lazy"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
          allowFullScreen
          referrerPolicy="strict-origin-when-cross-origin"
          className="h-full w-full border-0"
        />
      </div>
    );
  }

  return (
    // Tautan file langsung (.mp4, dll) — bukan YouTube/Vimeo.
    <video
      controls
      poster={data.poster ?? undefined}
      className="w-full rounded-xl bg-surface-muted"
      style={{ aspectRatio: "16 / 9" }}
    >
      <source src={data.url} />
    </video>
  );
}
