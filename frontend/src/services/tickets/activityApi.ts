import api from "@/api";
import type { Activity, Paginated } from "@/types";

export const list = async (ticketId: number, page = 1) => {
  const response = await api.get<Paginated<Activity>>(`/tickets/${ticketId}/activities`, {
    params: { page },
  });
  return response.data;
};
