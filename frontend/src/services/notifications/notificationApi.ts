import api from "@/api";
import type { AppNotification, NotificationFeed } from "@/types";

export const list = async () => {
  const response = await api.get<NotificationFeed>("/notifications");
  return response.data;
};

export const markRead = async (id: string) => {
  const response = await api.post<AppNotification>(`/notifications/${id}/read`);
  return response.data;
};

export const markAllRead = async () => {
  await api.post("/notifications/read-all");
};
