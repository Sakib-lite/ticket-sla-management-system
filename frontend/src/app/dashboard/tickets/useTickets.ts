"use client";

import { useFetch } from "@/hooks/useFetch";
import { ticketApi, type TicketListParams } from "@/services";

export const useTickets = (filters: TicketListParams) => {
  const { data, error, isLoading, refresh } = useFetch(
    () => ticketApi.list(filters),
    JSON.stringify(filters)
  );

  return {
    tickets: data?.data ?? [],
    meta: data?.meta ?? null,
    error,
    isLoading,
    refresh,
  };
};
