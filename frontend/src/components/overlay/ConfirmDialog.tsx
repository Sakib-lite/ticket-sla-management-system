"use client";

import { Button } from "@/components";
import { useConfirmStore, type ConfirmTone } from "@/hooks/useConfirm";
import Modal from "./Modal";

const confirmTones: Record<ConfirmTone, "primary" | "danger" | "ghost"> = {
  danger: "danger",
  warning: "primary",
  info: "primary",
};

export default function ConfirmDialog() {
  const { isOpen, isLoading, errorMessage, options, accept, cancel } = useConfirmStore();

  if (!options) {
    return null;
  }

  return (
    <Modal isOpen={isOpen} title={options.title} size="sm" onClose={cancel}>
      {options.description && <p className="text-sm text-slate-600">{options.description}</p>}
      {errorMessage && <p className="mt-3 text-sm text-red-600">{errorMessage}</p>}
      <div className="mt-6 flex justify-end gap-2">
        <Button.Primary tone="ghost" onClick={cancel} disabled={isLoading}>
          {options.cancelText || "Cancel"}
        </Button.Primary>
        <Button.Primary
          tone={confirmTones[options.tone || "warning"]}
          isLoading={isLoading}
          onClick={accept}
        >
          {options.confirmText || "Confirm"}
        </Button.Primary>
      </div>
    </Modal>
  );
}
