"use client";

import Link from "next/link";
import { PriorityBadge } from "@/components";
import { TICKETS_PATH } from "@/constants/paths";
import type { PriorityBreakdown as PriorityRow } from "@/types";

const barColors: Record<PriorityRow["priority"], string> = {
  critical: "bg-red-500",
  high: "bg-amber-500",
  medium: "bg-sky-500",
  low: "bg-slate-400",
};

export default function PriorityBreakdown({ rows }: { rows: PriorityRow[] }) {
  const max = Math.max(1, ...rows.map((row) => row.total));

  return (
    <section className="rounded-lg border border-slate-200 bg-white p-5">
      <h2 className="mb-4 text-sm font-semibold text-slate-900">Active tickets by priority</h2>
      <ul className="space-y-3">
        {rows.map((row) => (
          <li key={row.priority} className="flex items-center gap-3">
            <Link
              href={`${TICKETS_PATH}?view=active&priority=${row.priority}`}
              className="w-24 shrink-0"
            >
              <PriorityBadge priority={row.priority} />
            </Link>
            <div className="h-2 flex-1 overflow-hidden rounded-full bg-slate-100">
              <div
                className={`h-full rounded-full ${barColors[row.priority]}`}
                style={{ width: `${(row.total / max) * 100}%` }}
              />
            </div>
            <span className="w-8 text-right text-sm font-medium text-slate-700 tabular-nums">
              {row.total}
            </span>
          </li>
        ))}
      </ul>
    </section>
  );
}
