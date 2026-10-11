"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import {
  Button,
  DataTable,
  EmptyState,
  PriorityBadge,
  SlaBadge,
  StatusBadge,
  type Column,
} from "@/components";
import { NEW_TICKET_PATH, ticketPath } from "@/constants/paths";
import type { Ticket } from "@/types";
import { formatTimeAgo } from "@/utils";

type TicketTableProps = {
  tickets: Ticket[];
  isLoading: boolean;
  hasActiveFilters: boolean;
};

const columns: Column<Ticket>[] = [
  {
    key: "ticket",
    header: "Ticket",
    className: "min-w-64",
    render: (ticket) => (
      <div className="space-y-0.5">
        <Link
          href={ticketPath(ticket.id)}
          className="font-mono text-xs text-slate-500 hover:text-slate-900"
          onClick={(event) => event.stopPropagation()}
        >
          {ticket.ticket_number}
        </Link>
        <p className="line-clamp-1 font-medium text-slate-900">{ticket.subject}</p>
      </div>
    ),
  },
  { key: "status", header: "Status", render: (ticket) => <StatusBadge status={ticket.status} /> },
  {
    key: "priority",
    header: "Priority",
    render: (ticket) => <PriorityBadge priority={ticket.priority} />,
  },
  { key: "sla", header: "SLA", render: (ticket) => <SlaBadge sla={ticket.sla} /> },
  {
    key: "assignee",
    header: "Assignee",
    render: (ticket) => (
      <span className={ticket.assignee ? "text-slate-700" : "text-slate-400"}>
        {ticket.assignee?.name ?? "Unassigned"}
      </span>
    ),
  },
  {
    key: "created",
    header: "Created",
    className: "whitespace-nowrap",
    render: (ticket) => <span className="text-slate-500">{formatTimeAgo(ticket.created_at)}</span>,
  },
];

export default function TicketTable({ tickets, isLoading, hasActiveFilters }: TicketTableProps) {
  const router = useRouter();

  return (
    <DataTable
      columns={columns}
      rows={tickets}
      rowKey={(ticket) => ticket.id}
      isLoading={isLoading}
      onRowClick={(ticket) => router.push(ticketPath(ticket.id))}
      emptyState={
        <EmptyState
          title={hasActiveFilters ? "No tickets match these filters" : "No tickets yet"}
          description={
            hasActiveFilters
              ? "Try widening the search or clearing a filter."
              : "Tickets you create or are assigned will show up here."
          }
          action={
            !hasActiveFilters && (
              <Link href={NEW_TICKET_PATH}>
                <Button.Primary tone="ghost">Create a ticket</Button.Primary>
              </Link>
            )
          }
        />
      }
    />
  );
}
