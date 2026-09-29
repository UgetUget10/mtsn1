import type { GlobalTemplateResponse, TreeSectionNode } from "@/lib/types";
import { SectionBlock } from "@/components/blocks/section-block";
import { compileResponsiveCss } from "@/lib/style-engine";

/**
 * Slot konten tambahan Theme Builder — dirender DI ATAS header atau DI BAWAH
 * footer yang sudah ada (SiteHeader/SiteFooter TIDAK diganti, lihat
 * App\Models\GlobalTemplate). Tidak tampil apa pun bila admin belum pernah
 * mempublish desain untuk slot ini (`active=false`), supaya situs publik
 * tidak berubah sebelum Theme Builder sengaja dipakai.
 */
export function GlobalTemplateSlot({ template }: { template: GlobalTemplateResponse }) {
  if (!template.active || template.tree.tree.length === 0) return null;

  const responsiveCss = template.tree.tree.map((section) => collectResponsiveCss(section)).join("\n");

  return (
    <div className="space-y-10">
      {responsiveCss && <style dangerouslySetInnerHTML={{ __html: responsiveCss }} />}
      {template.tree.tree.map((section) => (
        <SectionBlock key={section.id} node={section} />
      ))}
    </div>
  );
}

function collectResponsiveCss(section: TreeSectionNode): string {
  const rules = [compileResponsiveCss(section.id, section.style)];
  for (const column of section.children) {
    rules.push(compileResponsiveCss(column.id, column.style));
    for (const widget of column.children) {
      rules.push(compileResponsiveCss(widget.id, widget.style));
    }
  }
  return rules.filter(Boolean).join("\n");
}
