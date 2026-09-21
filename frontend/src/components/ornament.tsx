import type { ReactNode } from "react";

/**
 * Watermark bintang-8 (khatam) — dekoratif, statis. Letakkan di dalam
 * wadah `relative`; atur posisi lewat `className` (mis. "-right-16 -top-16").
 */
export function MotifStar({ className = "" }: { className?: string }) {
  return <span aria-hidden className={`motif-star ${className}`} />;
}

/**
 * Pembatas seksi berhias. Ganti `border-t` polos dengan ini.
 * variant: "arabesque" (belah ketupat + titik aksen), "dot", "plain".
 */
export function SectionDivider({
  variant = "arabesque",
  className = "",
}: {
  variant?: "arabesque" | "dot" | "plain";
  className?: string;
}) {
  const v = variant === "arabesque" ? "" : variant;
  return (
    <div aria-hidden className={`section-divider ${v} ${className}`}>
      <span className="knot" />
    </div>
  );
}

/**
 * Bingkai sudut siku pada kartu penting. Bungkus konten kartu:
 * <CornerFrame className="card p-6">…</CornerFrame>
 */
export function CornerFrame({
  children,
  className = "",
  as: As = "div",
}: {
  children: ReactNode;
  className?: string;
  as?: React.ElementType;
}) {
  return <As className={`corner-frame ${className}`}>{children}</As>;
}
