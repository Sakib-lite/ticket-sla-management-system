"use client";

import { useEffect, useRef, type ReactNode } from "react";
import { createPortal } from "react-dom";
import { X } from "@phosphor-icons/react";
import { classNames } from "@/utils";

type ModalSize = "sm" | "md" | "lg";

type ModalProps = {
  isOpen: boolean;
  title?: string;
  size?: ModalSize;
  showClose?: boolean;
  onClose: () => void;
  footer?: ReactNode;
  children: ReactNode;
};

const sizeStyles: Record<ModalSize, string> = {
  sm: "max-w-md",
  md: "max-w-2xl",
  lg: "max-w-4xl",
};

const FOCUSABLE =
  'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

export default function Modal({
  isOpen,
  title,
  size = "md",
  showClose = true,
  onClose,
  footer,
  children,
}: ModalProps) {
  const dialogRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!isOpen) {
      return;
    }

    const previouslyFocused = document.activeElement as HTMLElement | null;
    const dialog = dialogRef.current;
    const firstField = dialog?.querySelector<HTMLElement>(FOCUSABLE);
    (firstField ?? dialog)?.focus();

    const onKeyDown = (event: KeyboardEvent) => {
      if (event.key === "Escape") {
        onClose();
        return;
      }
      if (event.key !== "Tab" || !dialog) {
        return;
      }
      const focusable = Array.from(dialog.querySelectorAll<HTMLElement>(FOCUSABLE));
      if (focusable.length === 0) {
        return;
      }
      const first = focusable[0];
      const last = focusable[focusable.length - 1];
      if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
      } else if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
      }
    };

    document.addEventListener("keydown", onKeyDown);
    document.body.classList.add("overflow-hidden");

    return () => {
      document.removeEventListener("keydown", onKeyDown);
      document.body.classList.remove("overflow-hidden");
      previouslyFocused?.focus();
    };
  }, [isOpen, onClose]);

  if (!isOpen || typeof document === "undefined") {
    return null;
  }

  return createPortal(
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="absolute inset-0 bg-slate-900/40" onClick={onClose} />
      <div
        ref={dialogRef}
        role="dialog"
        aria-modal="true"
        aria-label={title}
        tabIndex={-1}
        className={classNames(
          "relative z-10 w-full overflow-hidden rounded-lg bg-white shadow-xl outline-none",
          sizeStyles[size]
        )}
      >
        {(title || showClose) && (
          <header className="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h3 className="text-base font-semibold text-slate-900">{title}</h3>
            {showClose && (
              <button
                type="button"
                onClick={onClose}
                aria-label="Close dialog"
                className="rounded p-1 text-slate-500 hover:bg-slate-100"
              >
                <X size={18} />
              </button>
            )}
          </header>
        )}
        <div className="px-5 py-5">{children}</div>
        {footer && <footer className="border-t border-slate-100 px-5 py-4">{footer}</footer>}
      </div>
    </div>,
    document.body
  );
}
