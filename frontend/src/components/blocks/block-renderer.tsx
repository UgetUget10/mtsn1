import type { Block } from "@/lib/types";
import { HeroBlock } from "./hero-block";
import { RichTextBlock } from "./rich-text-block";
import { CardGridBlock } from "./card-grid-block";
import { AccordionBlock } from "./accordion-block";
import { CtaBlock } from "./cta-block";
import { FileListBlock } from "./file-list-block";
import { GalleryBlockView } from "./gallery-block";
import { StatsBlock } from "./stats-block";
import { HubGridBlock } from "./hub-grid-block";
import { TableBlock } from "./table-block";
import { StepsBlock } from "./steps-block";
import { QuoteBlock } from "./quote-block";
import { LinkCardsBlock } from "./link-cards-block";
import { ChecklistBlock } from "./checklist-block";
import { IconListBlock } from "./icon-list-block";
import { TimelineBlock } from "./timeline-block";

/**
 * Render satu daftar block (dari Page/Homepage) menjadi UI, block demi
 * block, sesuai `type`-nya. Tipe tak dikenal dilewati diam-diam agar
 * halaman tetap tampil meski backend menambah tipe baru sebelum frontend
 * di-deploy ulang.
 */
export function BlockRenderer({ blocks }: { blocks: Block[] }) {
  return (
    <div className="space-y-10">
      {blocks.map((block, i) => (
        <BlockSwitch key={i} block={block} />
      ))}
    </div>
  );
}

function BlockSwitch({ block }: { block: Block }) {
  switch (block.type) {
    case "hero":
      return <HeroBlock data={block.data} />;
    case "rich_text":
      return <RichTextBlock data={block.data} />;
    case "card_grid":
      return <CardGridBlock data={block.data} />;
    case "accordion":
      return <AccordionBlock data={block.data} />;
    case "cta":
      return <CtaBlock data={block.data} />;
    case "file_list":
      return <FileListBlock data={block.data} />;
    case "gallery_block":
      return <GalleryBlockView data={block.data} />;
    case "stats":
      return <StatsBlock data={block.data} />;
    case "hub_grid":
      return <HubGridBlock data={block.data} />;
    case "table":
      return <TableBlock data={block.data} />;
    case "steps":
      return <StepsBlock data={block.data} />;
    case "quote":
      return <QuoteBlock data={block.data} />;
    case "link_cards":
      return <LinkCardsBlock data={block.data} />;
    case "checklist":
      return <ChecklistBlock data={block.data} />;
    case "icon_list":
      return <IconListBlock data={block.data} />;
    case "timeline":
      return <TimelineBlock data={block.data} />;
    default:
      return null;
  }
}
