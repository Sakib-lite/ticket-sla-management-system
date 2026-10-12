import type { Role, SlaStatus, TicketPriority, TicketStatus } from "@/types";

export type Option = { label: string; value: string };

export const ROLE_LABELS: Record<Role, string> = {
  administrator: "Administrator",
  agent: "Support Agent",
  customer: "Customer",
};

export const TICKET_STATUS_LABELS: Record<TicketStatus, string> = {
  open: "Open",
  in_progress: "In Progress",
  waiting_for_customer: "Waiting for Customer",
  resolved: "Resolved",
  closed: "Closed",
};

export const TICKET_PRIORITY_LABELS: Record<TicketPriority, string> = {
  low: "Low",
  medium: "Medium",
  high: "High",
  critical: "Critical",
};

export const SLA_STATUS_LABELS: Record<SlaStatus, string> = {
  within: "Within SLA",
  approaching: "Approaching",
  breached: "Breached",
};

export const STATUS_TRANSITIONS: Record<TicketStatus, TicketStatus[]> = {
  open: ["in_progress", "waiting_for_customer", "resolved", "closed"],
  in_progress: ["open", "waiting_for_customer", "resolved", "closed"],
  waiting_for_customer: ["open", "in_progress", "resolved", "closed"],
  resolved: ["open", "closed"],
  closed: [],
};

export const STATUS_ACTION_LABELS: Record<TicketStatus, string> = {
  open: "Reopen",
  in_progress: "Start progress",
  waiting_for_customer: "Wait for customer",
  resolved: "Resolve",
  closed: "Close",
};

export const FINISHED_STATUSES: TicketStatus[] = ["resolved", "closed"];

export const isFinishedStatus = (status: TicketStatus) => FINISHED_STATUSES.includes(status);

export const toOptions = <T extends string>(labels: Record<T, string>): Option[] =>
  (Object.entries(labels) as [T, string][]).map(([value, label]) => ({ value, label }));

export const TICKET_STATUS_OPTIONS = toOptions(TICKET_STATUS_LABELS);
export const TICKET_PRIORITY_OPTIONS = toOptions(TICKET_PRIORITY_LABELS);
export const SLA_STATUS_OPTIONS = toOptions(SLA_STATUS_LABELS);

export const ROLE_OPTIONS = toOptions(ROLE_LABELS);
