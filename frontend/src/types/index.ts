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
