"use client";

import { useState } from "react";
import { Button, PriorityBadge } from "@/components";
import type { SlaPolicyPayload } from "@/services";
import type { SlaPolicy } from "@/types";

type SlaPolicyTableProps = {
  policies: SlaPolicy[];
  onSave: (policy: SlaPolicy, payload: SlaPolicyPayload) => Promise<void>;
};

function PolicyRow({
  policy,
  onSave,
}: {
  policy: SlaPolicy;
  onSave: SlaPolicyTableProps["onSave"];
}) {
  const [hours, setHours] = useState(String(policy.resolution_hours));
  const [threshold, setThreshold] = useState(String(policy.approaching_threshold_percent));

  const isDirty =
    Number(hours) !== policy.resolution_hours ||
    Number(threshold) !== policy.approaching_threshold_percent;

  const inputClass =
    "w-24 rounded-md border border-slate-300 px-2 py-1.5 text-sm tabular-nums outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-200";

  return (
    <tr>
      <td className="py-3">
        <PriorityBadge priority={policy.priority} />
      </td>
      <td className="py-3">
        <input
          type="number"
          min={1}
          max={720}
          value={hours}
          aria-label={`${policy.label} resolution hours`}
          onChange={(event) => setHours(event.target.value)}
          className={inputClass}
        />
      </td>
      <td className="py-3">
        <input
          type="number"
          min={1}
          max={99}
          value={threshold}
          aria-label={`${policy.label} approaching threshold`}
          onChange={(event) => setThreshold(event.target.value)}
          className={inputClass}
        />
      </td>
      <td className="py-3 text-right">
        <Button.Async
          tone="ghost"
          disabled={!isDirty}
          onClick={() =>
            onSave(policy, {
              resolution_hours: Number(hours),
              approaching_threshold_percent: Number(threshold),
            })
          }
        >
          Save
        </Button.Async>
      </td>
    </tr>
  );
}

export default function SlaPolicyTable({ policies, onSave }: SlaPolicyTableProps) {
  return (
    <section className="rounded-lg border border-slate-200 bg-white p-5">
      <h2 className="text-sm font-semibold text-slate-900">SLA policies</h2>
      <p className="mb-4 text-sm text-slate-500">
        Resolution time counts business hours only. Changes apply to tickets created from now on.
      </p>
      <table className="w-full text-sm">
        <thead className="text-xs tracking-wide text-slate-500 uppercase">
          <tr>
            <th className="pb-2 text-left font-medium">Priority</th>
            <th className="pb-2 text-left font-medium">Resolve within (hours)</th>
            <th className="pb-2 text-left font-medium">Warn at (% elapsed)</th>
            <th className="pb-2" />
          </tr>
        </thead>
        <tbody className="divide-y divide-slate-100">
          {policies.map((policy) => (
            <PolicyRow key={policy.id} policy={policy} onSave={onSave} />
          ))}
        </tbody>
      </table>
    </section>
  );
}
