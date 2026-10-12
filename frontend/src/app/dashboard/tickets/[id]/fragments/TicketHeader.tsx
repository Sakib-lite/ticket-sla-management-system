"use client";

import Link from "next/link";
import { ArrowLeft, PencilSimple, Warning } from "@phosphor-icons/react";
import { Badge, Button, PageHeader, PriorityBadge, SlaBadge, StatusBadge } from "@/components";
import { TICKETS_PATH } from "@/constants/paths";
import type { Ticket } from "@/types";

type TicketHeaderProps = {
  ticket: Ticket;
  canEdit: boolean;
  onEdit: () => void;
};

export default function TicketHeader({ ticket, canEdit, onEdit }: TicketHeaderProps) {
  return (
    <div className="space-y-3">
      <Link
        href={TICKETS_PATH}
        className="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-900"
      >
        <ArrowLeft size={14} /> All tickets
      </Link>
      <PageHeader
        title={
          <span className="flex flex-wrap items-center gap-x-3 gap-y-1">
            <span className="font-mono text-base text-slate-500">{ticket.ticket_number}</span>
            <span>{ticket.subject}</span>
          </span>
        }
        description={
          <span className="flex flex-wrap items-center gap-2">
            <StatusBadge status={ticket.status} />
            <PriorityBadge priority={ticket.priority} />
            <SlaBadge sla={ticket.sla} />
            {ticket.sla.escalated_at && !ticket.resolved_at && (
              <Badge tone="red">
                <Warning size={12} weight="fill" className="mr-1" /> Escalated
              </Badge>
            )}
          </span>
        }
        actions={
          canEdit && (
            <Button.Primary tone="ghost" onClick={onEdit}>
              <PencilSimple size={16} /> Edit
            </Button.Primary>
          )
        }
      />
    </div>
  );
}
