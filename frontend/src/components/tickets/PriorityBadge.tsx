import { Badge, type BadgeTone } from "@/components/data";
import { TICKET_PRIORITY_LABELS } from "@/constants/tickets";
import type { TicketPriority } from "@/types";

const tones: Record<TicketPriority, BadgeTone> = {
  low: "slate",
  medium: "blue",
  high: "amber",
  critical: "red",
};

export default function PriorityBadge({ priority }: { priority: TicketPriority }) {
  return <Badge tone={tones[priority]}>{TICKET_PRIORITY_LABELS[priority]}</Badge>;
}
