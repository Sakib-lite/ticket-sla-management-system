"use client";

import Link from "next/link";
import { TICKETS_PATH } from "@/constants/paths";
import type { AgentWorkload as AgentRow } from "@/types";

export default function AgentWorkload({ rows }: { rows: AgentRow[] }) {
  return (
    <section className="rounded-lg border border-slate-200 bg-white p-5">
      <h2 className="mb-4 text-sm font-semibold text-slate-900">Active tickets by agent</h2>
      {rows.length === 0 ? (
        <p className="text-sm text-slate-500">No tickets are assigned right now.</p>
      ) : (
        <table className="w-full text-sm">
          <thead className="text-xs tracking-wide text-slate-500 uppercase">
            <tr>
              <th className="pb-2 text-left font-medium">Agent</th>
              <th className="pb-2 text-right font-medium">Active</th>
              <th className="pb-2 text-right font-medium">Breached</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {rows.map((row) => (
              <tr key={row.agent_id}>
                <td className="py-2">
                  <Link
                    href={`${TICKETS_PATH}?view=active&assignee=${row.agent_id}`}
                    className="text-slate-800 hover:underline"
                  >
                    {row.agent_name}
                  </Link>
                </td>
                <td className="py-2 text-right text-slate-700 tabular-nums">{row.total}</td>
                <td className="py-2 text-right tabular-nums">
                  <span
                    className={row.breached > 0 ? "font-medium text-red-700" : "text-slate-400"}
                  >
                    {row.breached}
                  </span>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </section>
  );
}
