"use client";

import { useState } from "react";
import { useFetch } from "@/hooks/useFetch";
import { activityApi } from "@/services";

export const useTicketActivities = (ticketId: number, version: number) => {
  const { data, isLoading, setData } = useFetch(
    () => activityApi.list(ticketId),
    `ticket:${ticketId}:activities:${version}`
  );
  const [isLoadingMore, setIsLoadingMore] = useState(false);

  const hasMore = data ? data.meta.current_page < data.meta.last_page : false;

  const loadMore = async () => {
    if (!data || !hasMore) {
      return;
    }
    setIsLoadingMore(true);
    try {
      const next = await activityApi.list(ticketId, data.meta.current_page + 1);
      setData({ data: [...data.data, ...next.data], meta: next.meta });
    } finally {
      setIsLoadingMore(false);
    }
  };

  return { activities: data?.data ?? [], isLoading, hasMore, isLoadingMore, loadMore };
};
