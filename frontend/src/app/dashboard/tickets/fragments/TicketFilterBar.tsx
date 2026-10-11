"use client";

import { useEffect, useEffectEvent, useState } from "react";
import { MagnifyingGlass, X } from "@phosphor-icons/react";
import { Form } from "@/components";
import {
  SLA_STATUS_OPTIONS,
  TICKET_PRIORITY_OPTIONS,
  TICKET_STATUS_OPTIONS,
  type Option,
} from "@/constants/tickets";
import type { TicketListParams, TicketView } from "@/services";
import { classNames } from "@/utils";

const VIEWS: { label: string; value: TicketView }[] = [
  { label: "Active", value: "active" },
  { label: "Resolved", value: "resolved" },
  { label: "All", value: "all" },
];

const SEARCH_DEBOUNCE_MS = 350;

type TicketFilterBarProps = {
  filters: TicketListParams;
  categoryOptions: Option[];
  agentOptions: Option[] | null;
  hasActiveFilters: boolean;
  onChange: (changes: Partial<TicketListParams>) => void;
  onReset: () => void;
};

export default function TicketFilterBar({
  filters,
  categoryOptions,
  agentOptions,
  hasActiveFilters,
  onChange,
  onReset,
}: TicketFilterBarProps) {
  const urlSearch = filters.search ?? "";
  const [search, setSearch] = useState(urlSearch);
  const [syncedSearch, setSyncedSearch] = useState(urlSearch);

  if (urlSearch !== syncedSearch) {
    setSyncedSearch(urlSearch);
    setSearch(urlSearch);
  }

  const commitSearch = useEffectEvent((value: string) => onChange({ search: value }));

  useEffect(() => {
    if (search === urlSearch) {
      return;
    }

    const timer = setTimeout(() => commitSearch(search), SEARCH_DEBOUNCE_MS);
    return () => clearTimeout(timer);
  }, [search, urlSearch]);

  return (
    <div className="space-y-3">
      <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div className="inline-flex rounded-md border border-slate-300 bg-white p-0.5">
          {VIEWS.map((view) => (
            <button
              key={view.value}
              type="button"
              onClick={() => onChange({ view: view.value })}
              className={classNames(
                "rounded px-3 py-1.5 text-sm font-medium transition",
                filters.view === view.value
                  ? "bg-slate-900 text-white"
                  : "text-slate-600 hover:bg-slate-100"
              )}
            >
              {view.label}
            </button>
          ))}
        </div>
        <label className="relative block sm:w-80">
          <MagnifyingGlass size={16} className="absolute top-2.5 left-3 text-slate-400" />
          <input
            type="search"
            value={search}
            onChange={(event) => setSearch(event.target.value)}
            placeholder="Search by ticket number or subject"
            className="w-full rounded-md border border-slate-300 bg-white py-2 pr-3 pl-9 text-sm outline-none focus:border-slate-400 focus:ring-2 focus:ring-slate-200"
          />
        </label>
      </div>
      <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <Form.SelectField
          label="Status"
          value={filters.status ?? ""}
          placeholder="Any status"
          options={TICKET_STATUS_OPTIONS}
          onChange={(event) => onChange({ status: (event.target.value || undefined) as never })}
        />
        <Form.SelectField
          label="Priority"
          value={filters.priority ?? ""}
          placeholder="Any priority"
          options={TICKET_PRIORITY_OPTIONS}
          onChange={(event) => onChange({ priority: (event.target.value || undefined) as never })}
        />
        <Form.SelectField
          label="Category"
          value={filters.category_id ? String(filters.category_id) : ""}
          placeholder="Any category"
          options={categoryOptions}
          onChange={(event) =>
            onChange({ category_id: event.target.value ? Number(event.target.value) : undefined })
          }
        />
        <Form.SelectField
          label="SLA"
          value={filters.sla_status ?? ""}
          placeholder="Any SLA state"
          options={SLA_STATUS_OPTIONS}
          onChange={(event) => onChange({ sla_status: (event.target.value || undefined) as never })}
        />
        {agentOptions && (
          <Form.SelectField
            label="Assignee"
            value={filters.assignee ?? ""}
            placeholder="Anyone"
            options={[{ label: "Unassigned", value: "unassigned" }, ...agentOptions]}
            onChange={(event) => onChange({ assignee: event.target.value || undefined })}
          />
        )}
        <div className="flex items-end">
          {hasActiveFilters && (
            <button
              type="button"
              onClick={onReset}
              className="inline-flex items-center gap-1 rounded-md px-3 py-2 text-sm text-slate-600 hover:bg-slate-100"
            >
              <X size={14} /> Clear filters
            </button>
          )}
        </div>
      </div>
    </div>
  );
}
