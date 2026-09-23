import type { TreeSectionNode } from "@/lib/types";
import { ColumnBlock } from "./column-block";
import { compileBaseStyle } from "@/lib/style-engine";

/**
 * Kontainer struktural kanvas visual — merender kolom-kolomnya berdampingan
 * di layar lebar, ditumpuk di layar kecil. `style.base` (warna latar, padding
 * vertikal, perataan teks — panel styling Phase 2) diterapkan lewat inline
 * style; breakpoint lain (md/lg/xl) lewat <style> ber-media-query yang
 * dikumpulkan TreeRenderer (lihat block-renderer.tsx) via data-node-id.
 */
export function SectionBlock({ node }: { node: TreeSectionNode }) {
  return (
    <section data-node-id={node.id} className="flex flex-col gap-6 sm:flex-row" style={compileBaseStyle(node.style)}>
      {node.children.map((column) => (
        <ColumnBlock key={column.id} node={column} />
      ))}
    </section>
  );
}
