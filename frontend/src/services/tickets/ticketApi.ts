import api from "@/api";
import type { Paginated, SlaStatus, Ticket, TicketPriority, TicketStatus } from "@/types";

export type TicketView = "active" | "resolved" | "all";

export type TicketSort = "created_at" | "updated_at" | "sla_due_at" | "priority";

export type TicketListParams = {
  view?: TicketView;
  status?: TicketStatus;
  priority?: TicketPriority;
  sla_status?: SlaStatus;
  category_id?: number;
  assignee?: string;
  search?: string;
  sort?: TicketSort;
  direction?: "asc" | "desc";
  page?: number;
  per_page?: number;
};

export type TicketPayload = {
  subject: string;
  description: string;
  priority: TicketPriority;
  category_id: number;
};

export type TicketUpdatePayload = Partial<TicketPayload>;

export const list = async (params: TicketListParams) => {
  const response = await api.get<Paginated<Ticket>>("/tickets", { params });
  return response.data;
};

export const show = async (idOrNumber: number | string) => {
  const response = await api.get<Ticket>(`/tickets/${idOrNumber}`);
  return response.data;
};

export const create = async (payload: TicketPayload) => {
  const response = await api.post<Ticket>("/tickets", payload);
  return response.data;
};

export const update = async (id: number, payload: TicketUpdatePayload) => {
  const response = await api.patch<Ticket>(`/tickets/${id}`, payload);
  return response.data;
};

export const changeStatus = async (id: number, status: TicketStatus) => {
  const response = await api.post<Ticket>(`/tickets/${id}/status`, { status });
  return response.data;
};
