import { Badge, type BadgeTone } from "@/components/data";
import { SLA_STATUS_LABELS } from "@/constants/tickets";
import type { TicketSla } from "@/types";

const tones: Record<TicketSla["status"], BadgeTone> = {
  within: "green",
  approaching: "amber",
  breached: "red",
};

export default function SlaBadge({ sla }: { sla: TicketSla }) {
  if (sla.resolved_within_sla !== null) {
    return (
      <Badge tone={sla.resolved_within_sla ? "green" : "red"}>
        {sla.resolved_within_sla ? "Met SLA" : "Missed SLA"}
      </Badge>
    );
  }

  return <Badge tone={tones[sla.status]}>{SLA_STATUS_LABELS[sla.status]}</Badge>;
}
