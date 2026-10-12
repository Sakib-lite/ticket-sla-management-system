"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { DataTable, EmptyState, type Column } from "@/components";
import { importPath } from "@/constants/paths";
import type { Import } from "@/types";
import { formatTimeAgo } from "@/utils";
import ImportProgressBar from "./ImportProgressBar";
import ImportStatusBadge from "./ImportStatusBadge";

const columns: Column<Import>[] = [
  {
    key: "file",
    header: "File",
    className: "min-w-56",
    render: (item) => (
      <div className="space-y-0.5">
        <Link
          href={importPath(item.id)}
          className="font-medium text-slate-900 hover:underline"
          onClick={(event) => event.stopPropagation()}
        >
          {item.original_name}
        </Link>
        <p className="text-xs text-slate-500">
          by {item.user.name} · {formatTimeAgo(item.created_at)}
        </p>
      </div>
    ),
  },
  { key: "status", header: "Status", render: (item) => <ImportStatusBadge status={item.status} /> },
  {
    key: "progress",
    header: "Progress",
    className: "min-w-48",
    render: (item) => <ImportProgressBar item={item} />,
  },
  {
    key: "imported",
    header: "Imported",
    className: "text-right",
    render: (item) => <span className="text-emerald-700 tabular-nums">{item.imported_rows}</span>,
  },
  {
    key: "skipped",
    header: "Skipped",
    className: "text-right",
    render: (item) => <span className="text-slate-600 tabular-nums">{item.skipped_rows}</span>,
  },
  {
    key: "failed",
    header: "Failed",
    className: "text-right",
    render: (item) => (
      <span className={item.failed_rows > 0 ? "font-medium text-red-700" : "text-slate-400"}>
        {item.failed_rows}
      </span>
    ),
  },
];

export default function ImportTable({
  imports,
  isLoading,
}: {
  imports: Import[];
  isLoading: boolean;
}) {
  const router = useRouter();

  return (
    <DataTable
      columns={columns}
      rows={imports}
      rowKey={(item) => item.id}
      isLoading={isLoading}
      onRowClick={(item) => router.push(importPath(item.id))}
      emptyState={
        <EmptyState
          title="No imports yet"
          description="Upload a CSV above to bring tickets in from another system."
        />
      }
    />
  );
}
