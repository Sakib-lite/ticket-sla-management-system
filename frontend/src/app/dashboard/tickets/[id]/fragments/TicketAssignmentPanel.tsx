"use client";

import { useState } from "react";
import { HandGrabbing, UserMinus } from "@phosphor-icons/react";
import { Button, Form } from "@/components";
import type { Option } from "@/constants/tickets";
import type { Ticket } from "@/types";

type TicketAssignmentPanelProps = {
  ticket: Ticket;
  agentOptions: Option[];
  canAssign: boolean;
  canClaim: boolean;
  onAssign: (agentId: number) => Promise<void>;
  onClaim: () => Promise<void>;
  onUnassign: () => Promise<void>;
};

export default function TicketAssignmentPanel({
  ticket,
  agentOptions,
  canAssign,
  canClaim,
  onAssign,
  onClaim,
  onUnassign,
}: TicketAssignmentPanelProps) {
  const [selectedAgent, setSelectedAgent] = useState("");

  if (!canAssign && !canClaim) {
    return null;
  }

  return (
    <section className="space-y-4 rounded-lg border border-slate-200 bg-white p-5">
      <div className="flex items-center justify-between">
        <h2 className="text-sm font-semibold text-slate-900">Assignment</h2>
        <span className={ticket.assignee ? "text-sm text-slate-700" : "text-sm text-slate-400"}>
          {ticket.assignee?.name ?? "Unassigned"}
        </span>
      </div>
      {canClaim && !ticket.assignee && (
        <Button.Async onClick={onClaim} className="w-full">
          <HandGrabbing size={16} /> Claim this ticket
        </Button.Async>
      )}
      {canAssign && (
        <div className="flex items-end gap-2">
          <div className="flex-1">
            <Form.SelectField
              label={ticket.assignee ? "Reassign to" : "Assign to"}
              value={selectedAgent}
              placeholder="Choose an agent"
              options={agentOptions}
              onChange={(event) => setSelectedAgent(event.target.value)}
            />
          </div>
          <Button.Async
            tone="ghost"
            disabled={!selectedAgent}
            onClick={async () => {
              await onAssign(Number(selectedAgent));
              setSelectedAgent("");
            }}
          >
            Assign
          </Button.Async>
        </div>
      )}
      {canAssign && ticket.assignee && (
        <Button.Async tone="ghost" onClick={onUnassign} className="w-full">
          <UserMinus size={16} /> Unassign
        </Button.Async>
      )}
    </section>
  );
}
