import api from "@/api";
import type { Reply } from "@/types";

export type ReplyPayload = {
  body: string;
  is_internal?: boolean;
};

export const list = async (ticketId: number) => {
  const response = await api.get<Reply[]>(`/tickets/${ticketId}/replies`);
  return response.data;
};

export const create = async (ticketId: number, payload: ReplyPayload) => {
  const response = await api.post<Reply>(`/tickets/${ticketId}/replies`, payload);
  return response.data;
};
