import api from "@/api";
import type { Agent } from "@/types";

export const list = async () => {
  const response = await api.get<Agent[]>("/agents");
  return response.data;
};
