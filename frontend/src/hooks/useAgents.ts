"use client";

import { agentApi } from "@/services";
import { useAuthStore } from "@/stores/useAuthStore";
import { useFetch } from "./useFetch";

export const useAgents = () => {
  const isStaff = useAuthStore((state) => state.user?.role !== "customer");
  const { data, isLoading, refresh } = useFetch(
    () => (isStaff ? agentApi.list() : Promise.resolve([])),
    `agents:${isStaff}`
  );

  const agents = data ?? [];
  const options = agents.map((agent) => ({
    label: `${agent.name} (${agent.active_tickets_count} active)`,
    value: String(agent.id),
  }));

  return { agents, options, isLoading, refresh };
};
