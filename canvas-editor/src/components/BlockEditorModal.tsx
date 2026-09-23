import { config } from "../api";

export function BlockEditorModal({
  type,
  nodeId,
  onClose,
}: {
  type: string;
  nodeId: string | null;
  onClose: () => void;
}) {
  const root = document.getElementById("canvas-root")!;
  const pageSlug = root.dataset.pageSlug!;
  const base = new URL(config.treeUrl).origin;
  const path = nodeId
    ? `/admin/pages/${pageSlug}/canvas/block/${type}/${nodeId}`
    : `/admin/pages/${pageSlug}/canvas/block/${type}`;

  return (
    <div className="canvas-modal-overlay" onClick={onClose}>
      <div className="canvas-modal canvas-modal-block" onClick={(e) => e.stopPropagation()}>
        <div className="canvas-modal-header">
          <h2>Edit Widget</h2>
          <button type="button" onClick={onClose}>×</button>
        </div>
        <iframe src={`${base}${path}`} title="Edit widget" className="canvas-block-iframe" />
      </div>
    </div>
  );
}
