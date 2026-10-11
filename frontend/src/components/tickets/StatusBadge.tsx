import { Badge, type BadgeTone } from "@/components/data";
import { TICKET_STATUS_LABELS } from "@/constants/tickets";
import type { TicketStatus } from "@/types";

const tones: Record<TicketStatus, BadgeTone> = {
  open: "blue",
  in_progress: "violet",
  waiting_for_customer: "amber",
  resolved: "green",
  closed: "slate",
};

export default function StatusBadge({ status }: { status: TicketStatus }) {
  return <Badge tone={tones[status]}>{TICKET_STATUS_LABELS[status]}</Badge>;
}
