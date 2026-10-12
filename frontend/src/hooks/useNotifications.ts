"use client";

import { useEffect, useEffectEvent } from "react";
import { notificationApi } from "@/services";
import { useFetch } from "./useFetch";

const POLL_INTERVAL_MS = 30_000;

export const useNotifications = () => {
  const { data, refresh, setData } = useFetch(() => notificationApi.list(), "notifications");

  const poll = useEffectEvent(() => refresh());

  useEffect(() => {
    const timer = setInterval(() => poll(), POLL_INTERVAL_MS);
    return () => clearInterval(timer);
  }, []);

  const markRead = async (id: string) => {
    if (!data) {
      return;
    }
    const target = data.data.find((item) => item.id === id);
    if (!target || target.read_at) {
      return;
    }
    const readAt = new Date().toISOString();
    setData({
      ...data,
      unread_count: Math.max(0, data.unread_count - 1),
      data: data.data.map((item) => (item.id === id ? { ...item, read_at: readAt } : item)),
    });
    await notificationApi.markRead(id);
  };

  const markAllRead = async () => {
    if (!data) {
      return;
    }
    const readAt = new Date().toISOString();
    setData({
      ...data,
      unread_count: 0,
      data: data.data.map((item) => ({ ...item, read_at: item.read_at ?? readAt })),
    });
    await notificationApi.markAllRead();
  };

  return {
    notifications: data?.data ?? [],
    unreadCount: data?.unread_count ?? 0,
    markRead,
    markAllRead,
    refresh,
  };
};
