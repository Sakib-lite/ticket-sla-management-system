"use client";

import type { ReactNode } from "react";
import { Form, SlaBadge } from "@/components";
import { TICKET_PRIORITY_OPTIONS, type Option } from "@/constants/tickets";
import type { Ticket, TicketPriority } from "@/types";
import { formatDateTime, formatRelativeTime } from "@/utils";

type TicketMetaPanelProps = {
  ticket: Ticket;
  categoryOptions: Option[];
  canChangePriority: boolean;
  onPriorityChange: (priority: TicketPriority) => Promise<void>;
  onCategoryChange: (categoryId: number) => Promise<void>;
};

function Row({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div className="space-y-1">
      <dt className="text-xs font-medium tracking-wide text-slate-500 uppercase">{label}</dt>
      <dd className="text-sm text-slate-800">{children}</dd>
    </div>
  );
}

export default function TicketMetaPanel({
  ticket,
  categoryOptions,
  canChangePriority,
  onPriorityChange,
  onCategoryChange,
}: TicketMetaPanelProps) {
  const slaDescription = ticket.resolved_at
    ? `Resolved ${formatDateTime(ticket.resolved_at)}`
    : `Due ${formatRelativeTime(ticket.sla.due_at)} · ${formatDateTime(ticket.sla.due_at)}`;

  return (
    <dl className="space-y-5 rounded-lg border border-slate-200 bg-white p-5">
      <Row label="SLA">
        <div className="space-y-1">
          <SlaBadge sla={ticket.sla} />
          <p className="text-xs text-slate-500">{slaDescription}</p>
          {ticket.sla.escalated_at && (
            <p className="text-xs text-red-600">
              Escalated {formatDateTime(ticket.sla.escalated_at)}
            </p>
          )}
        </div>
      </Row>
      <Row label="Priority">
        {canChangePriority ? (
          <Form.SelectField
            label=""
            value={ticket.priority}
            options={TICKET_PRIORITY_OPTIONS}
            onChange={(event) => onPriorityChange(event.target.value as TicketPriority)}
          />
        ) : (
          TICKET_PRIORITY_OPTIONS.find((option) => option.value === ticket.priority)?.label
        )}
      </Row>
      <Row label="Category">
        {canChangePriority ? (
          <Form.SelectField
            label=""
            value={String(ticket.category.id)}
            options={categoryOptions}
            onChange={(event) => onCategoryChange(Number(event.target.value))}
          />
        ) : (
          ticket.category.name
        )}
      </Row>
      <Row label="Requester">
        <div>
          <p>{ticket.creator.name}</p>
          <p className="text-xs text-slate-500">{ticket.creator.email}</p>
        </div>
      </Row>
      <Row label="Assignee">
        <span className={ticket.assignee ? "" : "text-slate-400"}>
          {ticket.assignee?.name ?? "Unassigned"}
        </span>
      </Row>
      <Row label="Created">{formatDateTime(ticket.created_at)}</Row>
      {ticket.closed_at && <Row label="Closed">{formatDateTime(ticket.closed_at)}</Row>}
    </dl>
  );
}
