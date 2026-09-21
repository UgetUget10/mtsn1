import type { TableBlockData } from "@/lib/types";

export function TableBlock({ data }: { data: TableBlockData }) {
  return (
    <div>
      {data.heading && <h2 className="text-h2 heading-rule mb-6">{data.heading}</h2>}
      <div className="overflow-x-auto rounded-2xl border border-border bg-surface shadow-sm">
        <table className="w-full min-w-[42rem] border-collapse text-sm">
          <thead>
            <tr className="border-b border-border bg-surface-muted text-left text-[0.68rem] font-bold uppercase tracking-wider text-ink-muted">
              {data.columns.map((c) => (
                <th key={c.label} className="px-4 py-3.5">
                  {c.label}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className="divide-y divide-border">
            {data.rows.map((row, i) => (
              <tr key={i} className="align-top transition-colors hover:bg-brand-light/40">
                {row.cells.map((cell, j) => (
                  <td key={j} className="px-4 py-3.5 text-ink-soft">
                    {cell.value}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
}
