"use client";

import { Button } from "@/components";
import { STATUS_ACTION_LABELS, STATUS_TRANSITIONS } from "@/constants/tickets";
import type { TicketStatus } from "@/types";

type TicketStatusActionsProps = {
  status: TicketStatus;
  onChange: (status: TicketStatus) => Promise<void>;
};

const tones: Partial<Record<TicketStatus, "primary" | "danger" | "ghost">> = {
  resolved: "primary",
  closed: "danger",
};

export default function TicketStatusActions({ status, onChange }: TicketStatusActionsProps) {
  const transitions = STATUS_TRANSITIONS[status];

  if (transitions.length === 0) {
    return null;
  }

  return (
    <div className="flex flex-wrap gap-2">
      {transitions.map((next) => (
        <Button.Async key={next} tone={tones[next] ?? "ghost"} onClick={() => onChange(next)}>
          {STATUS_ACTION_LABELS[next]}
        </Button.Async>
      ))}
    </div>
  );
}
