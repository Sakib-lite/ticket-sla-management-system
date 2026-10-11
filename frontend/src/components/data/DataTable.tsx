"use client";

import type { ReactNode } from "react";
import { Loader } from "@/components/feedback";
import { classNames } from "@/utils";

export type Column<T> = {
  key: string;
  header: string;
  className?: string;
  render: (row: T) => ReactNode;
};

type DataTableProps<T> = {
  columns: Column<T>[];
  rows: T[];
  rowKey: (row: T) => string | number;
  isLoading?: boolean;
  emptyState?: ReactNode;
  onRowClick?: (row: T) => void;
};

export default function DataTable<T>({
  columns,
  rows,
  rowKey,
  isLoading = false,
  emptyState,
  onRowClick,
}: DataTableProps<T>) {
  return (
    <div className="overflow-hidden rounded-lg border border-slate-200 bg-white">
      <div className="overflow-x-auto">
        <table className="w-full text-left text-sm">
          <thead className="bg-slate-50 text-xs tracking-wide text-slate-500 uppercase">
            <tr>
              {columns.map((column) => (
                <th
                  key={column.key}
                  className={classNames("px-4 py-3 font-medium", column.className)}
                >
                  {column.header}
                </th>
              ))}
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {rows.map((row) => (
              <tr
                key={rowKey(row)}
                onClick={onRowClick ? () => onRowClick(row) : undefined}
                className={classNames(
                  "transition",
                  onRowClick && "cursor-pointer hover:bg-slate-50",
                  isLoading && "opacity-50"
                )}
              >
                {columns.map((column) => (
                  <td
                    key={column.key}
                    className={classNames("px-4 py-3 align-middle", column.className)}
                  >
                    {column.render(row)}
                  </td>
                ))}
              </tr>
            ))}
          </tbody>
        </table>
      </div>
      {isLoading && rows.length === 0 && (
        <div className="flex items-center justify-center py-12 text-slate-400">
          <Loader size={22} />
        </div>
      )}
      {!isLoading && rows.length === 0 && emptyState}
    </div>
  );
}
