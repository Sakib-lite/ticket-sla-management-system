"use client";

import { useFetch } from "@/hooks/useFetch";
import { dashboardApi } from "@/services";

export const useDashboard = () => {
  const { data, isLoading, error, refresh } = useFetch(() => dashboardApi.get(), "dashboard");

  return { dashboard: data, isLoading, error, refresh };
};
