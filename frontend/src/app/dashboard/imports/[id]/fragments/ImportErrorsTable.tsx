"use client";

import { Badge, DataTable, EmptyState, type Column } from "@/components";
import type { ImportError } from "@/types";

const columns: Column<ImportError>[] = [
  {
    key: "row",
    header: "Row",
    className: "w-20 whitespace-nowrap",
    render: (item) => <span className="font-mono text-xs">{item.row_number}</span>,
  },
  {
    key: "kind",
    header: "Kind",
    render: (item) => (
      <Badge tone={item.kind === "duplicate" ? "amber" : "red"}>
        {item.kind === "duplicate" ? "Duplicate" : "Invalid"}
      </Badge>
    ),
  },
  {
    key: "messages",
    header: "Problem",
    render: (item) => (
      <ul className="space-y-0.5 text-slate-700">
        {item.messages.map((message) => (
          <li key={message}>{message}</li>
        ))}
      </ul>
    ),
  },
  {
    key: "raw",
    header: "Row data",
    className: "max-w-md",
    render: (item) => (
      <p className="truncate font-mono text-xs text-slate-500" title={JSON.stringify(item.raw)}>
        {Object.entries(item.raw)
          .filter(([, value]) => value)
          .map(([key, value]) => `${key}=${value}`)
          .join(" · ")}
      </p>
    ),
  },
];

export default function ImportErrorsTable({
  errors,
  isLoading,
}: {
  errors: ImportError[];
  isLoading: boolean;
}) {
  return (
    <DataTable
      columns={columns}
      rows={errors}
      rowKey={(item) => item.id}
      isLoading={isLoading}
      emptyState={<EmptyState title="No problems" description="Every row was imported." />}
    />
  );
}
