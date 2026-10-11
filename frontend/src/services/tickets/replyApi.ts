import api from "@/api";
import type { Reply } from "@/types";
import { appendFiles } from "./attachmentApi";

export type ReplyPayload = {
  body: string;
  is_internal?: boolean;
};

export const list = async (ticketId: number) => {
  const response = await api.get<Reply[]>(`/tickets/${ticketId}/replies`);
  return response.data;
};

export const create = async (ticketId: number, payload: ReplyPayload, files: File[] = []) => {
  const form = new FormData();
  form.append("body", payload.body);
  form.append("is_internal", payload.is_internal ? "1" : "0");
  const response = await api.post<Reply>(`/tickets/${ticketId}/replies`, appendFiles(form, files));
  return response.data;
};
