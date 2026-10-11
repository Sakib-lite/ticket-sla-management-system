"use client";

import { CaretLeft, CaretRight } from "@phosphor-icons/react";
import type { PaginationMeta } from "@/types";

type PaginationProps = {
  meta: PaginationMeta;
  onPageChange: (page: number) => void;
};

export default function Pagination({ meta, onPageChange }: PaginationProps) {
  if (meta.total === 0) {
    return null;
  }

  const buttonClass =
    "inline-flex items-center gap-1 rounded-md border border-slate-300 px-3 py-1.5 text-sm text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40";

  return (
    <div className="flex flex-col items-center justify-between gap-3 text-sm text-slate-600 sm:flex-row">
      <span>
        Showing {meta.from}–{meta.to} of {meta.total}
      </span>
      <div className="flex items-center gap-2">
        <button
          type="button"
          className={buttonClass}
          disabled={meta.current_page <= 1}
          onClick={() => onPageChange(meta.current_page - 1)}
        >
          <CaretLeft size={14} /> Previous
        </button>
        <span className="px-2">
          Page {meta.current_page} of {meta.last_page}
        </span>
        <button
          type="button"
          className={buttonClass}
          disabled={meta.current_page >= meta.last_page}
          onClick={() => onPageChange(meta.current_page + 1)}
        >
          Next <CaretRight size={14} />
        </button>
      </div>
    </div>
  );
}
