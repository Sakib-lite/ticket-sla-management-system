export type Role = "administrator" | "agent" | "customer";

export type User = {
  id: number;
  name: string;
  email: string;
  role: Role;
};

export type TicketStatus = "open" | "in_progress" | "waiting_for_customer" | "resolved" | "closed";

export type TicketPriority = "low" | "medium" | "high" | "critical";

export type SlaStatus = "within" | "approaching" | "breached";

export type Category = {
  id: number;
  name: string;
  slug: string;
  is_active: boolean;
};

export type TicketSla = {
  status: SlaStatus;
  due_at: string;
  approaching_at: string;
  escalated_at: string | null;
  resolved_within_sla: boolean | null;
};

export type Ticket = {
  id: number;
  ticket_number: string;
  subject: string;
  description: string;
  priority: TicketPriority;
  status: TicketStatus;
  category: Category;
  creator: User;
  assignee: User | null;
  attachments?: Attachment[];
  sla: TicketSla;
  resolved_at: string | null;
  closed_at: string | null;
  created_at: string;
  updated_at: string;
};

export type PaginationMeta = {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
};

export type Paginated<T> = {
  data: T[];
  meta: PaginationMeta;
};

export type FieldErrors = Record<string, string[]>;

export type RequestFailure = {
  status: number;
  message: string;
  errors: FieldErrors;
};

export type Agent = {
  id: number;
  name: string;
  email: string;
  is_available: boolean;
  active_tickets_count: number;
};

export type Reply = {
  id: number;
  ticket_id: number;
  body: string;
  is_internal: boolean;
  author: User;
  attachments?: Attachment[];
  created_at: string;
};

export type Attachment = {
  id: number;
  original_name: string;
  mime_type: string;
  size: number;
  uploaded_by: number;
  created_at: string;
};

export type ActivityType =
  | "created"
  | "imported"
  | "assigned"
  | "auto_assigned"
  | "claimed"
  | "unassigned"
  | "status_changed"
  | "priority_changed"
  | "category_changed"
  | "subject_changed"
  | "reply_added"
  | "note_added"
  | "attachment_added"
  | "sla_approaching"
  | "escalated";

export type Activity = {
  id: number;
  type: ActivityType;
  description: string;
  actor: User | null;
  payload: Record<string, unknown> | null;
  created_at: string;
};

export type NotificationKind =
  | "ticket_created"
  | "ticket_assigned"
  | "agent_replied"
  | "customer_replied"
  | "ticket_resolved"
  | "sla_approaching"
  | "sla_breached";

export type AppNotification = {
  id: string;
  kind: NotificationKind;
  data: {
    ticket_id: number;
    ticket_number: string;
    ticket_subject: string;
    message: string;
  };
  read_at: string | null;
  created_at: string;
};

export type NotificationFeed = Paginated<AppNotification> & { unread_count: number };

export type DashboardMetrics = {
  open: number;
  unassigned: number;
  critical: number;
  breached: number;
  approaching: number;
  resolved: number;
  resolved_last_7_days: number;
};

export type PriorityBreakdown = { priority: TicketPriority; label: string; total: number };

export type AgentWorkload = {
  agent_id: number;
  agent_name: string;
  total: number;
  breached: number;
};

export type DashboardData = {
  metrics: DashboardMetrics;
  by_priority: PriorityBreakdown[];
  by_agent?: AgentWorkload[];
  generated_at: string;
};

export type ImportStatus = "pending" | "processing" | "completed" | "failed";

export type Import = {
  id: number;
  original_name: string;
  status: ImportStatus;
  progress: number | null;
  total_rows: number | null;
  processed_rows: number;
  imported_rows: number;
  skipped_rows: number;
  failed_rows: number;
  error_message: string | null;
  user: User;
  started_at: string | null;
  finished_at: string | null;
  created_at: string;
};

export type ImportError = {
  id: number;
  row_number: number;
  kind: "invalid" | "duplicate";
  messages: string[];
  raw: Record<string, string>;
};
