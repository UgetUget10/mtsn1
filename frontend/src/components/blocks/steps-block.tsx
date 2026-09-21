import type { StepsBlockData } from "@/lib/types";

export function StepsBlock({ data }: { data: StepsBlockData }) {
  return (
    <div>
      {data.heading && (
        <h2 className="text-h2 heading-rule mb-6">{data.heading}</h2>
      )}
      <ol className="relative space-y-4">
        {/* Satu garis penghubung menerus dari nomor pertama ke nomor
            terakhir — lebih andal daripada menyambung per-item lewat celah
            `space-y`, yang tingginya berubah-ubah tergantung isi tiap baris. */}
        {data.items.length > 1 && (
          <span
            aria-hidden
            className="absolute left-4.5 top-4.5 bottom-4.5 w-0.5 -translate-x-1/2 bg-linear-to-b from-brand/50 via-brand/25 to-brand/50"
          />
        )}
        {data.items.map((step, i) => (
          <li key={i} className="relative flex gap-4">
            <span className="relative z-10 grid h-9 w-9 shrink-0 place-items-center rounded-full bg-brand text-sm font-bold text-on-brand shadow-[0_2px_8px_-2px_var(--brand)] tabular-nums">
              {i + 1}
            </span>
            <div className="min-w-0 flex-1 rounded-xl border border-border bg-surface px-4 py-3 transition-colors hover:border-brand/40">
              <p className="font-semibold leading-snug text-foreground">
                {step.title}
              </p>
              {step.description && (
                <p className="mt-1 text-sm leading-relaxed text-ink-muted">
                  {step.description}
                </p>
              )}
            </div>
          </li>
        ))}
      </ol>
    </div>
  );
}
