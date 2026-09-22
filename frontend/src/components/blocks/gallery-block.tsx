import Image from "@/components/media-image";
import type { GalleryBlockData } from "@/lib/types";

export function GalleryBlockView({ data }: { data: GalleryBlockData }) {
  if (!data.gallery) return null;

  const images = data.gallery.items.filter((i) => i.type === "image" && i.url);

  return (
    <div>
      {(data.heading ?? data.gallery.title) && (
        <h2 className="text-h2 heading-rule mb-6">{data.heading ?? data.gallery.title}</h2>
      )}
      <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        {images.map((item, i) => (
          <div key={i} className="relative aspect-square overflow-hidden rounded-xl bg-surface-muted">
            <Image src={item.url!} alt={item.caption ?? ""} fill className="object-cover" />
          </div>
        ))}
      </div>
    </div>
  );
}
