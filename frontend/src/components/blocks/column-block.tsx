import type { Block, TreeColumnNode } from "@/lib/types";
import { BlockSwitch } from "./block-renderer";
import { compileBaseStyle } from "@/lib/style-engine";

const widthClass: Record<number, string> = {
  3: "sm:basis-1/4",
  4: "sm:basis-1/3",
  6: "sm:basis-1/2",
  8: "sm:basis-2/3",
  9: "sm:basis-3/4",
  12: "sm:basis-full",
};

export function ColumnBlock({ node }: { node: TreeColumnNode }) {
  const width = (node.style?.base?.width as number | undefined) ?? 12;

  return (
    <div
      data-node-id={node.id}
      className={`flex min-w-0 flex-1 flex-col gap-6 ${widthClass[width] ?? widthClass[12]}`}
      style={compileBaseStyle(node.style)}
    >
      {node.children.map((widget) => (
        <BlockSwitch key={widget.id} block={{ type: widget.type, data: widget.data } as Block} />
      ))}
    </div>
  );
}
